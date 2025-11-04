<?php

namespace App\Services;

use App\Models\Comision;
use App\Models\Liquidacion;
use App\Models\Notificacion;
use App\Models\Tienda;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificacionComisionService
{
    /**
     * Notificar nueva comisión generada
     */
    public function notificarNuevaComision(Comision $comision): void
    {
        try {
            $tienda = $comision->tienda;
            $usuario = $tienda->user;

            $this->crearNotificacion(
                $usuario->id,
                'Nueva comisión generada',
                "Se ha generado una nueva comisión de $" . number_format($comision->monto, 2) . " para tu tienda {$tienda->nombre_tienda}",
                'comision',
                $comision->id_comision
            );

            Log::info("Notificación de nueva comisión enviada", [
                'comision_id' => $comision->id_comision,
                'tienda_id' => $tienda->id_tienda,
                'usuario_id' => $usuario->id,
                'monto' => $comision->monto
            ]);

        } catch (\Exception $e) {
            Log::error("Error al notificar nueva comisión: " . $e->getMessage(), [
                'comision_id' => $comision->id_comision
            ]);
        }
    }

    /**
     * Notificar liquidación creada
     */
    public function notificarLiquidacionCreada(Liquidacion $liquidacion): void
    {
        try {
            $tienda = $liquidacion->tienda;
            $usuario = $tienda->user;

            $this->crearNotificacion(
                $usuario->id,
                'Liquidación creada',
                "Se ha creado una liquidación por $" . number_format($liquidacion->monto_total, 2) . " para tu tienda {$tienda->nombre_tienda}",
                'liquidacion',
                $liquidacion->id_liquidacion
            );

            Log::info("Notificación de liquidación creada enviada", [
                'liquidacion_id' => $liquidacion->id_liquidacion,
                'tienda_id' => $tienda->id_tienda,
                'usuario_id' => $usuario->id,
                'monto' => $liquidacion->monto_total
            ]);

        } catch (\Exception $e) {
            Log::error("Error al notificar liquidación creada: " . $e->getMessage(), [
                'liquidacion_id' => $liquidacion->id_liquidacion
            ]);
        }
    }

    /**
     * Notificar liquidación procesada
     */
    public function notificarLiquidacionProcesada(Liquidacion $liquidacion): void
    {
        try {
            $tienda = $liquidacion->tienda;
            $usuario = $tienda->user;

            $this->crearNotificacion(
                $usuario->id,
                'Liquidación procesada',
                "Tu liquidación por $" . number_format($liquidacion->monto_total, 2) . " ha sido procesada y está lista para pago",
                'liquidacion',
                $liquidacion->id_liquidacion
            );

            Log::info("Notificación de liquidación procesada enviada", [
                'liquidacion_id' => $liquidacion->id_liquidacion,
                'tienda_id' => $tienda->id_tienda,
                'usuario_id' => $usuario->id,
                'monto' => $liquidacion->monto_total
            ]);

        } catch (\Exception $e) {
            Log::error("Error al notificar liquidación procesada: " . $e->getMessage(), [
                'liquidacion_id' => $liquidacion->id_liquidacion
            ]);
        }
    }

    /**
     * Notificar liquidación pagada
     */
    public function notificarLiquidacionPagada(Liquidacion $liquidacion): void
    {
        try {
            $tienda = $liquidacion->tienda;
            $usuario = $tienda->user;

            $this->crearNotificacion(
                $usuario->id,
                'Pago de liquidación completado',
                "El pago de tu liquidación por $" . number_format($liquidacion->monto_total, 2) . " ha sido completado",
                'liquidacion',
                $liquidacion->id_liquidacion
            );

            Log::info("Notificación de liquidación pagada enviada", [
                'liquidacion_id' => $liquidacion->id_liquidacion,
                'tienda_id' => $tienda->id_tienda,
                'usuario_id' => $usuario->id,
                'monto' => $liquidacion->monto_total
            ]);

        } catch (\Exception $e) {
            Log::error("Error al notificar liquidación pagada: " . $e->getMessage(), [
                'liquidacion_id' => $liquidacion->id_liquidacion
            ]);
        }
    }

    /**
     * Notificar comisiones acumuladas (notificación semanal)
     */
    public function notificarComisionesAcumuladas(Tienda $tienda, float $montoAcumulado, int $cantidadComisiones): void
    {
        try {
            $usuario = $tienda->user;

            $this->crearNotificacion(
                $usuario->id,
                'Resumen semanal de comisiones',
                "Esta semana has acumulado $" . number_format($montoAcumulado, 2) . " en {$cantidadComisiones} comisiones para tu tienda {$tienda->nombre_tienda}",
                'resumen_comisiones',
                $tienda->id_tienda
            );

            Log::info("Notificación de resumen semanal enviada", [
                'tienda_id' => $tienda->id_tienda,
                'usuario_id' => $usuario->id,
                'monto_acumulado' => $montoAcumulado,
                'cantidad_comisiones' => $cantidadComisiones
            ]);

        } catch (\Exception $e) {
            Log::error("Error al notificar comisiones acumuladas: " . $e->getMessage(), [
                'tienda_id' => $tienda->id_tienda
            ]);
        }
    }

    /**
     * Notificar a administradores sobre liquidaciones pendientes
     */
    public function notificarLiquidacionesPendientes(int $cantidadPendientes, float $montoTotal): void
    {
        try {
            $administradores = User::where('tipo_usuario', 'administrador')->get();

            foreach ($administradores as $admin) {
                $this->crearNotificacion(
                    $admin->id,
                    'Liquidaciones pendientes de procesamiento',
                    "Hay {$cantidadPendientes} liquidaciones pendientes por un total de $" . number_format($montoTotal, 2),
                    'admin_liquidaciones',
                    null
                );
            }

            Log::info("Notificaciones de liquidaciones pendientes enviadas a administradores", [
                'cantidad_pendientes' => $cantidadPendientes,
                'monto_total' => $montoTotal,
                'administradores_notificados' => $administradores->count()
            ]);

        } catch (\Exception $e) {
            Log::error("Error al notificar liquidaciones pendientes: " . $e->getMessage());
        }
    }

    /**
     * Crear una notificación en la base de datos
     */
    private function crearNotificacion(int $userId, string $titulo, string $mensaje, string $tipo, ?int $referenciaId = null): void
    {
        Notificacion::create([
            'user_id' => $userId,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'tipo' => $tipo,
            'referencia_id' => $referenciaId,
            'leida' => false,
            'fecha_creacion' => now()
        ]);
    }
}