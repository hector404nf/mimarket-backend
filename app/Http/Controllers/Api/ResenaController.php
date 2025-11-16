<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resena;
use App\Models\Producto;
use App\Models\Notificacion;
use App\Services\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ResenaController extends Controller
{
    public function index(): JsonResponse
    {
        $resenas = Resena::with(['user', 'producto'])->orderBy('created_at', 'desc')->get();
        return response()->json($resenas);
    }

    public function show($id): JsonResponse
    {
        $resena = Resena::with(['user', 'producto'])->find($id);
        
        if (!$resena) {
            return response()->json(['message' => 'Reseña no encontrada'], 404);
        }
        
        return response()->json($resena);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id_producto' => 'required|exists:productos,id_producto',
                'user_id' => 'required|exists:users,id',
                'calificacion' => 'required|integer|min:1|max:5',
                'comentario' => 'nullable|string|max:1000',
                'verificada' => 'boolean'
            ]);

            // Verificar que el usuario no haya reseñado ya este producto
            $existingResena = Resena::where('id_producto', $validated['id_producto'])
                                  ->where('user_id', $validated['user_id'])
                                  ->first();

            if ($existingResena) {
                return response()->json(['message' => 'Ya has reseñado este producto'], 400);
            }

            $resena = Resena::create($validated);
            $resena->load(['user', 'producto']);

            // Notificar a la tienda propietaria del producto
            try {
                $producto = Producto::with('tienda.user')->find($validated['id_producto']);
                if ($producto && $producto->tienda && $producto->tienda->user) {
                    $storeUserId = $producto->tienda->user->id;

                    // Crear notificación en BD
                    Notificacion::create([
                        'user_id'   => $storeUserId,
                        'tipo'      => 'nueva_resena',
                        'titulo'    => 'Nueva reseña en tu producto',
                        'mensaje'   => sprintf(
                            'El usuario %s calificó "%s" con %d estrellas%s',
                            ($resena->user->name ?? 'Cliente'),
                            ($producto->nombre ?? 'Producto'),
                            (int)$resena->calificacion,
                            $resena->comentario ? (': "' . (strlen($resena->comentario) > 120 ? substr($resena->comentario, 0, 117) . '…' : $resena->comentario) . '"') : ''
                        ),
                        'url_accion' => '/productos/' . $producto->id_producto,
                        'leida'     => false,
                    ]);

                    // Enviar push al propietario de la tienda
                    try {
                        app(PushNotificationService::class)->sendToUser($storeUserId, [
                            'title' => 'Nueva reseña recibida',
                            'body'  => ($producto->nombre ?? 'Producto') . ' ahora tiene una nueva reseña',
                            'url'   => '/productos/' . $producto->id_producto,
                            'icon'  => '/placeholder-logo.png',
                        ]);
                    } catch (\Throwable $e) { /* log opcional */ }
                }
            } catch (\Throwable $e) { /* log opcional */ }

            // Notificar al cliente que su reseña fue publicada
            try {
                $clientUserId = $resena->user_id;
                $productName = $resena->producto->nombre ?? 'Producto';

                Notificacion::create([
                    'user_id'   => $clientUserId,
                    'tipo'      => 'resena_publicada',
                    'titulo'    => 'Tu reseña fue publicada',
                    'mensaje'   => sprintf('Publicaste una reseña de "%s" con %d estrellas.', $productName, (int)$resena->calificacion),
                    'url_accion'=> '/productos/' . $resena->id_producto,
                    'leida'     => false,
                ]);

                try {
                    app(PushNotificationService::class)->sendToUser($clientUserId, [
                        'title' => 'Reseña publicada',
                        'body'  => $productName,
                        'url'   => '/productos/' . $resena->id_producto,
                        'icon'  => '/placeholder-user.jpg',
                    ]);
                } catch (\Throwable $e) { /* ignore */ }
            } catch (\Throwable $e) { /* ignore */ }

            return response()->json($resena, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $resena = Resena::find($id);
        
        if (!$resena) {
            return response()->json(['message' => 'Reseña no encontrada'], 404);
        }

        try {
            $validated = $request->validate([
                'calificacion' => 'required|integer|min:1|max:5',
                'comentario' => 'nullable|string|max:1000',
                'verificada' => 'boolean'
            ]);

            $resena->update($validated);
            $resena->load(['user', 'producto']);

            return response()->json($resena);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function destroy($id): JsonResponse
    {
        $resena = Resena::find($id);
        
        if (!$resena) {
            return response()->json(['message' => 'Reseña no encontrada'], 404);
        }

        $resena->delete();
        return response()->json(['message' => 'Reseña eliminada correctamente']);
    }

    public function getByProducto($productoId): JsonResponse
    {
        $resenas = Resena::where('id_producto', $productoId)
                        ->with(['user'])
                        ->orderBy('created_at', 'desc')
                        ->get();
        
        return response()->json($resenas);
    }

    public function getByUser($userId): JsonResponse
    {
        $resenas = Resena::where('user_id', $userId)
                        ->with(['producto'])
                        ->orderBy('created_at', 'desc')
                        ->get();
        
        return response()->json($resenas);
    }

    public function getProductoStats($productoId): JsonResponse
    {
        $stats = Resena::where('id_producto', $productoId)
                      ->selectRaw('
                          COUNT(*) as total_resenas,
                          AVG(calificacion) as promedio_calificacion,
                          COUNT(CASE WHEN calificacion = 5 THEN 1 END) as cinco_estrellas,
                          COUNT(CASE WHEN calificacion = 4 THEN 1 END) as cuatro_estrellas,
                          COUNT(CASE WHEN calificacion = 3 THEN 1 END) as tres_estrellas,
                          COUNT(CASE WHEN calificacion = 2 THEN 1 END) as dos_estrellas,
                          COUNT(CASE WHEN calificacion = 1 THEN 1 END) as una_estrella
                      ')
                      ->first();
        
        return response()->json($stats);
    }

    public function verify($id): JsonResponse
    {
        $resena = Resena::find($id);
        
        if (!$resena) {
            return response()->json(['message' => 'Reseña no encontrada'], 404);
        }

        $resena->update(['verificada' => true]);
        $resena->load(['user', 'producto']);

        return response()->json($resena);
    }

    public function getByTienda($tiendaId): JsonResponse
    {
        $resenas = Resena::whereHas('producto', function ($q) use ($tiendaId) {
                            $q->where('id_tienda', $tiendaId);
                        })
                        ->with(['user', 'producto'])
                        ->orderBy('created_at', 'desc')
                        ->get();

        return response()->json($resenas);
    }

    public function getTiendaStats($tiendaId): JsonResponse
    {
        $stats = Resena::whereHas('producto', function ($q) use ($tiendaId) {
                            $q->where('id_tienda', $tiendaId);
                        })
                        ->selectRaw('
                            COUNT(*) as total_resenas,
                            AVG(calificacion) as promedio_calificacion,
                            COUNT(CASE WHEN calificacion = 5 THEN 1 END) as cinco_estrellas,
                            COUNT(CASE WHEN calificacion = 4 THEN 1 END) as cuatro_estrellas,
                            COUNT(CASE WHEN calificacion = 3 THEN 1 END) as tres_estrellas,
                            COUNT(CASE WHEN calificacion = 2 THEN 1 END) as dos_estrellas,
                            COUNT(CASE WHEN calificacion = 1 THEN 1 END) as una_estrella
                        ')
                        ->first();

        return response()->json($stats);
    }
}