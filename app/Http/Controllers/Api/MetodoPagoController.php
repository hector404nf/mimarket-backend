<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MetodoPago;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class MetodoPagoController extends Controller
{
    public function index(): JsonResponse
    {
        $metodos = MetodoPago::with('user')->get();
        return response()->json($metodos);
    }

    public function show($id): JsonResponse
    {
        $metodo = MetodoPago::with('user')->find($id);
        if (!$metodo) {
            return response()->json(['message' => 'Método de pago no encontrado'], 404);
        }
        return response()->json($metodo);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'tipo' => 'required|string|in:tarjeta,efectivo,transferencia',
                'marca' => 'nullable|string|max:50',
                'terminacion' => 'nullable|string|size:4',
                'nombre_titular' => 'nullable|string|max:255',
                'mes_venc' => 'nullable|integer|min:1|max:12',
                'anio_venc' => 'nullable|integer|min:2000|max:2100',
                'banco' => 'nullable|string|max:100',
                'predeterminada' => 'boolean',
                'activo' => 'boolean',
                'metadata' => 'nullable|array',
            ]);

            // Reglas condicionales
            if ($validated['tipo'] === 'tarjeta') {
                foreach (['marca', 'terminacion', 'nombre_titular', 'mes_venc', 'anio_venc'] as $campo) {
                    if (empty($validated[$campo])) {
                        throw ValidationException::withMessages([
                            $campo => ["El campo $campo es requerido para tarjetas"]
                        ]);
                    }
                }
            }

            if ($validated['tipo'] === 'transferencia') {
                foreach (['banco', 'nombre_titular'] as $campo) {
                    if (empty($validated[$campo])) {
                        throw ValidationException::withMessages([
                            $campo => ["El campo $campo es requerido para transferencias"]
                        ]);
                    }
                }
            }

            // Unicidad predeterminada por usuario
            if ($validated['predeterminada'] ?? false) {
                MetodoPago::where('user_id', $validated['user_id'])->update(['predeterminada' => false]);
            }

            $metodo = MetodoPago::create($validated);
            $metodo->load('user');
            return response()->json($metodo, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $metodo = MetodoPago::find($id);
        if (!$metodo) {
            return response()->json(['message' => 'Método de pago no encontrado'], 404);
        }

        try {
            $validated = $request->validate([
                'tipo' => 'nullable|string|in:tarjeta,efectivo,transferencia',
                'marca' => 'nullable|string|max:50',
                'terminacion' => 'nullable|string|size:4',
                'nombre_titular' => 'nullable|string|max:255',
                'mes_venc' => 'nullable|integer|min:1|max:12',
                'anio_venc' => 'nullable|integer|min:2000|max:2100',
                'banco' => 'nullable|string|max:100',
                'predeterminada' => 'boolean',
                'activo' => 'boolean',
                'metadata' => 'nullable|array',
            ]);

            $tipo = $validated['tipo'] ?? $metodo->tipo;
            if ($tipo === 'tarjeta') {
                foreach (['marca', 'terminacion'] as $campo) {
                    if (array_key_exists($campo, $validated) && empty($validated[$campo])) {
                        throw ValidationException::withMessages([
                            $campo => ["El campo $campo es requerido para tarjetas"]
                        ]);
                    }
                }
            }

            if (($validated['predeterminada'] ?? false) === true) {
                MetodoPago::where('user_id', $metodo->user_id)->where('id_metodo', '!=', $id)->update(['predeterminada' => false]);
            }

            $metodo->update($validated);
            $metodo->load('user');
            return response()->json($metodo);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function destroy($id): JsonResponse
    {
        $metodo = MetodoPago::find($id);
        if (!$metodo) {
            return response()->json(['message' => 'Método de pago no encontrado'], 404);
        }

        $metodo->delete();
        return response()->json(['message' => 'Método de pago eliminado correctamente']);
    }

    public function getByUser($userId): JsonResponse
    {
        $metodos = MetodoPago::where('user_id', $userId)
            ->orderBy('predeterminada', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($metodos);
    }

    public function setDefault($id): JsonResponse
    {
        $metodo = MetodoPago::find($id);
        if (!$metodo) {
            return response()->json(['message' => 'Método de pago no encontrado'], 404);
        }

        MetodoPago::where('user_id', $metodo->user_id)->update(['predeterminada' => false]);
        $metodo->update(['predeterminada' => true]);
        $metodo->load('user');

        return response()->json($metodo);
    }
}