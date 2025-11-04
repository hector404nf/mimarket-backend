<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Models\Tienda;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class NotificacionController extends Controller
{
    public function index(): JsonResponse
    {
        $notificaciones = Notificacion::with('user')->orderBy('created_at', 'desc')->get();
        return response()->json($notificaciones);
    }

    public function show($id): JsonResponse
    {
        $notificacion = Notificacion::with('user')->find($id);
        
        if (!$notificacion) {
            return response()->json(['message' => 'Notificación no encontrada'], 404);
        }
        
        return response()->json($notificacion);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'tipo' => 'required|string|max:50',
                'titulo' => 'required|string|max:255',
                'mensaje' => 'required|string',
                'url_accion' => 'nullable|string|max:500',
                'leida' => 'boolean'
            ]);

            $notificacion = Notificacion::create($validated);
            $notificacion->load('user');

            return response()->json($notificacion, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $notificacion = Notificacion::find($id);
        
        if (!$notificacion) {
            return response()->json(['message' => 'Notificación no encontrada'], 404);
        }

        try {
            $validated = $request->validate([
                'leida' => 'boolean'
            ]);

            $notificacion->update($validated);
            $notificacion->load('user');

            return response()->json($notificacion);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function destroy($id): JsonResponse
    {
        $notificacion = Notificacion::find($id);
        
        if (!$notificacion) {
            return response()->json(['message' => 'Notificación no encontrada'], 404);
        }

        $notificacion->delete();
        return response()->json(['message' => 'Notificación eliminada correctamente']);
    }

    public function getByUser($userId): JsonResponse
    {
        $notificaciones = Notificacion::where('user_id', $userId)
                                    ->orderBy('created_at', 'desc')
                                    ->get();
        
        return response()->json($notificaciones);
    }

    public function markAsRead($id): JsonResponse
    {
        $notificacion = Notificacion::find($id);
        
        if (!$notificacion) {
            return response()->json(['message' => 'Notificación no encontrada'], 404);
        }

        $notificacion->update(['leida' => true]);
        $notificacion->load('user');

        return response()->json($notificacion);
    }

    public function markAllAsRead($userId): JsonResponse
    {
        Notificacion::where('user_id', $userId)
                   ->where('leida', false)
                   ->update(['leida' => true]);

        return response()->json(['message' => 'Todas las notificaciones marcadas como leídas']);
    }

    public function getUnreadCount($userId): JsonResponse
    {
        $count = Notificacion::where('user_id', $userId)
                            ->where('leida', false)
                            ->count();
        
        return response()->json(['unread_count' => $count]);
    }

    public function getUnread($userId): JsonResponse
    {
        $notificaciones = Notificacion::where('user_id', $userId)
                                    ->where('leida', false)
                                    ->orderBy('created_at', 'desc')
                                    ->get();
        
        return response()->json($notificaciones);
    }

    public function deleteAllRead($userId): JsonResponse
    {
        Notificacion::where('user_id', $userId)
                   ->where('leida', true)
                   ->delete();

        return response()->json(['message' => 'Notificaciones leídas eliminadas correctamente']);
    }

    /**
     * Obtener notificaciones de una tienda específica
     */
    public function getByTienda($tiendaId): JsonResponse
    {
        // Obtener el user_id de la tienda
        $tienda = Tienda::find($tiendaId);
        if (!$tienda) {
            return response()->json(['message' => 'Tienda no encontrada'], 404);
        }

        $notificaciones = Notificacion::where('user_id', $tienda->user_id)
                                     ->orderBy('created_at', 'desc')
                                     ->get();
        
        return response()->json($notificaciones);
    }
}