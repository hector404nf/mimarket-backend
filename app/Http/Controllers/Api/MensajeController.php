<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Mensaje;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class MensajeController extends Controller
{
    public function index(): JsonResponse
    {
        $mensajes = Mensaje::with(['remitente', 'destinatario'])->orderBy('created_at', 'desc')->get();
        return response()->json($mensajes);
    }

    public function show($id): JsonResponse
    {
        $mensaje = Mensaje::with(['remitente', 'destinatario'])->find($id);
        
        if (!$mensaje) {
            return response()->json(['message' => 'Mensaje no encontrado'], 404);
        }
        
        return response()->json($mensaje);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id_remitente' => 'required|exists:users,id',
                'user_id_destinatario' => 'required|exists:users,id|different:user_id_remitente',
                'asunto' => 'required|string|max:255',
                'contenido' => 'required|string',
                'leido' => 'boolean'
            ]);

            $mensaje = Mensaje::create($validated);
            $mensaje->load(['remitente', 'destinatario']);

            return response()->json($mensaje, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $mensaje = Mensaje::find($id);
        
        if (!$mensaje) {
            return response()->json(['message' => 'Mensaje no encontrado'], 404);
        }

        try {
            $validated = $request->validate([
                'leido' => 'boolean'
            ]);

            $mensaje->update($validated);
            $mensaje->load(['remitente', 'destinatario']);

            return response()->json($mensaje);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function destroy($id): JsonResponse
    {
        $mensaje = Mensaje::find($id);
        
        if (!$mensaje) {
            return response()->json(['message' => 'Mensaje no encontrado'], 404);
        }

        $mensaje->delete();
        return response()->json(['message' => 'Mensaje eliminado correctamente']);
    }

    public function getInbox($userId): JsonResponse
    {
        $mensajes = Mensaje::where('user_id_destinatario', $userId)
                          ->with(['remitente'])
                          ->orderBy('created_at', 'desc')
                          ->get();
        
        return response()->json($mensajes);
    }

    public function getSent($userId): JsonResponse
    {
        $mensajes = Mensaje::where('user_id_remitente', $userId)
                          ->with(['destinatario'])
                          ->orderBy('created_at', 'desc')
                          ->get();
        
        return response()->json($mensajes);
    }

    public function getConversation($userId1, $userId2): JsonResponse
    {
        $mensajes = Mensaje::where(function($query) use ($userId1, $userId2) {
                              $query->where('user_id_remitente', $userId1)
                                    ->where('user_id_destinatario', $userId2);
                          })
                          ->orWhere(function($query) use ($userId1, $userId2) {
                              $query->where('user_id_remitente', $userId2)
                                    ->where('user_id_destinatario', $userId1);
                          })
                          ->with(['remitente', 'destinatario'])
                          ->orderBy('created_at', 'asc')
                          ->get();
        
        return response()->json($mensajes);
    }

    public function markAsRead($id): JsonResponse
    {
        $mensaje = Mensaje::find($id);
        
        if (!$mensaje) {
            return response()->json(['message' => 'Mensaje no encontrado'], 404);
        }

        $mensaje->update(['leido' => true]);
        $mensaje->load(['remitente', 'destinatario']);

        return response()->json($mensaje);
    }

    public function markAllAsRead($userId): JsonResponse
    {
        Mensaje::where('user_id_destinatario', $userId)
               ->where('leido', false)
               ->update(['leido' => true]);

        return response()->json(['message' => 'Todos los mensajes marcados como leídos']);
    }

    public function getUnreadCount($userId): JsonResponse
    {
        $count = Mensaje::where('user_id_destinatario', $userId)
                       ->where('leido', false)
                       ->count();
        
        return response()->json(['unread_count' => $count]);
    }

    public function getUnread($userId): JsonResponse
    {
        $mensajes = Mensaje::where('user_id_destinatario', $userId)
                          ->where('leido', false)
                          ->with(['remitente'])
                          ->orderBy('created_at', 'desc')
                          ->get();
        
        return response()->json($mensajes);
    }

    public function getContacts($userId): JsonResponse
    {
        // Obtener usuarios con los que ha tenido conversaciones
        $contacts = Mensaje::where('user_id_remitente', $userId)
                          ->orWhere('user_id_destinatario', $userId)
                          ->with(['remitente', 'destinatario'])
                          ->get()
                          ->flatMap(function($mensaje) use ($userId) {
                              return [
                                  $mensaje->user_id_remitente == $userId ? $mensaje->destinatario : $mensaje->remitente
                              ];
                          })
                          ->unique('id')
                          ->values();
        
        return response()->json($contacts);
    }
}