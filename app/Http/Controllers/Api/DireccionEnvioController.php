<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DireccionEnvio;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class DireccionEnvioController extends Controller
{
    public function index(): JsonResponse
    {
        $direcciones = DireccionEnvio::with('user')->get();
        return response()->json($direcciones);
    }

    public function show($id): JsonResponse
    {
        $direccion = DireccionEnvio::with('user')->find($id);
        
        if (!$direccion) {
            return response()->json(['message' => 'Dirección no encontrada'], 404);
        }
        
        return response()->json($direccion);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'nombre_completo' => 'required|string|max:255',
                'direccion' => 'required|string|max:500',
                'ciudad' => 'required|string|max:100',
                'estado' => 'required|string|max:100',
                'codigo_postal' => 'required|string|max:20',
                'pais' => 'required|string|max:100',
                'telefono' => 'nullable|string|max:20',
                'predeterminada' => 'boolean'
            ]);

            // Si se marca como predeterminada, desmarcar las demás del usuario
            if ($validated['predeterminada'] ?? false) {
                DireccionEnvio::where('user_id', $validated['user_id'])
                             ->update(['predeterminada' => false]);
            }

            $direccion = DireccionEnvio::create($validated);
            $direccion->load('user');

            return response()->json($direccion, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $direccion = DireccionEnvio::find($id);
        
        if (!$direccion) {
            return response()->json(['message' => 'Dirección no encontrada'], 404);
        }

        try {
            $validated = $request->validate([
                'nombre_completo' => 'required|string|max:255',
                'direccion' => 'required|string|max:500',
                'ciudad' => 'required|string|max:100',
                'estado' => 'required|string|max:100',
                'codigo_postal' => 'required|string|max:20',
                'pais' => 'required|string|max:100',
                'telefono' => 'nullable|string|max:20',
                'predeterminada' => 'boolean'
            ]);

            // Si se marca como predeterminada, desmarcar las demás del usuario
            if ($validated['predeterminada'] ?? false) {
                DireccionEnvio::where('user_id', $direccion->user_id)
                             ->where('id_direccion', '!=', $id)
                             ->update(['predeterminada' => false]);
            }

            $direccion->update($validated);
            $direccion->load('user');

            return response()->json($direccion);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function destroy($id): JsonResponse
    {
        $direccion = DireccionEnvio::find($id);
        
        if (!$direccion) {
            return response()->json(['message' => 'Dirección no encontrada'], 404);
        }

        $direccion->delete();
        return response()->json(['message' => 'Dirección eliminada correctamente']);
    }

    public function getByUser($userId): JsonResponse
    {
        $direcciones = DireccionEnvio::where('user_id', $userId)
                                   ->orderBy('predeterminada', 'desc')
                                   ->orderBy('created_at', 'desc')
                                   ->get();
        
        return response()->json($direcciones);
    }

    public function setDefault($id): JsonResponse
    {
        $direccion = DireccionEnvio::find($id);
        
        if (!$direccion) {
            return response()->json(['message' => 'Dirección no encontrada'], 404);
        }

        // Desmarcar todas las direcciones del usuario
        DireccionEnvio::where('user_id', $direccion->user_id)
                     ->update(['predeterminada' => false]);

        // Marcar esta como predeterminada
        $direccion->update(['predeterminada' => true]);
        $direccion->load('user');

        return response()->json($direccion);
    }
}