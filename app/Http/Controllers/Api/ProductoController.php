<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Producto::with(['categoria', 'tienda', 'usuario']);

        // Filtros
        if ($request->has('categoria')) {
            $query->where('id_categoria', $request->categoria);
        }

        if ($request->has('tienda')) {
            $query->where('id_tienda', $request->tienda);
        }

        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->has('destacado')) {
            $query->where('destacado', $request->boolean('destacado'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'fecha_creacion');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginación
        $perPage = $request->get('per_page', 15);
        $productos = $query->paginate($perPage);

        return response()->json($productos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'id_tienda' => 'required|exists:tiendas,id_tienda',
                'id_usuario' => 'required|exists:usuarios,id_usuario',
                'id_categoria' => 'required|exists:categorias,id_categoria',
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'precio' => 'required|numeric|min:0',
                'precio_oferta' => 'nullable|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'sku' => 'nullable|string|max:100|unique:productos,sku',
                'peso' => 'nullable|numeric|min:0',
                'dimensiones' => 'nullable|string|max:100',
                'imagen_principal' => 'nullable|string|max:500',
                'imagenes_adicionales' => 'nullable|array',
                'activo' => 'boolean',
                'destacado' => 'boolean'
            ]);

            $validated['fecha_creacion'] = now();
            $validated['fecha_actualizacion'] = now();

            $producto = Producto::create($validated);
            $producto->load(['categoria', 'tienda', 'usuario']);

            return response()->json([
                'message' => 'Producto creado exitosamente',
                'data' => $producto
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
        $producto = Producto::with(['categoria', 'tienda', 'usuario'])->find($id);

        if (!$producto) {
            return response()->json([
                'message' => 'Producto no encontrado'
            ], 404);
        }

        return response()->json([
            'data' => $producto
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'message' => 'Producto no encontrado'
            ], 404);
        }

        try {
            $validated = $request->validate([
                'id_tienda' => 'sometimes|exists:tiendas,id_tienda',
                'id_usuario' => 'sometimes|exists:usuarios,id_usuario',
                'id_categoria' => 'sometimes|exists:categorias,id_categoria',
                'nombre' => 'sometimes|string|max:255',
                'descripcion' => 'nullable|string',
                'precio' => 'sometimes|numeric|min:0',
                'precio_oferta' => 'nullable|numeric|min:0',
                'stock' => 'sometimes|integer|min:0',
                'sku' => 'sometimes|string|max:100|unique:productos,sku,' . $id . ',id_producto',
                'peso' => 'nullable|numeric|min:0',
                'dimensiones' => 'nullable|string|max:100',
                'imagen_principal' => 'nullable|string|max:500',
                'imagenes_adicionales' => 'nullable|array',
                'activo' => 'boolean',
                'destacado' => 'boolean'
            ]);

            $validated['fecha_actualizacion'] = now();
            $producto->update($validated);
            $producto->load(['categoria', 'tienda', 'usuario']);

            return response()->json([
                'message' => 'Producto actualizado exitosamente',
                'data' => $producto
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
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'message' => 'Producto no encontrado'
            ], 404);
        }

        $producto->delete();

        return response()->json([
            'message' => 'Producto eliminado exitosamente'
        ]);
    }
}
