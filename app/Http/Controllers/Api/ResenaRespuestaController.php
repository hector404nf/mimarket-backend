<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ResenaRespuesta;
use App\Models\Resena;
use App\Models\Notificacion;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ResenaRespuestaController extends Controller
{
    public function getByResena($resenaId): JsonResponse
    {
        $respuestas = ResenaRespuesta::where('id_resena', $resenaId)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($respuestas);
    }

    public function store(Request $request, $resenaId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'respuesta' => 'required|string|max:1000',
            ]);

            // Verificar reseña existente
            $resena = Resena::with(['user', 'producto'])->find($resenaId);
            if (!$resena) {
                return response()->json(['message' => 'Reseña no encontrada'], 404);
            }

            $respuesta = ResenaRespuesta::create([
                'id_resena' => $resenaId,
                'user_id' => $validated['user_id'],
                'respuesta' => $validated['respuesta'],
            ]);

            $respuesta->load('user');

            // Notificar al autor de la reseña
            try {
                $authorId = $resena->user_id;
                $productName = $resena->producto->nombre ?? 'Producto';

                Notificacion::create([
                    'user_id'   => $authorId,
                    'tipo'      => 'resena_respuesta',
                    'titulo'    => 'Han respondido tu reseña',
                    'mensaje'   => sprintf('Han respondido tu reseña sobre "%s".', $productName),
                    'url_accion'=> '/productos/' . $resena->id_producto,
                    'leida'     => false,
                ]);

                // Push al autor
                try {
                    app(PushNotificationService::class)->sendToUser($authorId, [
                        'title' => 'Han respondido tu reseña',
                        'body'  => $productName,
                        'url'   => '/productos/' . $resena->id_producto,
                        'icon'  => '/placeholder-user.jpg',
                    ]);
                } catch (\Throwable $e) { /* ignore */ }
            } catch (\Throwable $e) { /* ignore */ }

            // Notificar a la tienda propietaria del producto (evitar auto-notificación)
            try {
                if ($resena->producto && $resena->producto->tienda && $resena->producto->tienda->user) {
                    $storeUserId = $resena->producto->tienda->user->id;
                    if ($storeUserId !== (int)$validated['user_id']) {
                        $productName = $resena->producto->nombre ?? 'Producto';

                        Notificacion::create([
                            'user_id'   => $storeUserId,
                            'tipo'      => 'resena_respuesta',
                            'titulo'    => 'Nueva respuesta en reseña',
                            'mensaje'   => sprintf('Hay una respuesta en la reseña de "%s".', $productName),
                            'url_accion'=> '/productos/' . $resena->id_producto,
                            'leida'     => false,
                        ]);

                        try {
                            app(PushNotificationService::class)->sendToUser($storeUserId, [
                                'title' => 'Nueva respuesta en reseña',
                                'body'  => $productName,
                                'url'   => '/productos/' . $resena->id_producto,
                                'icon'  => '/placeholder-store.jpg',
                            ]);
                        } catch (\Throwable $e) { /* ignore */ }
                    }
                }
            } catch (\Throwable $e) { /* ignore */ }

            return response()->json($respuesta, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function update(Request $request, $respuestaId): JsonResponse
    {
        $respuesta = ResenaRespuesta::find($respuestaId);
        if (!$respuesta) {
            return response()->json(['message' => 'Respuesta no encontrada'], 404);
        }

        try {
            $validated = $request->validate([
                'respuesta' => 'required|string|max:1000',
            ]);

            $respuesta->update($validated);
            $respuesta->load('user');

            return response()->json($respuesta);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function destroy($respuestaId): JsonResponse
    {
        $respuesta = ResenaRespuesta::find($respuestaId);
        if (!$respuesta) {
            return response()->json(['message' => 'Respuesta no encontrada'], 404);
        }

        $respuesta->delete();
        return response()->json(['message' => 'Respuesta eliminada correctamente']);
    }
}