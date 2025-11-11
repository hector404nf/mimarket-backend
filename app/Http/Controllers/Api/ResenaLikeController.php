<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ResenaLike;
use App\Models\Resena;
use App\Models\Notificacion;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ResenaLikeController extends Controller
{
    public function getByResena($resenaId): JsonResponse
    {
        $likes = ResenaLike::where('id_resena', $resenaId)->with('user')->orderBy('created_at', 'desc')->get();
        return response()->json($likes);
    }

    public function check($userId, $resenaId): JsonResponse
    {
        $exists = ResenaLike::where('user_id', $userId)->where('id_resena', $resenaId)->exists();
        return response()->json(['liked' => $exists]);
    }

    public function toggle(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'id_resena' => 'required|exists:resenas,id_resena',
            ]);

            $existing = ResenaLike::where('user_id', $validated['user_id'])
                                  ->where('id_resena', $validated['id_resena'])
                                  ->first();

            if ($existing) {
                $existing->delete();
                return response()->json(['message' => 'Like removido', 'action' => 'removed']);
            }

            $like = ResenaLike::create($validated);

            // Notificar al autor de la reseña
            try {
                $resena = Resena::with(['user', 'producto.tienda'])->find($validated['id_resena']);
                if ($resena) {
                    $authorId = $resena->user_id;
                    $productName = $resena->producto->nombre ?? 'Producto';

                    Notificacion::create([
                        'user_id'   => $authorId,
                        'tipo'      => 'resena_like',
                        'titulo'    => 'Tu reseña recibió un me gusta',
                        'mensaje'   => sprintf('Tu reseña de "%s" recibió un me gusta.', $productName),
                        'url_accion'=> '/productos/' . $resena->id_producto,
                        'leida'     => false,
                    ]);

                    // Push al autor
                    try {
                        app(PushNotificationService::class)->sendToUser($authorId, [
                            'title' => 'Tu reseña recibió un me gusta',
                            'body'  => $productName,
                            'url'   => '/productos/' . $resena->id_producto,
                            'icon'  => '/placeholder-user.jpg',
                        ]);
                    } catch (\Throwable $e) { /* ignore */ }

                    // Notificar a la tienda propietaria del producto
                    try {
                        if ($resena->producto && $resena->producto->tienda && $resena->producto->tienda->user) {
                            $storeUserId = $resena->producto->tienda->user->id;

                            Notificacion::create([
                                'user_id'   => $storeUserId,
                                'tipo'      => 'resena_like',
                                'titulo'    => 'Una reseña recibió un me gusta',
                                'mensaje'   => sprintf('La reseña de "%s" recibió un me gusta.', $productName),
                                'url_accion'=> '/productos/' . $resena->id_producto,
                                'leida'     => false,
                            ]);

                            try {
                                app(PushNotificationService::class)->sendToUser($storeUserId, [
                                    'title' => 'Reseña con me gusta',
                                    'body'  => $productName,
                                    'url'   => '/productos/' . $resena->id_producto,
                                    'icon'  => '/placeholder-store.jpg',
                                ]);
                            } catch (\Throwable $e) { /* ignore */ }
                        }
                    } catch (\Throwable $e) { /* ignore */ }
                }
            } catch (\Throwable $e) { /* ignore */ }

            return response()->json(['message' => 'Like agregado', 'action' => 'added']);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }
}