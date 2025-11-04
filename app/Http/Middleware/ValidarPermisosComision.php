<?php

namespace App\Http\Middleware;

use App\Models\Tienda;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ValidarPermisosComision
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permiso = 'ver'): Response
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        // Los administradores tienen acceso completo
        if ($user->tipo_usuario === 'administrador') {
            return $next($request);
        }

        // Para otros usuarios, validar permisos específicos
        switch ($permiso) {
            case 'administrar':
                // Solo administradores pueden administrar comisiones
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para administrar comisiones'
                ], 403);

            case 'ver':
                // Usuarios pueden ver sus propias comisiones
                return $this->validarAccesoTienda($request, $next);

            case 'liquidar':
                // Solo administradores pueden crear/procesar liquidaciones
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para gestionar liquidaciones'
                ], 403);

            default:
                return response()->json([
                    'success' => false,
                    'message' => 'Permiso no válido'
                ], 400);
        }
    }

    /**
     * Validar que el usuario tenga acceso a la tienda especificada
     */
    private function validarAccesoTienda(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $tiendaId = $request->route('tiendaId') ?? $request->input('id_tienda');

        if (!$tiendaId) {
            return response()->json([
                'success' => false,
                'message' => 'ID de tienda requerido'
            ], 400);
        }

        try {
            $tienda = Tienda::findOrFail($tiendaId);

            // Verificar que el usuario sea el propietario de la tienda
            if ($user->id !== $tienda->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para acceder a las comisiones de esta tienda'
                ], 403);
            }

            return $next($request);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tienda no encontrada'
            ], 404);
        }
    }
}
