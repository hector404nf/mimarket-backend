<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tienda;
use App\Models\DireccionEnvioTienda;
use Illuminate\Http\Request;

class DireccionEnvioTiendaController extends Controller
{
    public function index($tiendaId)
    {
        $tienda = Tienda::findOrFail($tiendaId);
        if ($tienda->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        $items = DireccionEnvioTienda::where('id_tienda', $tiendaId)->orderBy('nombre')->get();
        return response()->json(['data' => $items]);
    }

    public function bulkReplace(Request $request, $tiendaId)
    {
        $tienda = Tienda::findOrFail($tiendaId);
        if ($tienda->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        $payload = $request->validate([
            'zonas' => 'required|array',
            'zonas.*.nombre' => 'required|string|max:200',
            'zonas.*.precio_envio' => 'required|numeric',
            'zonas.*.minutos_entrega' => 'required|integer',
            'zonas.*.latitud' => 'required|numeric',
            'zonas.*.longitud' => 'required|numeric',
            'zonas.*.direccion_completa' => 'required|string|max:500',
            'zonas.*.zona_cobertura' => 'nullable|string',
            'zonas.*.activo' => 'boolean',
        ]);

        DireccionEnvioTienda::where('id_tienda', $tiendaId)->delete();
        foreach ($payload['zonas'] as $z) {
            DireccionEnvioTienda::create([
                'id_tienda' => $tiendaId,
                'nombre' => $z['nombre'],
                'precio_envio' => $z['precio_envio'],
                'minutos_entrega' => $z['minutos_entrega'],
                'latitud' => $z['latitud'],
                'longitud' => $z['longitud'],
                'direccion_completa' => $z['direccion_completa'],
                'zona_cobertura' => $z['zona_cobertura'] ?? null,
                'activo' => (bool)($z['activo'] ?? true),
            ]);
        }

        $items = DireccionEnvioTienda::where('id_tienda', $tiendaId)->orderBy('nombre')->get();
        return response()->json(['success' => true, 'data' => $items]);
    }
}