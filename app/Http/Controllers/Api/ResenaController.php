<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resena;
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
}