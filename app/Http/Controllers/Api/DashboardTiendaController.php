<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Orden;
use App\Models\DetalleOrden;
use App\Models\Carrito;
use App\Models\Resena;
use App\Models\Tienda;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardTiendaController extends Controller
{
    /**
     * Obtener métricas y analíticas por tienda.
     * Filtros opcionales: fecha_inicio (YYYY-MM-DD), fecha_fin (YYYY-MM-DD).
     */
    public function getAnaliticasTienda(Request $request, $tiendaId): JsonResponse
    {
        try {
            // Autorización: admin o propietario de la tienda
            $tienda = Tienda::findOrFail($tiendaId);
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }
            if ($user->tipo_usuario !== 'administrador' && $user->id !== $tienda->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver las analíticas de esta tienda'
                ], 403);
            }

            $fechaInicio = $request->get('fecha_inicio');
            $fechaFin = $request->get('fecha_fin');
            if (!$fechaInicio || !$fechaFin) {
                $fechaFin = now();
                $fechaInicio = now()->subDays(30);
            } else {
                $fechaInicio = \Carbon\Carbon::parse($fechaInicio)->startOfDay();
                $fechaFin = \Carbon\Carbon::parse($fechaFin)->endOfDay();
            }

            // Órdenes por tienda (filtradas por rango de fechas)
            $ordenesQuery = Orden::whereHas('detalles.producto', function ($q) use ($tiendaId) {
                    $q->where('id_tienda', $tiendaId);
                })
                ->whereBetween('created_at', [$fechaInicio, $fechaFin]);

            // Conteo por estado
            $porEstado = $ordenesQuery->clone()
                ->select('estado', DB::raw('COUNT(*) as total'))
                ->groupBy('estado')
                ->pluck('total', 'estado');

            $totalOrdenes = (int) ($ordenesQuery->clone()->count());

            // Ingresos específicos de la tienda (sumando subtotales de DetalleOrden)
            $ingresosTienda = (float) DetalleOrden::whereHas('producto', function ($q) use ($tiendaId) {
                    $q->where('id_tienda', $tiendaId);
                })
                ->whereHas('orden', function ($q) use ($fechaInicio, $fechaFin) {
                    $q->whereBetween('created_at', [$fechaInicio, $fechaFin]);
                })
                ->sum('subtotal');

            // Tiempo promedio de entrega (aprox): órdenes entregadas, diff updated_at - created_at
            $entregadas = $ordenesQuery->clone()->where('estado', 'entregado')->get(['created_at', 'updated_at']);
            $tiemposEntregaHoras = [];
            foreach ($entregadas as $o) {
                if ($o->updated_at && $o->created_at) {
                    $tiemposEntregaHoras[] = \Carbon\Carbon::parse($o->created_at)->diffInHours(\Carbon\Carbon::parse($o->updated_at));
                }
            }
            $promedioEntregaHoras = count($tiemposEntregaHoras) > 0 ? array_sum($tiemposEntregaHoras) / count($tiemposEntregaHoras) : null;

            // Conversión: usuarios con carritos vs usuarios con órdenes
            $usuariosConCarrito = Carrito::whereHas('producto', function ($q) use ($tiendaId) {
                    $q->where('id_tienda', $tiendaId);
                })
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->distinct('user_id')
                ->pluck('user_id')
                ->toArray();

            $usuariosConOrden = Orden::whereHas('detalles.producto', function ($q) use ($tiendaId) {
                    $q->where('id_tienda', $tiendaId);
                })
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->distinct('user_id')
                ->pluck('user_id')
                ->toArray();

            $totalUsuariosCarrito = count($usuariosConCarrito);
            $totalUsuariosOrden = count($usuariosConOrden);
            $usuariosCarritoSet = collect($usuariosConCarrito)->unique();
            $usuariosOrdenSet = collect($usuariosConOrden)->unique();
            $usuariosConvierten = $usuariosOrdenSet->intersect($usuariosCarritoSet)->count();
            $conversionRate = $totalUsuariosCarrito > 0 ? round(($usuariosConvierten / $totalUsuariosCarrito) * 100, 2) : null;

            // Satisfacción: promedio y conteo de reseñas para productos de la tienda
            $resenasQuery = Resena::whereHas('producto', function ($q) use ($tiendaId) {
                    $q->where('id_tienda', $tiendaId);
                })
                ->whereBetween('created_at', [$fechaInicio, $fechaFin]);

            $totalResenas = (int) $resenasQuery->clone()->count();
            $promedioCalificacion = (float) $resenasQuery->clone()->avg('calificacion');

            // Top productos por ventas (cantidad y subtotal) para la tienda
            $topProductos = DetalleOrden::select('id_producto', DB::raw('SUM(cantidad) as cantidad_total'), DB::raw('SUM(subtotal) as monto_total'))
                ->whereHas('producto', function ($q) use ($tiendaId) {
                    $q->where('id_tienda', $tiendaId);
                })
                ->whereHas('orden', function ($q) use ($fechaInicio, $fechaFin) {
                    $q->whereBetween('created_at', [$fechaInicio, $fechaFin]);
                })
                ->groupBy('id_producto')
                ->orderByDesc('cantidad_total')
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'rango' => [
                        'fecha_inicio' => $fechaInicio->format('Y-m-d'),
                        'fecha_fin' => $fechaFin->format('Y-m-d')
                    ],
                    'ordenes' => [
                        'total' => $totalOrdenes,
                        'por_estado' => [
                            'pendiente' => (int) ($porEstado['pendiente'] ?? 0),
                            'procesando' => (int) ($porEstado['procesando'] ?? 0),
                            'enviado' => (int) ($porEstado['enviado'] ?? 0),
                            'entregado' => (int) ($porEstado['entregado'] ?? 0),
                            'cancelado' => (int) ($porEstado['cancelado'] ?? 0),
                        ],
                        'ingresos_tienda' => $ingresosTienda,
                        'tiempo_promedio_entrega_horas' => $promedioEntregaHoras,
                    ],
                    'conversion' => [
                        'usuarios_con_carrito' => $totalUsuariosCarrito,
                        'usuarios_con_orden' => $totalUsuariosOrden,
                        'usuarios_convierten' => $usuariosConvierten,
                        'tasa_conversion_por_usuario' => $conversionRate,
                    ],
                    'satisfaccion' => [
                        'total_resenas' => $totalResenas,
                        'promedio_calificacion' => $promedioCalificacion,
                    ],
                    'top_productos' => $topProductos,
                ]
            ]);

        } catch (\Throwable $e) {
            Log::error('Error en getAnaliticasTienda: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }
}