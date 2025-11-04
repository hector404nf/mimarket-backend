<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
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
        $query = User::query();

        // Filtros
        if ($request->has('activo')) {
            $query->where('activo', $request->boolean('activo'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('apellido', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('telefono', 'like', "%{$search}%");
            });
        }

        // Ordenamiento
        $sortBy = $request->get('sort_by', 'created_at');
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
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:8',
                'name' => 'required|string|max:100',
                'apellido' => 'required|string|max:100',
                'telefono' => 'nullable|string|max:20',
                'foto_perfil' => 'nullable|string|max:500',
                'activo' => 'boolean'
            ]);

            // Encriptar contraseña
            $validated['password'] = Hash::make($validated['password']);

            $usuario = User::create($validated);

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
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        // Incluir tiendas y productos si se solicita
        if (request()->boolean('with_tiendas')) {
            $usuario->load(['tiendas' => function($query) {
                $query->orderBy('created_at', 'desc');
            }]);
        }

        if (request()->boolean('with_productos')) {
            $usuario->load(['productos' => function($query) {
                $query->where('activo', true)
                      ->orderBy('created_at', 'desc')
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
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        try {
            $validated = $request->validate([
                'email' => 'sometimes|email|max:255|unique:users,email,' . $id . ',id',
                'password' => 'sometimes|string|min:8',
                'name' => 'sometimes|string|max:100',
                'apellido' => 'sometimes|string|max:100',
                'telefono' => 'nullable|string|max:20',
                'foto_perfil' => 'nullable|string|max:500',
                'activo' => 'boolean'
            ]);

            // Encriptar contraseña si se proporciona
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

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
        $usuario = User::find($id);

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
