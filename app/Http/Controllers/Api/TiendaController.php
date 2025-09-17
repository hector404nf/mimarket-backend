<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tienda;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class TiendaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tienda::with(['usuario']);

        // Filtros
        if ($request->has('usuario')) {
            $query->where('id_usuario', $request->usuario);
        }

        if ($request->has('verificada')) {
            $query->where('verificada', $request->boolean('verificada'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre_tienda', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%")
                  ->orWhere('email_contacto', 'like', "%{$search}%");
            });
        }

        // Filtro por calificación mínima
        if ($request->has('min_rating')) {
            $query->where('calificacion_promedio', '>=', $request->min_rating);
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'fecha_creacion');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Incluir conteo de productos si se solicita
        if ($request->boolean('with_products_count')) {
            $query->withCount('productos');
        }

        // Paginación
        $perPage = $request->get('per_page', 15);
        $tiendas = $query->paginate($perPage);

        return response()->json($tiendas);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id_usuario' => 'required|exists:usuarios,id_usuario',
                'nombre_tienda' => 'required|string|max:255|unique:tiendas,nombre_tienda',
                'descripcion' => 'nullable|string|max:1000',
                'logo' => 'nullable|string|max:500',
                'banner' => 'nullable|string|max:500',
                'direccion' => 'nullable|string|max:500',
                'telefono_contacto' => 'nullable|string|max:20',
                'email_contacto' => 'nullable|email|max:255',
                'verificada' => 'boolean'
            ]);

            $validated['fecha_creacion'] = now();
            $validated['fecha_actualizacion'] = now();
            $validated['calificacion_promedio'] = 0;
            $validated['total_productos'] = 0;

            $tienda = Tienda::create($validated);
            $tienda->load(['usuario']);

            return response()->json([
                'message' => 'Tienda creada exitosamente',
                'data' => $tienda
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $tienda = Tienda::with(['usuario'])->find($id);

        if (!$tienda) {
            return response()->json([
                'message' => 'Tienda no encontrada'
            ], 404);
        }

        // Incluir productos si se solicita
        if (request()->boolean('with_products')) {
            $tienda->load(['productos' => function($query) {
                $query->where('activo', true)
                      ->orderBy('fecha_creacion', 'desc')
                      ->limit(20);
            }]);
        }

        return response()->json([
            'data' => $tienda
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $tienda = Tienda::find($id);

        if (!$tienda) {
            return response()->json([
                'message' => 'Tienda no encontrada'
            ], 404);
        }

        try {
            $validated = $request->validate([
                'nombre_tienda' => 'sometimes|string|max:255|unique:tiendas,nombre_tienda,' . $id . ',id_tienda',
                'descripcion' => 'nullable|string|max:1000',
                'logo' => 'nullable|string|max:500',
                'banner' => 'nullable|string|max:500',
                'direccion' => 'nullable|string|max:500',
                'telefono_contacto' => 'nullable|string|max:20',
                'email_contacto' => 'nullable|email|max:255',
                'verificada' => 'boolean'
            ]);

            $validated['fecha_actualizacion'] = now();
            $tienda->update($validated);
            $tienda->load(['usuario']);

            return response()->json([
                'message' => 'Tienda actualizada exitosamente',
                'data' => $tienda
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $tienda = Tienda::find($id);

        if (!$tienda) {
            return response()->json([
                'message' => 'Tienda no encontrada'
            ], 404);
        }

        // Verificar si tiene productos asociados
        if ($tienda->productos()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar la tienda porque tiene productos asociados'
            ], 422);
        }

        $tienda->delete();

        return response()->json([
            'message' => 'Tienda eliminada exitosamente'
        ]);
    }
}
