<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Perfil;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class PerfilController extends Controller
{
    public function index(): JsonResponse
    {
        $perfiles = Perfil::with('user')->get();
        return response()->json($perfiles);
    }

    public function show($id): JsonResponse
    {
        $perfil = Perfil::with('user')->find($id);
        
        if (!$perfil) {
            return response()->json(['message' => 'Perfil no encontrado'], 404);
        }
        
        return response()->json($perfil);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'biografia' => 'nullable|string|max:1000',
                'direccion' => 'nullable|string|max:255',
                'ciudad' => 'nullable|string|max:100',
                'codigo_postal' => 'nullable|string|max:10',
                'pais' => 'nullable|string|max:100',
                'preferencias_notificacion' => 'nullable|array'
            ]);

            $perfil = Perfil::create($validated);
            $perfil->load('user');

            return response()->json($perfil, 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        $perfil = Perfil::find($id);
        
        if (!$perfil) {
            return response()->json(['message' => 'Perfil no encontrado'], 404);
        }

        try {
            $validated = $request->validate([
                'biografia' => 'nullable|string|max:1000',
                'direccion' => 'nullable|string|max:255',
                'ciudad' => 'nullable|string|max:100',
                'codigo_postal' => 'nullable|string|max:10',
                'pais' => 'nullable|string|max:100',
                'preferencias_notificacion' => 'nullable|array'
            ]);

            $perfil->update($validated);
            $perfil->load('user');

            return response()->json($perfil);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function destroy($id): JsonResponse
    {
        $perfil = Perfil::find($id);
        
        if (!$perfil) {
            return response()->json(['message' => 'Perfil no encontrado'], 404);
        }

        $perfil->delete();
        return response()->json(['message' => 'Perfil eliminado correctamente']);
    }

    public function getByUser($userId): JsonResponse
    {
        $perfil = Perfil::where('user_id', $userId)->with('user')->first();
        
        if (!$perfil) {
            return response()->json(['message' => 'Perfil no encontrado'], 404);
        }
        
        return response()->json($perfil);
    }

    public function createOrUpdateProfile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $validated = $request->validate([
                'biografia' => 'nullable|string|max:1000',
                'direccion' => 'nullable|string|max:255',
                'ciudad' => 'nullable|string|max:100',
                'codigo_postal' => 'nullable|string|max:10',
                'pais' => 'nullable|string|max:100',
                'preferencias_notificacion' => 'nullable|array'
            ]);

            $perfil = Perfil::updateOrCreate(
                ['user_id' => $user->id],
                $validated
            );
            
            $perfil->load('user');

            return response()->json([
                'message' => 'Perfil actualizado correctamente',
                'data' => $perfil
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    public function completeOnboarding(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $validated = $request->validate([
                'biografia' => 'nullable|string|max:1000',
                'direccion' => 'nullable|string|max:255',
                'ciudad' => 'nullable|string|max:100',
                'codigo_postal' => 'nullable|string|max:10',
                'pais' => 'nullable|string|max:100',
                'preferencias_notificacion' => 'nullable|array'
            ]);

            // Crear o actualizar perfil
            $perfil = Perfil::updateOrCreate(
                ['user_id' => $user->id],
                $validated
            );
            
            // Marcar usuario como onboarded
            $user->update(['onboarded' => true]);
            
            $perfil->load('user');

            return response()->json([
                'message' => 'Onboarding completado correctamente',
                'data' => $perfil,
                'user' => $user->fresh()
            ]);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        }
    }
}