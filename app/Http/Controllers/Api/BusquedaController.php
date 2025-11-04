<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Busqueda;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class BusquedaController extends Controller
{
    public function index(): JsonResponse
    {
        $busquedas = Busqueda::with('user')->orderBy('created_at', 'desc')->get();
        return response()->json($busquedas);
    }

    public function show($id): JsonResponse
    {
        $busqueda = Busqueda::with('user')->find($id);
        
        if (!$busqueda) {
            return response()->json(['message' => 'Búsqueda no encontrada'], 404);
        }
        
        return response()->json($busqueda);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'nullable|exists:users,id',
                'termino_busqueda' => 'required|string|max:255',
                'resultados_encontrados' => 'required|integer|min:0',
                'filtros_aplicados' => 'nullable|array',
                'ip_address' => 'nullable|ip'
            ]);

            $busqueda = Busqueda::create($validated);
            $busqueda->load('user');

            return response()->json($busqueda, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function destroy($id): JsonResponse
    {
        $busqueda = Busqueda::find($id);
        
        if (!$busqueda) {
            return response()->json(['message' => 'Búsqueda no encontrada'], 404);
        }

        $busqueda->delete();
        return response()->json(['message' => 'Búsqueda eliminada correctamente']);
    }

    public function getByUser($userId): JsonResponse
    {
        $busquedas = Busqueda::where('user_id', $userId)
                           ->orderBy('created_at', 'desc')
                           ->get();
        
        return response()->json($busquedas);
    }

    public function getPopularSearches(): JsonResponse
    {
        $busquedas = Busqueda::selectRaw('termino_busqueda, COUNT(*) as total_busquedas')
                           ->groupBy('termino_busqueda')
                           ->orderBy('total_busquedas', 'desc')
                           ->limit(10)
                           ->get();
        
        return response()->json($busquedas);
    }

    public function getRecentSearches($userId): JsonResponse
    {
        $busquedas = Busqueda::where('user_id', $userId)
                           ->orderBy('created_at', 'desc')
                           ->limit(10)
                           ->get(['termino_busqueda', 'created_at']);
        
        return response()->json($busquedas);
    }
}