<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tienda;
use App\Models\HorarioTienda;
use Illuminate\Http\Request;

class HorarioTiendaController extends Controller
{
    public function index($tiendaId)
    {
        $tienda = Tienda::findOrFail($tiendaId);
        if ($tienda->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        $items = HorarioTienda::where('id_tienda', $tiendaId)->orderBy('dia_semana')->get();
        return response()->json(['data' => $items]);
    }

    public function bulkUpdate(Request $request, $tiendaId)
    {
        $tienda = Tienda::findOrFail($tiendaId);
        if ($tienda->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        $payload = $request->validate([
            'horarios' => 'required|array',
            'horarios.*.dia_semana' => 'required|string|max:20',
            'horarios.*.hora_apertura' => 'nullable',
            'horarios.*.hora_cierre' => 'nullable',
            'horarios.*.cerrado' => 'boolean',
            'horarios.*.notas_especiales' => 'nullable|string',
        ]);

        $map = collect($payload['horarios']);
        $map->each(function ($h) use ($tiendaId) {
            HorarioTienda::updateOrCreate(
                ['id_tienda' => $tiendaId, 'dia_semana' => $h['dia_semana']],
                [
                    'hora_apertura' => $h['hora_apertura'] ?? null,
                    'hora_cierre' => $h['hora_cierre'] ?? null,
                    'cerrado' => (bool)($h['cerrado'] ?? false),
                    'notas_especiales' => $h['notas_especiales'] ?? null,
                ]
            );
        });

        $items = HorarioTienda::where('id_tienda', $tiendaId)->orderBy('dia_semana')->get();
        return response()->json(['success' => true, 'data' => $items]);
    }
}