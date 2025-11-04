<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UsoCupon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class UsoCuponController extends Controller
{
    public function index(): JsonResponse
    {
        $usoCupones = UsoCupon::with(['cupon', 'user', 'orden'])->orderBy('created_at', 'desc')->get();
        return response()->json($usoCupones);
    }

    public function show($id): JsonResponse
    {
        $usoCupon = UsoCupon::with(['cupon', 'user', 'orden'])->find($id);
        
        if (!$usoCupon) {
            return response()->json(['message' => 'Uso de cupón no encontrado'], 404);
        }
        
        return response()->json($usoCupon);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id_cupon' => 'required|exists:cupones,id_cupon',
                'user_id' => 'required|exists:users,id',
                'id_orden' => 'required|exists:ordenes,id_orden',
                'descuento_aplicado' => 'required|numeric|min:0'
            ]);

            $usoCupon = UsoCupon::create($validated);
            $usoCupon->load(['cupon', 'user', 'orden']);

            return response()->json($usoCupon, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function destroy($id): JsonResponse
    {
        $usoCupon = UsoCupon::find($id);
        
        if (!$usoCupon) {
            return response()->json(['message' => 'Uso de cupón no encontrado'], 404);
        }

        $usoCupon->delete();
        return response()->json(['message' => 'Uso de cupón eliminado correctamente']);
    }

    public function getByCupon($cuponId): JsonResponse
    {
        $usoCupones = UsoCupon::where('id_cupon', $cuponId)
                            ->with(['user', 'orden'])
                            ->orderBy('created_at', 'desc')
                            ->get();
        
        return response()->json($usoCupones);
    }

    public function getByUser($userId): JsonResponse
    {
        $usoCupones = UsoCupon::where('user_id', $userId)
                            ->with(['cupon', 'orden'])
                            ->orderBy('created_at', 'desc')
                            ->get();
        
        return response()->json($usoCupones);
    }

    public function getByOrden($ordenId): JsonResponse
    {
        $usoCupones = UsoCupon::where('id_orden', $ordenId)
                            ->with(['cupon', 'user'])
                            ->get();
        
        return response()->json($usoCupones);
    }
}