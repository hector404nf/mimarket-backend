<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoriaResource;
use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class CategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Categoria::query();

        // Filtros
        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'orden');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Incluir conteo de productos si se solicita
        if ($request->boolean('with_products_count') || $request->boolean('include_product_count')) {
            $query->withCount(['productos' => function ($query) {
                $query->where('activo', true);
            }]);
        }

        $categorias = $query->get();

        return response()->json([
            'success' => true,
            'message' => 'Categorías obtenidas exitosamente',
            'data' => CategoriaResource::collection($categorias)
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'nombre' => 'required|string|max:100|unique:categorias,nombre',
                'descripcion' => 'nullable|string|max:500',
                'description' => 'nullable|string|max:500',
                'icono' => 'nullable|string|max:100',
                'activo' => 'boolean',
                'orden' => 'nullable|integer|min:0'
            ]);

            // Mapear descripcion a description si se envió descripcion
            if (isset($validated['descripcion'])) {
                $validated['description'] = $validated['descripcion'];
                unset($validated['descripcion']);
            }

            // Generar slug automáticamente
            $validated['slug'] = Str::slug($validated['nombre']);
            $validated['fecha_creacion'] = now();

            // Si no se especifica orden, usar el siguiente disponible
            if (!isset($validated['orden'])) {
                $maxOrden = Categoria::max('orden') ?? 0;
                $validated['orden'] = $maxOrden + 1;
            }

            $categoria = Categoria::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Categoría creada exitosamente',
                'data' => new CategoriaResource($categoria)
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
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
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        // Incluir productos si se solicita
        if (request()->boolean('with_products')) {
            $categoria->load('productos');
        }

        return response()->json([
            'success' => true,
            'message' => 'Categoría obtenida exitosamente',
            'data' => new CategoriaResource($categoria)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        try {
            $validated = $request->validate([
                'nombre' => 'sometimes|string|max:100|unique:categorias,nombre,' . $id . ',id_categoria',
                'descripcion' => 'nullable|string|max:500',
                'description' => 'nullable|string|max:500',
                'icono' => 'nullable|string|max:100',
                'activo' => 'boolean',
                'orden' => 'nullable|integer|min:0'
            ]);

            // Mapear descripcion a description si se envió descripcion
            if (isset($validated['descripcion'])) {
                $validated['description'] = $validated['descripcion'];
                unset($validated['descripcion']);
            }

            // Actualizar slug si se cambió el nombre
            if (isset($validated['nombre'])) {
                $validated['slug'] = Str::slug($validated['nombre']);
            }

            $categoria->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Categoría actualizada exitosamente',
                'data' => new CategoriaResource($categoria)
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
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
        $categoria = Categoria::find($id);

        if (!$categoria) {
            return response()->json([
                'success' => false,
                'message' => 'Categoría no encontrada'
            ], 404);
        }

        // Verificar si tiene productos asociados
        if ($categoria->productos()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar la categoría porque tiene productos asociados'
            ], 422);
        }

        $categoria->delete();

        return response()->json([
            'success' => true,
            'message' => 'Categoría eliminada exitosamente'
        ]);
    }
}
