<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LogActividad;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class LogActividadController extends Controller
{
    public function index(): JsonResponse
    {
        $logs = LogActividad::with('user')->orderBy('created_at', 'desc')->get();
        return response()->json($logs);
    }

    public function show($id): JsonResponse
    {
        $log = LogActividad::with('user')->find($id);
        
        if (!$log) {
            return response()->json(['message' => 'Log de actividad no encontrado'], 404);
        }
        
        return response()->json($log);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'nullable|exists:users,id',
                'accion' => 'required|string|max:100',
                'tabla_afectada' => 'nullable|string|max:100',
                'id_registro_afectado' => 'nullable|integer',
                'detalles' => 'nullable|array',
                'ip_address' => 'nullable|ip',
                'user_agent' => 'nullable|string'
            ]);

            $log = LogActividad::create($validated);
            $log->load('user');

            return response()->json($log, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function destroy($id): JsonResponse
    {
        $log = LogActividad::find($id);
        
        if (!$log) {
            return response()->json(['message' => 'Log de actividad no encontrado'], 404);
        }

        $log->delete();
        return response()->json(['message' => 'Log de actividad eliminado correctamente']);
    }

    public function getByUser($userId): JsonResponse
    {
        $logs = LogActividad::where('user_id', $userId)
                           ->orderBy('created_at', 'desc')
                           ->get();
        
        return response()->json($logs);
    }

    public function getByAction($action): JsonResponse
    {
        $logs = LogActividad::where('accion', $action)
                           ->with('user')
                           ->orderBy('created_at', 'desc')
                           ->get();
        
        return response()->json($logs);
    }

    public function getByTable($table): JsonResponse
    {
        $logs = LogActividad::where('tabla_afectada', $table)
                           ->with('user')
                           ->orderBy('created_at', 'desc')
                           ->get();
        
        return response()->json($logs);
    }

    public function getByDateRange(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio'
            ]);

            $logs = LogActividad::whereBetween('created_at', [
                                   $validated['fecha_inicio'],
                                   $validated['fecha_fin']
                               ])
                               ->with('user')
                               ->orderBy('created_at', 'desc')
                               ->get();
            
            return response()->json($logs);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function getStats(): JsonResponse
    {
        $stats = [
            'total_logs' => LogActividad::count(),
            'logs_hoy' => LogActividad::whereDate('created_at', today())->count(),
            'logs_semana' => LogActividad::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'acciones_mas_comunes' => LogActividad::selectRaw('accion, COUNT(*) as total')
                                                 ->groupBy('accion')
                                                 ->orderBy('total', 'desc')
                                                 ->limit(10)
                                                 ->get(),
            'tablas_mas_afectadas' => LogActividad::selectRaw('tabla_afectada, COUNT(*) as total')
                                                 ->whereNotNull('tabla_afectada')
                                                 ->groupBy('tabla_afectada')
                                                 ->orderBy('total', 'desc')
                                                 ->limit(10)
                                                 ->get(),
            'usuarios_mas_activos' => LogActividad::selectRaw('user_id, COUNT(*) as total')
                                                 ->whereNotNull('user_id')
                                                 ->groupBy('user_id')
                                                 ->orderBy('total', 'desc')
                                                 ->limit(10)
                                                 ->with('user')
                                                 ->get()
        ];
        
        return response()->json($stats);
    }

    public function clearOldLogs(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'dias' => 'required|integer|min:1'
            ]);

            $fechaLimite = now()->subDays($validated['dias']);
            $eliminados = LogActividad::where('created_at', '<', $fechaLimite)->delete();

            return response()->json([
                'message' => 'Logs antiguos eliminados correctamente',
                'logs_eliminados' => $eliminados
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }
}