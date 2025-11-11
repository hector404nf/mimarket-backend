<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comision;
use App\Models\Liquidacion;
use App\Models\Tienda;
use App\Services\ComisionService;
use App\Services\NotificacionComisionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ComisionController extends Controller
{
    protected $comisionService;
    protected $notificacionService;

    public function __construct(ComisionService $comisionService, NotificacionComisionService $notificacionService)
    {
        $this->comisionService = $comisionService;
        $this->notificacionService = $notificacionService;
    }

    /**
     * Obtener comisiones de una tienda específica
     */
    public function getComisionesTienda(Request $request, $tiendaId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'estado' => 'nullable|in:pendiente,pagada,cancelada',
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Verificar que la tienda existe y el usuario tiene permisos
            $tienda = Tienda::findOrFail($tiendaId);
            
            if (Auth::user()->tipo_usuario !== 'administrador' && 
                Auth::user()->id !== $tienda->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver estas comisiones'
                ], 403);
            }

            $query = Comision::where('id_tienda', $tiendaId)
                ->with(['orden', 'planTienda']);

            // Filtros opcionales
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            if ($request->has('fecha_inicio')) {
                $query->whereDate('created_at', '>=', $request->fecha_inicio);
            }

            if ($request->has('fecha_fin')) {
                $query->whereDate('created_at', '<=', $request->fecha_fin);
            }

            $perPage = $request->get('per_page', 15);
            $comisiones = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $comisiones,
                'message' => 'Comisiones obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener comisiones de tienda: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener resumen de comisiones por tienda (método legacy)
     */
    public function getResumenComisiones($tiendaId): JsonResponse
    {
        try {
            $tienda = Tienda::findOrFail($tiendaId);
            
            if (Auth::user()->tipo_usuario !== 'administrador' && 
                Auth::user()->id !== $tienda->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver este resumen'
                ], 403);
            }

            // Usar la función de servicio correcta
            $resumen = $this->comisionService->getResumenComisionesTienda($tiendaId);

            return response()->json([
                'success' => true,
                'data' => $resumen,
                'message' => 'Resumen obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener resumen de comisiones: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener resumen de comisiones por tienda (coincide con rutas)
     */
    public function getResumenComisionesTienda(Request $request, $tiendaId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'fecha_inicio' => 'nullable|date',
                'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            $tienda = Tienda::findOrFail($tiendaId);
            
            if (Auth::user()->tipo_usuario !== 'administrador' && 
                Auth::user()->id !== $tienda->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver este resumen'
                ], 403);
            }

            $resumen = $this->comisionService->getResumenComisionesTienda(
                $tiendaId,
                $request->get('fecha_inicio'),
                $request->get('fecha_fin')
            );

            return response()->json([
                'success' => true,
                'data' => $resumen,
                'message' => 'Resumen obtenido exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener resumen de comisiones (tienda): ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Crear liquidación para una tienda
     */
    public function crearLiquidacion(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_tienda' => 'required|exists:tiendas,id',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
                'notas' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            // Solo administradores pueden crear liquidaciones
            if (Auth::user()->tipo_usuario !== 'administrador') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para crear liquidaciones'
                ], 403);
            }

            $liquidacion = $this->comisionService->crearLiquidacionAutomatica(
                $request->id_tienda,
                $request->fecha_inicio,
                $request->fecha_fin,
                $request->notas
            );

            return response()->json([
                'success' => true,
                'data' => $liquidacion->load('tienda', 'comisiones'),
                'message' => 'Liquidación creada exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al crear liquidación: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la liquidación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener liquidaciones de una tienda
     */
    public function getLiquidacionesTienda(Request $request, $tiendaId): JsonResponse
    {
        try {
            $tienda = Tienda::findOrFail($tiendaId);
            
            if (Auth::user()->tipo_usuario !== 'administrador' && 
                Auth::user()->id !== $tienda->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver estas liquidaciones'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'estado' => 'nullable|in:pendiente,procesada,pagada,cancelada',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            $query = Liquidacion::where('id_tienda', $tiendaId)
                ->with(['tienda', 'comisiones']);

            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }

            $perPage = $request->get('per_page', 15);
            $liquidaciones = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $liquidaciones,
                'message' => 'Liquidaciones obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener liquidaciones: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Procesar una liquidación (marcar como procesada)
     */
    public function procesarLiquidacion($liquidacionId): JsonResponse
    {
        try {
            // Solo administradores pueden procesar liquidaciones
            if (Auth::user()->tipo_usuario !== 'administrador') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para procesar liquidaciones'
                ], 403);
            }

            $liquidacion = Liquidacion::findOrFail($liquidacionId);
            $liquidacion->procesar();

            // Enviar notificación de liquidación procesada
            $this->notificacionService->notificarLiquidacionProcesada($liquidacion);

            return response()->json([
                'success' => true,
                'data' => $liquidacion->load('tienda', 'comisiones'),
                'message' => 'Liquidación procesada exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al procesar liquidación: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la liquidación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Marcar liquidación como pagada
     */
    public function marcarLiquidacionPagada(Request $request, $liquidacionId): JsonResponse
    {
        try {
            // Solo administradores pueden marcar como pagada
            if (Auth::user()->tipo_usuario !== 'administrador') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para marcar liquidaciones como pagadas'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'notas' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de entrada inválidos',
                    'errors' => $validator->errors()
                ], 400);
            }

            $liquidacion = Liquidacion::findOrFail($liquidacionId);
            $liquidacion->marcarComoPagada($request->notas);

            // Enviar notificación de liquidación pagada
            $this->notificacionService->notificarLiquidacionPagada($liquidacion);

            return response()->json([
                'success' => true,
                'data' => $liquidacion->load('tienda', 'comisiones'),
                'message' => 'Liquidación marcada como pagada exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al marcar liquidación como pagada: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al marcar la liquidación como pagada: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas generales de comisiones (solo administradores)
     */
    public function getEstadisticasGenerales(): JsonResponse
    {
        try {
            // Solo administradores pueden ver estadísticas generales
            if (Auth::user()->tipo_usuario !== 'administrador') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver estas estadísticas'
                ], 403);
            }

            $estadisticas = $this->comisionService->obtenerEstadisticasGenerales();

            return response()->json([
                'success' => true,
                'data' => $estadisticas,
                'message' => 'Estadísticas obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener estadísticas generales: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Recalcular comisiones de una orden específica
     */
    public function recalcularComisionesOrden($ordenId): JsonResponse
    {
        try {
            // Solo administradores pueden recalcular comisiones
            if (Auth::user()->tipo_usuario !== 'administrador') {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para recalcular comisiones'
                ], 403);
            }

            $comisiones = $this->comisionService->recalcularComisionesOrden($ordenId);

            return response()->json([
                'success' => true,
                'data' => $comisiones,
                'message' => 'Comisiones recalculadas exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al recalcular comisiones: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al recalcular las comisiones: ' . $e->getMessage()
            ], 500);
        }
    }
}
