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
        $query = Tienda::with(['user']);

        // Por defecto, solo mostrar tiendas verificadas
        $query->where('verificada', true);

        // Filtros
        if ($request->has('usuario')) {
            $query->where('user_id', $request->usuario);
        }

        // Permitir override del filtro de verificada si se especifica explícitamente
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

        // Filtro por ciudad
        if ($request->has('ciudad')) {
            $query->where('direccion', 'like', '%' . $request->ciudad . '%');
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
        // Verificar si el usuario ya tiene una tienda
        $existingStore = Tienda::where('user_id', auth()->id())->first();
        if ($existingStore) {
            return response()->json([
                'message' => 'Ya tienes una tienda creada'
            ], 403);
        }

        try {
            $validated = $request->validate([
                'nombre_tienda' => 'required|string|max:255|unique:tiendas,nombre_tienda',
                'descripcion' => 'nullable|string|max:1000',
                'categoria_principal' => 'nullable|string|max:255',
                'logo' => 'nullable|string|max:500',
                'banner' => 'nullable|string|max:500',
                'direccion' => 'nullable|string|max:500',
                'telefono_contacto' => 'nullable|string|max:20',
                'email_contacto' => 'nullable|email|max:255',
                'sitio_web' => 'nullable|url|max:500',
                'latitud' => 'nullable|numeric|between:-90,90',
                'longitud' => 'nullable|numeric|between:-180,180',
                'verificada' => 'boolean'
            ]);

            // Agregar el user_id del usuario autenticado
            $validated['user_id'] = auth()->id();
            $validated['calificacion_promedio'] = 0;
            $validated['total_productos'] = 0;
            
            // Establecer verificada como true por defecto si no se especifica
            if (!isset($validated['verificada'])) {
                $validated['verificada'] = true;
            }

            $tienda = Tienda::create($validated);
            $tienda->load(['user']);

            return response()->json([
                'success' => true,
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
        $tienda = Tienda::with(['user'])->find($id);

        if (!$tienda || !$tienda->verificada) {
            return response()->json([
                'message' => 'Tienda no encontrada'
            ], 404);
        }

        // Incluir productos si se solicita
        if (request()->boolean('with_products')) {
            $tienda->load(['productos' => function($query) {
                $query->where('estado', 'activo')
                      ->orderBy('created_at', 'desc')
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

        // Verificar que el usuario autenticado sea el propietario de la tienda
        if ($tienda->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'No tienes permisos para actualizar esta tienda'
            ], 403);
        }

        try {
            $validated = $request->validate([
                'nombre_tienda' => 'sometimes|string|max:255|unique:tiendas,nombre_tienda,' . $id . ',id_tienda',
                'descripcion' => 'nullable|string|max:1000',
                'categoria_principal' => 'nullable|string|max:255',
                'logo' => 'nullable|string|max:500',
                'banner' => 'nullable|string|max:500',
                'direccion' => 'nullable|string|max:500',
                'telefono_contacto' => 'nullable|string|max:20',
                'email_contacto' => 'nullable|email|max:255',
                'sitio_web' => 'nullable|url|max:500',
                'latitud' => 'nullable|numeric|between:-90,90',
                'longitud' => 'nullable|numeric|between:-180,180',
                'verificada' => 'boolean'
            ]);

            $tienda->update($validated);
            $tienda->load(['user']);

            return response()->json([
                'success' => true,
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

    /**
     * Deactivate the specified store.
     */
    public function deactivate(string $id): JsonResponse
    {
        $tienda = Tienda::find($id);

        if (!$tienda) {
            return response()->json([
                'message' => 'Tienda no encontrada'
            ], 404);
        }

        // Verificar que el usuario autenticado sea el propietario de la tienda
        if ($tienda->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'No tienes permisos para desactivar esta tienda'
            ], 403);
        }

        $tienda->update(['verificada' => false]);

        return response()->json([
             'success' => true,
             'message' => 'Tienda desactivada exitosamente'
         ]);
     }

     /**
      * Get products for a specific store.
      */
     public function getProducts(string $id): JsonResponse
     {
         $tienda = Tienda::find($id);

         if (!$tienda) {
             return response()->json([
                 'message' => 'Tienda no encontrada'
             ], 404);
         }

         $productos = $tienda->productos()
             ->where('estado', 'activo')
             ->orderBy('created_at', 'desc')
             ->get();

         return response()->json([
             'success' => true,
             'data' => $productos
         ]);
     }
 }
