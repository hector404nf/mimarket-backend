<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductoResource;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Producto::with(['categoria', 'tienda', 'usuario']);

        // Filtros
        if ($request->has('categoria')) {
            $query->where('id_categoria', $request->categoria);
        }

        if ($request->has('tienda')) {
            $query->where('id_tienda', $request->tienda);
        }

        if ($request->has('precio_min')) {
            $query->where('precio', '>=', $request->precio_min);
        }

        if ($request->has('precio_max')) {
            $query->where('precio', '<=', $request->precio_max);
        }

        if ($request->has('buscar')) {
            $buscar = $request->buscar;
            $query->where(function($q) use ($buscar) {
                $q->where('nombre', 'LIKE', "%{$buscar}%")
                  ->orWhere('descripcion', 'LIKE', "%{$buscar}%");
            });
        }

        if ($request->has('destacado')) {
            $query->where('destacado', $request->boolean('destacado'));
        }

        // Solo productos activos
        $query->where('activo', true);

        // Ordenamiento
        $ordenar = $request->get('ordenar', 'created_at');
        $direccion = $request->get('direccion', 'desc');
        
        if (in_array($ordenar, ['nombre', 'precio', 'created_at'])) {
            $query->orderBy($ordenar, $direccion);
        }

        // Paginación
        $perPage = min($request->get('per_page', 15), 50);
        $productos = $query->paginate($perPage);

        return ProductoResource::collection($productos);
    }

    /**
     * Get products by store (tienda) with frontend-compatible filters.
     */
    public function getByTienda(string $tiendaId, Request $request)
    {
        // Base query: products for the given store, active
        $query = Producto::with(['categoria', 'tienda', 'usuario'])
            ->where('id_tienda', $tiendaId)
            ->where('activo', true);

        // Buscar/search filter (support both 'search' and 'buscar')
        $search = $request->get('search', $request->get('buscar'));
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'LIKE', "%{$search}%")
                  ->orWhere('descripcion', 'LIKE', "%{$search}%");
            });
        }

        // Sorting compatibility: support 'sort_by'/'sort_order' and map known fields
        $sortBy = $request->get('sort_by');
        $ordenar = $request->get('ordenar', 'created_at');
        $mappedSortBy = $ordenar;

        if (!empty($sortBy)) {
            // Map frontend sort fields to DB columns
            switch ($sortBy) {
                case 'nombre':
                    $mappedSortBy = 'nombre';
                    break;
                case 'precio':
                    $mappedSortBy = 'precio';
                    break;
                case 'fecha_creacion':
                    $mappedSortBy = 'created_at';
                    break;
                // 'calificacion_promedio' not stored directly; ignore to avoid SQL error
                default:
                    $mappedSortBy = 'created_at';
            }
        }

        $direccion = $request->get('sort_order', $request->get('direccion', 'desc'));
        if (in_array($mappedSortBy, ['nombre', 'precio', 'created_at'])) {
            $query->orderBy($mappedSortBy, $direccion);
        }

        // Paginación (limit to max 50 per page)
        $perPage = min((int) $request->get('per_page', 15), 50);
        $productos = $query->paginate($perPage);

        return ProductoResource::collection($productos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Verificar que el usuario tenga una tienda
        $userStore = \App\Models\Tienda::where('user_id', auth()->id())->first();
        if (!$userStore) {
            return response()->json([
                'message' => 'Debes tener una tienda para crear productos'
            ], 403);
        }

        try {
            $validated = $request->validate([
                'id_tienda' => 'required|exists:tiendas,id_tienda',
                'id_categoria' => 'required|exists:categorias,id_categoria',
                'nombre' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'precio' => 'required|numeric|min:0',
                'cantidad_stock' => 'required|integer|min:0',
                'peso' => 'nullable|numeric|min:0',
                'dimensiones' => 'nullable|string|max:100',
                'marca' => 'nullable|string|max:100',
                'modelo' => 'nullable|string|max:100',
                'condicion' => 'nullable|string|max:50',
                'tipo_vendedor' => 'required|string|in:directa,pedido,delivery',
                'estado' => 'nullable|string|max:50',
                'activo' => 'boolean',
                'destacado' => 'boolean',
                'imagen_principal' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'imagenes_galeria' => 'nullable|array',
                'imagenes_galeria.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048'
            ]);

            // Agregar el user_id del usuario autenticado
            $validated['user_id'] = auth()->id();

            $producto = Producto::create($validated);

            // Manejar imagen principal
            if ($request->hasFile('imagen_principal')) {
                $producto->addMediaFromRequest('imagen_principal')
                    ->toMediaCollection('images');
            }

            // Manejar imágenes de galería
            if ($request->hasFile('imagenes_galeria')) {
                foreach ($request->file('imagenes_galeria') as $imagen) {
                    $producto->addMedia($imagen)
                        ->toMediaCollection('gallery');
                }
            }

            $producto->load(['categoria', 'tienda', 'usuario']);

            return response()->json([
                'message' => 'Producto creado exitosamente',
                'data' => new ProductoResource($producto)
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
    public function show(string $id)
    {
        $producto = Producto::with(['categoria', 'tienda', 'usuario'])
            ->where('activo', true)
            ->find($id);

        if (!$producto) {
            return response()->json([
                'message' => 'Producto no encontrado'
            ], 404);
        }

        return new ProductoResource($producto);
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
                'cantidad_stock' => 'sometimes|integer|min:0',
                'sku' => 'sometimes|string|max:100|unique:productos,sku,' . $id . ',id_producto',
                'peso' => 'nullable|numeric|min:0',
                'dimensiones' => 'nullable|string|max:100',
                'imagen_principal' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'imagenes_galeria' => 'nullable|array',
                'imagenes_galeria.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'reemplazar_imagen_principal' => 'nullable|boolean',
                'reemplazar_galeria' => 'nullable|boolean',
                'activo' => 'boolean',
                'destacado' => 'boolean'
            ]);

            // Manejar imagen principal
            if ($request->hasFile('imagen_principal')) {
                if ($request->get('reemplazar_imagen_principal', false)) {
                    $producto->clearMediaCollection('images');
                }
                $producto->addMediaFromRequest('imagen_principal')
                    ->toMediaCollection('images');
            }

            // Manejar imágenes de galería
            if ($request->hasFile('imagenes_galeria')) {
                if ($request->get('reemplazar_galeria', false)) {
                    $producto->clearMediaCollection('gallery');
                }
                foreach ($request->file('imagenes_galeria') as $imagen) {
                    $producto->addMedia($imagen)
                        ->toMediaCollection('gallery');
                }
            }

            $validated['fecha_actualizacion'] = now();
            // Remover campos de archivos de la validación antes de actualizar
            unset($validated['imagen_principal'], $validated['imagenes_galeria'], 
                  $validated['reemplazar_imagen_principal'], $validated['reemplazar_galeria']);
            
            $producto->update($validated);
            $producto->load(['categoria', 'tienda', 'usuario']);

            return response()->json([
                'message' => 'Producto actualizado exitosamente',
                'data' => new ProductoResource($producto)
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

        // Limpiar todas las imágenes asociadas
        $producto->clearMediaCollection('images');
        $producto->clearMediaCollection('gallery');
        
        $producto->delete();

        return response()->json([
            'message' => 'Producto eliminado exitosamente'
        ]);
    }

    /**
     * Eliminar una imagen específica del producto
     */
    public function deleteImage(Request $request, string $id): JsonResponse
    {
        $producto = Producto::find($id);

        if (!$producto) {
            return response()->json([
                'message' => 'Producto no encontrado'
            ], 404);
        }

        $validated = $request->validate([
            'media_id' => 'required|integer',
            'collection' => 'required|string|in:images,gallery'
        ]);

        $media = $producto->getMedia($validated['collection'])
            ->where('id', $validated['media_id'])
            ->first();

        if (!$media) {
            return response()->json([
                'message' => 'Imagen no encontrada'
            ], 404);
        }

        $media->delete();

        return response()->json([
            'message' => 'Imagen eliminada exitosamente'
        ]);
    }
}
