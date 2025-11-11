<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Routing\Controller as BaseController;

class RoutingController extends BaseController
{
    /**
     * Proxy de ruta OSRM (driving) para evitar bloqueos desde el navegador.
     * Params: origin_lat, origin_lng, dest_lat, dest_lng
     */
    public function driving(Request $request)
    {
        $validated = $request->validate([
            'origin_lat' => 'required|numeric',
            'origin_lng' => 'required|numeric',
            'dest_lat'   => 'required|numeric',
            'dest_lng'   => 'required|numeric',
        ]);

        $oLat = (float) $validated['origin_lat'];
        $oLng = (float) $validated['origin_lng'];
        $dLat = (float) $validated['dest_lat'];
        $dLng = (float) $validated['dest_lng'];

        $base = env('OSRM_BASE_URL', 'https://router.project-osrm.org');
        $url = rtrim($base, '/') . "/route/v1/driving/{$oLng},{$oLat};{$dLng},{$dLat}";

        try {
            $resp = Http::timeout(15)->get($url, [
                'overview' => 'full',
                'geometries' => 'geojson',
            ]);

            if (!$resp->ok()) {
                return response()->json([
                    'ok' => false,
                    'error' => 'routing_upstream_failed',
                    'status' => $resp->status(),
                ], 502);
            }

            $data = $resp->json();
            $route = $data['routes'][0] ?? null;
            if (!$route) {
                return response()->json([
                    'ok' => false,
                    'error' => 'no_route',
                ], 404);
            }

            return response()->json([
                'ok' => true,
                'source' => 'osrm',
                'geometry' => $route['geometry'] ?? null,
                'distance' => $route['distance'] ?? null,
                'duration' => $route['duration'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'error' => 'proxy_exception',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}