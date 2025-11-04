<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cupon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class CuponController extends Controller
{
    public function index(): JsonResponse
    {
        $cupones = Cupon::with('usoCupones')->orderBy('created_at', 'desc')->get();
        return response()->json($cupones);
    }

    public function show($id): JsonResponse
    {
        $cupon = Cupon::with('usoCupones.user')->find($id);
        
        if (!$cupon) {
            return response()->json(['message' => 'Cupón no encontrado'], 404);
        }
        
        return response()->json($cupon);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'codigo' => 'required|string|unique:cupones,codigo|max:50',
                'tipo' => 'required|in:porcentaje,monto_fijo',
                'valor' => 'required|numeric|min:0',
                'monto_minimo' => 'nullable|numeric|min:0',
                'limite_uso' => 'nullable|integer|min:1',
                'fecha_inicio' => 'required|date',
                'fecha_expiracion' => 'required|date|after:fecha_inicio',
                'activo' => 'boolean',
                'descripcion' => 'nullable|string|max:500'
            ]);

            $cupon = Cupon::create($validated);

            return response()->json($cupon, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $cupon = Cupon::find($id);
        
        if (!$cupon) {
            return response()->json(['message' => 'Cupón no encontrado'], 404);
        }

        try {
            $validated = $request->validate([
                'codigo' => 'required|string|max:50|unique:cupones,codigo,' . $id . ',id_cupon',
                'tipo' => 'required|in:porcentaje,monto_fijo',
                'valor' => 'required|numeric|min:0',
                'monto_minimo' => 'nullable|numeric|min:0',
                'limite_uso' => 'nullable|integer|min:1',
                'fecha_inicio' => 'required|date',
                'fecha_expiracion' => 'required|date|after:fecha_inicio',
                'activo' => 'boolean',
                'descripcion' => 'nullable|string|max:500'
            ]);

            $cupon->update($validated);

            return response()->json($cupon);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function destroy($id): JsonResponse
    {
        $cupon = Cupon::find($id);
        
        if (!$cupon) {
            return response()->json(['message' => 'Cupón no encontrado'], 404);
        }

        $cupon->delete();
        return response()->json(['message' => 'Cupón eliminado correctamente']);
    }

    public function validateCoupon(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'codigo' => 'required|string',
                'monto_orden' => 'required|numeric|min:0',
                'user_id' => 'nullable|exists:users,id'
            ]);

            $cupon = Cupon::where('codigo', $validated['codigo'])->first();

            if (!$cupon) {
                return response()->json(['message' => 'Cupón no válido'], 404);
            }

            // Verificar si está activo
            if (!$cupon->activo) {
                return response()->json(['message' => 'Cupón inactivo'], 400);
            }

            // Verificar fechas
            $now = Carbon::now();
            if ($now->lt($cupon->fecha_inicio) || $now->gt($cupon->fecha_expiracion)) {
                return response()->json(['message' => 'Cupón expirado o no válido aún'], 400);
            }

            // Verificar monto mínimo
            if ($cupon->monto_minimo && $validated['monto_orden'] < $cupon->monto_minimo) {
                return response()->json(['message' => 'Monto mínimo no alcanzado'], 400);
            }

            // Verificar límite de uso
            if ($cupon->limite_uso) {
                $usos = $cupon->usoCupones()->count();
                if ($usos >= $cupon->limite_uso) {
                    return response()->json(['message' => 'Cupón agotado'], 400);
                }
            }

            // Calcular descuento
            $descuento = 0;
            if ($cupon->tipo === 'porcentaje') {
                $descuento = ($validated['monto_orden'] * $cupon->valor) / 100;
            } else {
                $descuento = $cupon->valor;
            }

            return response()->json([
                'valid' => true,
                'cupon' => $cupon,
                'descuento' => $descuento,
                'monto_final' => $validated['monto_orden'] - $descuento
            ]);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function getActiveCoupons(): JsonResponse
    {
        $now = Carbon::now();
        $cupones = Cupon::where('activo', true)
                       ->where('fecha_inicio', '<=', $now)
                       ->where('fecha_expiracion', '>=', $now)
                       ->get();
        
        return response()->json($cupones);
    }

    public function toggleStatus($id): JsonResponse
    {
        $cupon = Cupon::find($id);
        
        if (!$cupon) {
            return response()->json(['message' => 'Cupón no encontrado'], 404);
        }

        $cupon->update(['activo' => !$cupon->activo]);

        return response()->json($cupon);
    }

    public function getUsageStats($id): JsonResponse
    {
        $cupon = Cupon::with('usoCupones.user')->find($id);
        
        if (!$cupon) {
            return response()->json(['message' => 'Cupón no encontrado'], 404);
        }

        $stats = [
            'total_usos' => $cupon->usoCupones->count(),
            'limite_uso' => $cupon->limite_uso,
            'usos_restantes' => $cupon->limite_uso ? $cupon->limite_uso - $cupon->usoCupones->count() : null,
            'total_descuento_aplicado' => $cupon->usoCupones->sum('descuento_aplicado'),
            'usuarios_unicos' => $cupon->usoCupones->unique('user_id')->count()
        ];

        return response()->json($stats);
    }
}