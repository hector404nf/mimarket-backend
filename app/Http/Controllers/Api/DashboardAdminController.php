<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comision;
use App\Models\Liquidacion;
use App\Models\Orden;
use App\Models\Tienda;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardAdminController extends Controller
{
    /**
     * Obtener estadísticas generales del dashboard
     */
    public function getEstadisticasGenerales(): JsonResponse
    {
        try {
            // Verificar que sea administrador
            if (Auth::user()->tipo_usuario !== 'administrador') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para acceder a estas estadísticas'
                ], 403);
            }

            // Estadísticas generales
            $totalComisiones = Comision::sum('monto_comision');
            $comisionesPendientes = Comision::where('estado', Comision::ESTADO_PENDIENTE)->sum('monto_comision');
            $comisionesLiquidadas = Comision::where('estado', Comision::ESTADO_LIQUIDADA)->sum('monto_comision');
            
            $totalLiquidaciones = Liquidacion::count();
            $liquidacionesPendientes = Liquidacion::where('estado', Liquidacion::ESTADO_PENDIENTE)->count();
            $liquidacionesProcesadas = Liquidacion::where('estado', Liquidacion::ESTADO_PROCESADA)->count();
            $liquidacionesPagadas = Liquidacion::where('estado', Liquidacion::ESTADO_PAGADA)->count();

            $totalTiendas = Tienda::count();
            $tiendasActivas = Tienda::where('verificada', true)->count();
            
            $totalOrdenes = Orden::count();
            $ordenesConComisiones = Orden::where('comisiones_calculadas', true)->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'comisiones' => [
                        'total' => $totalComisiones,
                        'pendientes' => $comisionesPendientes,
                        'liquidadas' => $comisionesLiquidadas,
                        'porcentaje_liquidadas' => $totalComisiones > 0 ? ($comisionesLiquidadas / $totalComisiones) * 100 : 0
                    ],
                    'liquidaciones' => [
                        'total' => $totalLiquidaciones,
                        'pendientes' => $liquidacionesPendientes,
                        'procesadas' => $liquidacionesProcesadas,
                        'pagadas' => $liquidacionesPagadas
                    ],
                    'tiendas' => [
                        'total' => $totalTiendas,
                        'activas' => $tiendasActivas,
                        'porcentaje_activas' => $totalTiendas > 0 ? ($tiendasActivas / $totalTiendas) * 100 : 0
                    ],
                    'ordenes' => [
                        'total' => $totalOrdenes,
                        'con_comisiones' => $ordenesConComisiones,
                        'porcentaje_procesadas' => $totalOrdenes > 0 ? ($ordenesConComisiones / $totalOrdenes) * 100 : 0
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener ganancias por período
     */
    public function getGananciasPorPeriodo(Request $request): JsonResponse
    {
        try {
            // Verificar que sea administrador
            if (Auth::user()->tipo_usuario !== 'administrador') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para acceder a estas estadísticas'
                ], 403);
            }

            $periodo = $request->get('periodo', 'mes'); // mes, semana, año
            $fechaInicio = $this->calcularFechaInicio($periodo);
            $fechaFin = now();

            // Ganancias por comisiones
            $comisionesPorDia = Comision::select(
                    DB::raw('DATE(created_at) as fecha'),
                    DB::raw('SUM(monto_comision) as total_comisiones'),
                    DB::raw('COUNT(*) as cantidad_comisiones')
                )
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->groupBy('fecha')
                ->orderBy('fecha')
                ->get();

            // Top tiendas por comisiones
            $topTiendas = Comision::select(
                    'tiendas.nombre_tienda',
                    'tiendas.id_tienda',
                    DB::raw('SUM(comisiones.monto_comision) as total_comisiones'),
                    DB::raw('COUNT(comisiones.id_comision) as cantidad_comisiones')
                )
                ->join('tiendas', 'comisiones.id_tienda', '=', 'tiendas.id_tienda')
                ->whereBetween('comisiones.created_at', [$fechaInicio, $fechaFin])
                ->groupBy('tiendas.id_tienda', 'tiendas.nombre_tienda')
                ->orderBy('total_comisiones', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'periodo' => $periodo,
                    'fecha_inicio' => $fechaInicio->format('Y-m-d'),
                    'fecha_fin' => $fechaFin->format('Y-m-d'),
                    'ganancias_por_dia' => $comisionesPorDia,
                    'top_tiendas' => $topTiendas,
                    'resumen' => [
                        'total_comisiones' => $comisionesPorDia->sum('total_comisiones'),
                        'cantidad_comisiones' => $comisionesPorDia->sum('cantidad_comisiones'),
                        'promedio_diario' => $comisionesPorDia->avg('total_comisiones')
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener ganancias: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener liquidaciones pendientes de procesamiento
     */
    public function getLiquidacionesPendientes(): JsonResponse
    {
        try {
            // Verificar que sea administrador
            if (Auth::user()->tipo_usuario !== 'administrador') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para acceder a estas estadísticas'
                ], 403);
            }

            $liquidacionesPendientes = Liquidacion::with(['tienda'])
                ->where('estado', Liquidacion::ESTADO_PENDIENTE)
                ->orderBy('created_at', 'asc')
                ->get();

            $montoTotalPendiente = $liquidacionesPendientes->sum('monto_total');

            return response()->json([
                'success' => true,
                'data' => [
                    'liquidaciones' => $liquidacionesPendientes,
                    'resumen' => [
                        'cantidad_pendientes' => $liquidacionesPendientes->count(),
                        'monto_total_pendiente' => $montoTotalPendiente,
                        'liquidacion_mas_antigua' => $liquidacionesPendientes->first()?->created_at,
                        'liquidacion_mas_reciente' => $liquidacionesPendientes->last()?->created_at
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener liquidaciones pendientes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener métricas de rendimiento del sistema
     */
    public function getMetricasRendimiento(): JsonResponse
    {
        try {
            // Verificar que sea administrador
            if (Auth::user()->tipo_usuario !== 'administrador') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para acceder a estas estadísticas'
                ], 403);
            }

            // Métricas de los últimos 30 días
            $fechaInicio = now()->subDays(30);

            $ordenesCreadas = Orden::where('created_at', '>=', $fechaInicio)->count();
            $ordenesConComisiones = Orden::where('created_at', '>=', $fechaInicio)
                ->where('comisiones_calculadas', true)->count();

            $tiempoPromedioLiquidacion = Liquidacion::where('estado', Liquidacion::ESTADO_PAGADA)
                ->where('created_at', '>=', $fechaInicio)
                ->selectRaw('AVG(DATEDIFF(fecha_pago, created_at)) as promedio_dias')
                ->value('promedio_dias');

            $comisionesPorPlan = Comision::select(
                    'planes_tienda.nombre_plan',
                    DB::raw('COUNT(*) as cantidad'),
                    DB::raw('SUM(monto_comision) as total_monto')
                )
                ->join('planes_tienda', 'comisiones.id_plan', '=', 'planes_tienda.id_plan')
                ->where('comisiones.created_at', '>=', $fechaInicio)
                ->groupBy('planes_tienda.id_plan', 'planes_tienda.nombre_plan')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'periodo_analisis' => '30 días',
                    'procesamiento_ordenes' => [
                        'ordenes_creadas' => $ordenesCreadas,
                        'ordenes_con_comisiones' => $ordenesConComisiones,
                        'porcentaje_procesamiento' => $ordenesCreadas > 0 ? ($ordenesConComisiones / $ordenesCreadas) * 100 : 0
                    ],
                    'tiempo_liquidacion' => [
                        'promedio_dias' => round($tiempoPromedioLiquidacion ?? 0, 2)
                    ],
                    'comisiones_por_plan' => $comisionesPorPlan
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener métricas de rendimiento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular fecha de inicio según el período
     */
    private function calcularFechaInicio(string $periodo)
    {
        switch ($periodo) {
            case 'semana':
                return now()->subWeek();
            case 'mes':
                return now()->subMonth();
            case 'año':
                return now()->subYear();
            default:
                return now()->subMonth();
        }
    }
}
