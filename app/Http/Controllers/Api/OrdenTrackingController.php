<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\Orden;
use App\Models\OrdenTracking;
use App\Models\DetalleOrden;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class OrdenTrackingController extends Controller
{
    /**
     * Devuelve el último snapshot de tracking de una orden.
     */
    public function get(Request $request, $ordenId)
    {
        $user = $request->user();
        $orden = Orden::findOrFail($ordenId);
        if (!$this->puedeVerTracking($user->id, $orden)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $snap = OrdenTracking::where('id_orden', $orden->id_orden)->first();
        if (!$snap) {
            return response()->json(null);
        }
        return response()->json([
            'latitud' => $snap->latitud,
            'longitud' => $snap->longitud,
            'precision' => $snap->precision,
            'velocidad' => $snap->velocidad,
            'heading' => $snap->heading,
            'tracking_activo' => (bool)$snap->tracking_activo,
            'fuente' => $snap->fuente,
            'updated_at' => $snap->updated_at,
        ]);
    }

    /**
     * Inserta/actualiza el tracking para una orden.
     */
    public function upsert(Request $request, $ordenId)
    {
        $user = $request->user();
        $orden = Orden::findOrFail($ordenId);
        if (!$this->puedeActualizarTracking($user->id, $orden)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'latitud' => 'required|numeric',
            'longitud' => 'required|numeric',
            'precision' => 'nullable|numeric',
            'velocidad' => 'nullable|numeric',
            'heading' => 'nullable|integer',
            'fuente' => 'nullable|string|max:32',
            'tracking_activo' => 'nullable|boolean',
        ]);

        $tracking = OrdenTracking::updateOrCreate(
            ['id_orden' => $orden->id_orden],
            [
                'latitud' => $validated['latitud'],
                'longitud' => $validated['longitud'],
                'precision' => $validated['precision'] ?? null,
                'velocidad' => $validated['velocidad'] ?? null,
                'heading' => $validated['heading'] ?? null,
                'fuente' => $validated['fuente'] ?? 'store_app',
                'tracking_activo' => $validated['tracking_activo'] ?? true,
            ]
        );

        return response()->json([
            'latitud' => $tracking->latitud,
            'longitud' => $tracking->longitud,
            'precision' => $tracking->precision,
            'velocidad' => $tracking->velocidad,
            'heading' => $tracking->heading,
            'tracking_activo' => (bool)$tracking->tracking_activo,
            'fuente' => $tracking->fuente,
            'updated_at' => $tracking->updated_at,
        ]);
    }

    /**
     * SSE stream con actualizaciones de tracking.
     */
    public function stream(Request $request, $ordenId)
    {
        // Intentar autenticación estándar (cookie/header)
        $user = $request->user();
        // Si no hay usuario (por ejemplo, EventSource no envía headers), intentar con token en query
        if (!$user) {
            $token = (string) $request->query('token');
            if ($token) {
                $pat = PersonalAccessToken::findToken($token);
                if ($pat) {
                    $user = $pat->tokenable;
                    // Vincular usuario al contexto para permisos/uso posterior
                    if ($user) {
                        Auth::setUser($user);
                    }
                }
            }
        }

        $orden = Orden::findOrFail($ordenId);
        if (!$user || !$this->puedeVerTracking($user->id, $orden)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $response = new StreamedResponse(function () use ($orden) {
            @ob_end_flush();
            // Cabeceras SSE
            echo ":ok\n\n"; // comentario inicial para abrir canal
            $lastHash = null;
            while (!connection_aborted()) {
                try {
                    $snap = OrdenTracking::where('id_orden', $orden->id_orden)->first();
                    $payload = null;
                    if ($snap) {
                        $payload = [
                            'latitud' => $snap->latitud,
                            'longitud' => $snap->longitud,
                            'precision' => $snap->precision,
                            'velocidad' => $snap->velocidad,
                            'heading' => $snap->heading,
                            'tracking_activo' => (bool)$snap->tracking_activo,
                            'fuente' => $snap->fuente,
                            'updated_at' => (string)$snap->updated_at,
                        ];
                    }
                    $json = json_encode($payload);
                    $hash = $json ? md5($json) : 'null';
                    if ($hash !== $lastHash) {
                        $lastHash = $hash;
                        echo "event: tracking\n";
                        echo "data: {$json}\n\n";
                    } else {
                        // Mantener vivo
                        echo ":keepalive\n\n";
                    }
                    @ob_flush();
                    flush();
                } catch (\Throwable $e) {
                    Log::warning('SSE tracking error: ' . $e->getMessage());
                    echo ":error " . addslashes($e->getMessage()) . "\n\n";
                    @ob_flush();
                    flush();
                }
                sleep(2);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no'); // para Nginx

        return $response;
    }

    private function puedeVerTracking(int $userId, Orden $orden): bool
    {
        if ($orden->user_id === $userId) return true; // cliente dueño
        // propietario de tienda con productos en la orden
        return DetalleOrden::where('id_orden', $orden->id_orden)
            ->whereHas('producto.tienda', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->exists();
    }

    private function puedeActualizarTracking(int $userId, Orden $orden): bool
    {
        // por ahora: propietario de tienda involucrada en la orden
        return DetalleOrden::where('id_orden', $orden->id_orden)
            ->whereHas('producto.tienda', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->exists();
    }
}