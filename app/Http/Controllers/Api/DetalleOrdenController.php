<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetalleOrden;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class DetalleOrdenController extends Controller
{
    public function index(): JsonResponse
    {
        $detalles = DetalleOrden::with(['orden', 'producto'])->get();
        return response()->json($detalles);
    }

    public function show($id): JsonResponse
    {
        $detalle = DetalleOrden::with(['orden', 'producto'])->find($id);
        
        if (!$detalle) {
            return response()->json(['message' => 'Detalle de orden no encontrado'], 404);
        }
        
        return response()->json($detalle);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id_orden' => 'required|exists:ordenes,id_orden',
                'id_producto' => 'required|exists:productos,id_producto',
                'cantidad' => 'required|integer|min:1',
                'precio_unitario' => 'required|numeric|min:0',
                'subtotal' => 'required|numeric|min:0'
            ]);

            $detalle = DetalleOrden::create($validated);
            $detalle->load(['orden', 'producto']);

            return response()->json($detalle, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $detalle = DetalleOrden::find($id);
        
        if (!$detalle) {
            return response()->json(['message' => 'Detalle de orden no encontrado'], 404);
        }

        try {
            $validated = $request->validate([
                'cantidad' => 'required|integer|min:1',
                'precio_unitario' => 'required|numeric|min:0',
                'subtotal' => 'required|numeric|min:0'
            ]);

            $detalle->update($validated);
            $detalle->load(['orden', 'producto']);

            return response()->json($detalle);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function destroy($id): JsonResponse
    {
        $detalle = DetalleOrden::find($id);
        
        if (!$detalle) {
            return response()->json(['message' => 'Detalle de orden no encontrado'], 404);
        }

        $detalle->delete();
        return response()->json(['message' => 'Detalle de orden eliminado correctamente']);
    }

    public function getByOrden($ordenId): JsonResponse
    {
        $detalles = DetalleOrden::where('id_orden', $ordenId)
                              ->with(['producto'])
                              ->get();
        
        return response()->json($detalles);
    }
}