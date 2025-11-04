<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorito;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class FavoritoController extends Controller
{
    public function index(): JsonResponse
    {
        $favoritos = Favorito::with(['user', 'producto'])->get();
        return response()->json($favoritos);
    }

    public function show($id): JsonResponse
    {
        $favorito = Favorito::with(['user', 'producto'])->find($id);
        
        if (!$favorito) {
            return response()->json(['message' => 'Favorito no encontrado'], 404);
        }
        
        return response()->json($favorito);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'id_producto' => 'required|exists:productos,id_producto'
            ]);

            // Verificar que no exista ya este favorito
            $existingFavorito = Favorito::where('user_id', $validated['user_id'])
                                      ->where('id_producto', $validated['id_producto'])
                                      ->first();

            if ($existingFavorito) {
                return response()->json(['message' => 'Este producto ya está en favoritos'], 400);
            }

            $favorito = Favorito::create($validated);
            $favorito->load(['user', 'producto']);

            return response()->json($favorito, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function destroy($id): JsonResponse
    {
        $favorito = Favorito::find($id);
        
        if (!$favorito) {
            return response()->json(['message' => 'Favorito no encontrado'], 404);
        }

        $favorito->delete();
        return response()->json(['message' => 'Producto eliminado de favoritos']);
    }

    public function getByUser($userId): JsonResponse
    {
        $favoritos = Favorito::where('user_id', $userId)
                           ->with(['producto'])
                           ->orderBy('created_at', 'desc')
                           ->get();
        
        return response()->json($favoritos);
    }

    public function toggle(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'id_producto' => 'required|exists:productos,id_producto'
            ]);

            $favorito = Favorito::where('user_id', $validated['user_id'])
                              ->where('id_producto', $validated['id_producto'])
                              ->first();

            if ($favorito) {
                // Si existe, lo eliminamos
                $favorito->delete();
                return response()->json(['message' => 'Producto eliminado de favoritos', 'action' => 'removed']);
            } else {
                // Si no existe, lo creamos
                $favorito = Favorito::create($validated);
                $favorito->load(['user', 'producto']);
                return response()->json(['message' => 'Producto agregado a favoritos', 'action' => 'added', 'favorito' => $favorito]);
            }
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function removeByProduct(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'id_producto' => 'required|exists:productos,id_producto'
            ]);

            $favorito = Favorito::where('user_id', $validated['user_id'])
                              ->where('id_producto', $validated['id_producto'])
                              ->first();

            if (!$favorito) {
                return response()->json(['message' => 'Este producto no está en favoritos'], 404);
            }

            $favorito->delete();
            return response()->json(['message' => 'Producto eliminado de favoritos']);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function checkFavorite($userId, $productoId): JsonResponse
    {
        $favorito = Favorito::where('user_id', $userId)
                          ->where('id_producto', $productoId)
                          ->exists();
        
        return response()->json(['is_favorite' => $favorito]);
    }
}