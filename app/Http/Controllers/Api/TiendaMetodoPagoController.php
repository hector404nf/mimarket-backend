<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tienda;
use App\Models\TiendaMetodoPago;
use Illuminate\Http\Request;

class TiendaMetodoPagoController extends Controller
{
    public function index($tiendaId)
    {
        $tienda = Tienda::findOrFail($tiendaId);
        if ($tienda->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        $items = TiendaMetodoPago::where('id_tienda', $tiendaId)->orderBy('metodo')->get();
        return response()->json(['data' => $items]);
    }

    public function setAccepted(Request $request, $tiendaId)
    {
        $tienda = Tienda::findOrFail($tiendaId);
        if ($tienda->user_id !== auth()->id()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        $payload = $request->validate([
            'metodos' => 'required|array',
            'metodos.*' => 'required|string|max:50',
        ]);

        $existing = TiendaMetodoPago::where('id_tienda', $tiendaId)->get()->keyBy('metodo');
        foreach ($payload['metodos'] as $m) {
            if ($existing->has($m)) {
                $item = $existing->get($m);
                $item->activo = true;
                $item->save();
            } else {
                TiendaMetodoPago::create([
                    'id_tienda' => $tiendaId,
                    'metodo' => $m,
                    'activo' => true,
                ]);
            }
        }
        foreach ($existing as $key => $row) {
            if (!in_array($key, $payload['metodos'])) {
                $row->activo = false;
                $row->save();
            }
        }

        $items = TiendaMetodoPago::where('id_tienda', $tiendaId)->orderBy('metodo')->get();
        return response()->json(['success' => true, 'data' => $items]);
    }
}