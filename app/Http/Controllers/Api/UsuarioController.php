<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;

class UsuarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Usuario::query();

        // Filtros
        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('apellido', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('telefono', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'fecha_registro');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Incluir conteo de tiendas y productos si se solicita
        if ($request->boolean('with_counts')) {
            $query->withCount(['tiendas', 'productos']);
        }

        // Paginación
        $perPage = $request->get('per_page', 15);
        $usuarios = $query->paginate($perPage);

        return response()->json($usuarios);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email|max:255|unique:usuarios,email',
                'password' => 'required|string|min:8',
                'nombre' => 'required|string|max:100',
                'apellido' => 'required|string|max:100',
                'telefono' => 'nullable|string|max:20',
                'foto_perfil' => 'nullable|string|max:500',
                'activo' => 'boolean'
            ]);

            // Encriptar contraseña
            $validated['password'] = Hash::make($validated['password']);
            $validated['fecha_registro'] = now();
            $validated['fecha_actualizacion'] = now();

            $usuario = Usuario::create($validated);

            // Remover password de la respuesta
            $usuario->makeHidden(['password']);

            return response()->json([
                'message' => 'Usuario creado exitosamente',
                'data' => $usuario
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
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        // Incluir tiendas y productos si se solicita
        if (request()->boolean('with_tiendas')) {
            $usuario->load(['tiendas' => function($query) {
                $query->orderBy('fecha_creacion', 'desc');
            }]);
        }

        if (request()->boolean('with_productos')) {
            $usuario->load(['productos' => function($query) {
                $query->where('activo', true)
                      ->orderBy('fecha_creacion', 'desc')
                      ->limit(10);
            }]);
        }

        return response()->json([
            'data' => $usuario
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        try {
            $validated = $request->validate([
                'email' => 'sometimes|email|max:255|unique:usuarios,email,' . $id . ',id_usuario',
                'password' => 'sometimes|string|min:8',
                'nombre' => 'sometimes|string|max:100',
                'apellido' => 'sometimes|string|max:100',
                'telefono' => 'nullable|string|max:20',
                'foto_perfil' => 'nullable|string|max:500',
                'activo' => 'boolean'
            ]);

            // Encriptar contraseña si se proporciona
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $validated['fecha_actualizacion'] = now();
            $usuario->update($validated);

            // Remover password de la respuesta
            $usuario->makeHidden(['password']);

            return response()->json([
                'message' => 'Usuario actualizado exitosamente',
                'data' => $usuario
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
        $usuario = Usuario::find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        // Verificar si tiene tiendas o productos asociados
        if ($usuario->tiendas()->count() > 0 || $usuario->productos()->count() > 0) {
            return response()->json([
                'message' => 'No se puede eliminar el usuario porque tiene tiendas o productos asociados'
            ], 422);
        }

        $usuario->delete();

        return response()->json([
            'message' => 'Usuario eliminado exitosamente'
        ]);
    }
}
