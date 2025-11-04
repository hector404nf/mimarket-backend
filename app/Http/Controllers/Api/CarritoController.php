<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Carrito;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class CarritoController extends Controller
{
    public function index(): JsonResponse
    {
        $carrito = Carrito::with(['user', 'producto'])->get();
        return response()->json($carrito);
    }

    public function show($id): JsonResponse
    {
        $carrito = Carrito::with(['user', 'producto'])->find($id);
        
        if (!$carrito) {
            return response()->json(['message' => 'Item del carrito no encontrado'], 404);
        }
        
        return response()->json($carrito);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'id_producto' => 'required|exists:productos,id_producto',
                'cantidad' => 'required|integer|min:1',
                'precio_unitario' => 'required|numeric|min:0'
            ]);

            $carrito = Carrito::create($validated);
            $carrito->load(['user', 'producto']);

            return response()->json($carrito, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $carrito = Carrito::find($id);
        
        if (!$carrito) {
            return response()->json(['message' => 'Item del carrito no encontrado'], 404);
        }

        try {
            $validated = $request->validate([
                'cantidad' => 'required|integer|min:1',
                'precio_unitario' => 'required|numeric|min:0'
            ]);

            $carrito->update($validated);
            $carrito->load(['user', 'producto']);

            return response()->json($carrito);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function destroy($id): JsonResponse
    {
        $carrito = Carrito::find($id);
        
        if (!$carrito) {
            return response()->json(['message' => 'Item del carrito no encontrado'], 404);
        }

        $carrito->delete();
        return response()->json(['message' => 'Item eliminado del carrito']);
    }

    public function getByUser($userId): JsonResponse
    {
        $carrito = Carrito::where('user_id', $userId)
                         ->with(['producto'])
                         ->get();
        
        return response()->json($carrito);
    }

    public function clearCart($userId): JsonResponse
    {
        Carrito::where('user_id', $userId)->delete();
        return response()->json(['message' => 'Carrito vaciado correctamente']);
    }
}