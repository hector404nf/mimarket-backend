<?php

namespace App\Services;

use App\Models\Orden;
use App\Models\DetalleOrden;
use App\Models\Comision;
use App\Models\Liquidacion;
use App\Models\Tienda;
use App\Models\PlanTienda;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ComisionService
{
    protected $notificacionService;

    public function __construct(NotificacionComisionService $notificacionService)
    {
        $this->notificacionService = $notificacionService;
    }
    /**
     * Calcular comisiones para una orden específica
     */
    public function calcularComisionesOrden(Orden $orden): bool
    {
        try {
            DB::beginTransaction();

            // Verificar si ya se calcularon las comisiones
            if ($orden->comisiones_calculadas) {
                Log::info("Las comisiones ya fueron calculadas para la orden {$orden->id_orden}");
                return true;
            }

            $comisionTotal = 0;

            // Agrupar detalles por tienda
            $detallesPorTienda = $orden->detalles()
                ->with(['producto.tienda.planActual'])
                ->get()
                ->groupBy(function ($detalle) {
                    return $detalle->producto->tienda->id_tienda;
                });

            foreach ($detallesPorTienda as $idTienda => $detalles) {
                $tienda = $detalles->first()->producto->tienda;
                $plan = $tienda->planActual;

                if (!$plan) {
                    Log::warning("La tienda {$idTienda} no tiene un plan activo");
                    continue;
                }

                // Calcular subtotal de la tienda
                $subtotalTienda = $detalles->sum('subtotal');
                
                // Obtener porcentaje de comisión del plan
                $porcentajeComision = $plan->porcentaje_comision ?? 0;
                
                // Calcular comisión
                $montoComision = ($subtotalTienda * $porcentajeComision) / 100;

                // Crear registro de comisión
                $comision = Comision::create([
                    'id_orden' => $orden->id_orden,
                    'id_tienda' => $idTienda,
                    'id_plan' => $plan->id_plan,
                    'monto_venta' => $subtotalTienda,
                    'porcentaje_comision' => $porcentajeComision,
                    'monto_comision' => $montoComision,
                    'estado' => Comision::ESTADO_PENDIENTE,
                    'fecha_vencimiento' => now()->addDays($plan->dias_liquidacion ?? 30)
                ]);

                // Enviar notificación de nueva comisión
                $this->notificacionService->notificarNuevaComision($comision);

                // Actualizar detalles de orden con información de comisión
                foreach ($detalles as $detalle) {
                    $comisionDetalle = ($detalle->subtotal * $porcentajeComision) / 100;
                    $detalle->update([
                        'comision_tienda' => $comisionDetalle,
                        'porcentaje_comision' => $porcentajeComision
                    ]);
                }

                $comisionTotal += $montoComision;

                Log::info("Comisión calculada para tienda {$idTienda}: {$montoComision} ({$porcentajeComision}%)");
            }

            // Actualizar orden con información de comisiones
            $orden->update([
                'comision_total' => $comisionTotal,
                'comisiones_calculadas' => true,
                'fecha_calculo_comisiones' => now()
            ]);

            DB::commit();
            
            Log::info("Comisiones calculadas exitosamente para la orden {$orden->id_orden}. Total: {$comisionTotal}");
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error calculando comisiones para orden {$orden->id_orden}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Recalcular comisiones para una orden (en caso de cambios)
     */
    public function recalcularComisionesOrden(Orden $orden): bool
    {
        try {
            DB::beginTransaction();

            // Eliminar comisiones existentes
            Comision::where('id_orden', $orden->id_orden)->delete();

            // Resetear campos de comisión en detalles
            $orden->detalles()->update([
                'comision_tienda' => 0,
                'porcentaje_comision' => 0
            ]);

            // Resetear campos de comisión en orden
            $orden->update([
                'comision_total' => 0,
                'comisiones_calculadas' => false,
                'fecha_calculo_comisiones' => null
            ]);

            DB::commit();

            // Calcular nuevamente
            return $this->calcularComisionesOrden($orden);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error recalculando comisiones para orden {$orden->id_orden}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener comisiones pendientes de una tienda
     */
    public function getComisionesPendientesTienda(int $idTienda)
    {
        return Comision::with(['orden', 'plan'])
            ->deTienda($idTienda)
            ->pendientes()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Obtener resumen de comisiones por tienda
     */
    public function getResumenComisionesTienda(int $idTienda, $fechaInicio = null, $fechaFin = null): array
    {
        $query = Comision::deTienda($idTienda);

        if ($fechaInicio) {
            $query->whereDate('created_at', '>=', $fechaInicio);
        }

        if ($fechaFin) {
            $query->whereDate('created_at', '<=', $fechaFin);
        }

        $comisiones = $query->get();

        return [
            'total_comisiones' => $comisiones->count(),
            'monto_total' => $comisiones->sum('monto_comision'),
            'pendientes' => $comisiones->where('estado', Comision::ESTADO_PENDIENTE)->count(),
            'monto_pendiente' => $comisiones->where('estado', Comision::ESTADO_PENDIENTE)->sum('monto_comision'),
            'pagadas' => $comisiones->where('estado', Comision::ESTADO_PAGADA)->count(),
            'monto_pagado' => $comisiones->where('estado', Comision::ESTADO_PAGADA)->sum('monto_comision'),
            'vencidas' => $comisiones->filter(function ($comision) {
                return $comision->estaVencida();
            })->count(),
            'promedio_comision' => $comisiones->avg('monto_comision') ?? 0,
        ];
    }

    /**
     * Crear liquidación automática para una tienda
     */
    public function crearLiquidacionAutomatica(int $idTienda, $fechaInicio, $fechaFin): ?Liquidacion
    {
        try {
            DB::beginTransaction();

            // Obtener comisiones pendientes en el período
            $comisiones = Comision::deTienda($idTienda)
                ->pendientes()
                ->whereDate('created_at', '>=', $fechaInicio)
                ->whereDate('created_at', '<=', $fechaFin)
                ->get();

            if ($comisiones->isEmpty()) {
                Log::info("No hay comisiones pendientes para la tienda {$idTienda} en el período especificado");
                return null;
            }

            // Crear liquidación
            $liquidacion = Liquidacion::create([
                'id_tienda' => $idTienda,
                'numero_liquidacion' => Liquidacion::generarNumeroLiquidacion($idTienda),
                'monto_total' => $comisiones->sum('monto_comision'),
                'cantidad_ordenes' => $comisiones->pluck('id_orden')->unique()->count(),
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'estado' => Liquidacion::ESTADO_PENDIENTE
            ]);

            // Asociar comisiones a la liquidación
            $liquidacion->comisiones()->attach($comisiones->pluck('id_comision'));

            // Enviar notificación de liquidación creada
            $this->notificacionService->notificarLiquidacionCreada($liquidacion);

            DB::commit();

            Log::info("Liquidación automática creada para tienda {$idTienda}: {$liquidacion->numero_liquidacion}");
            return $liquidacion;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creando liquidación automática para tienda {$idTienda}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Procesar liquidaciones vencidas automáticamente
     */
    public function procesarLiquidacionesVencidas(): int
    {
        $liquidacionesProcesadas = 0;

        try {
            // Obtener liquidaciones pendientes que deberían ser procesadas
            $liquidaciones = Liquidacion::pendientes()
                ->where('fecha_fin', '<', now()->subDays(7)) // 7 días de gracia
                ->get();

            foreach ($liquidaciones as $liquidacion) {
                if ($liquidacion->puedeSerProcesada()) {
                    $liquidacion->procesar();
                    $liquidacionesProcesadas++;
                    
                    Log::info("Liquidación procesada automáticamente: {$liquidacion->numero_liquidacion}");
                }
            }

        } catch (\Exception $e) {
            Log::error("Error procesando liquidaciones vencidas: " . $e->getMessage());
        }

        return $liquidacionesProcesadas;
    }

    /**
     * Obtener estadísticas generales de comisiones
     */
    public function getEstadisticasGenerales(): array
    {
        $hoy = now();
        $inicioMes = $hoy->copy()->startOfMonth();
        $finMes = $hoy->copy()->endOfMonth();

        return [
            'comisiones_mes_actual' => [
                'total' => Comision::whereDate('created_at', '>=', $inicioMes)
                    ->whereDate('created_at', '<=', $finMes)
                    ->sum('monto_comision'),
                'cantidad' => Comision::whereDate('created_at', '>=', $inicioMes)
                    ->whereDate('created_at', '<=', $finMes)
                    ->count(),
            ],
            'comisiones_pendientes' => [
                'total' => Comision::pendientes()->sum('monto_comision'),
                'cantidad' => Comision::pendientes()->count(),
            ],
            'comisiones_vencidas' => [
                'total' => Comision::vencidas()->sum('monto_comision'),
                'cantidad' => Comision::vencidas()->count(),
            ],
            'liquidaciones_pendientes' => Liquidacion::pendientes()->count(),
            'tiendas_con_comisiones' => Comision::distinct('id_tienda')->count('id_tienda'),
        ];
    }
}