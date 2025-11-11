<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PushController extends Controller
{
    /**
     * Devuelve la VAPID public key desde .env
     */
    public function getVapidPublicKey()
    {
        $publicKey = config('services.vapid.public_key') ?? env('VAPID_PUBLIC_KEY');
        if (!$publicKey) {
            return response()->json([
                'success' => false,
                'message' => 'VAPID_PUBLIC_KEY no configurada en .env',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [ 'publicKey' => $publicKey ],
        ]);
    }

    /**
     * Lista suscripciones push del usuario autenticado
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $subs = PushSubscription::where('user_id', $userId)->whereNull('revoked_at')->get();
        return response()->json(['success' => true, 'data' => $subs]);
    }

    /**
     * Suscribir (guardar) una suscripción push
     */
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'device' => 'nullable|string',
        ]);

        $userId = Auth::id();

        $subscription = PushSubscription::updateOrCreate(
            ['endpoint' => $validated['endpoint']],
            [
                'user_id' => $userId,
                'p256dh' => data_get($validated, 'keys.p256dh'),
                'auth' => data_get($validated, 'keys.auth'),
                'device' => $request->input('device'),
                'user_agent' => $request->header('User-Agent'),
                'subscribed_at' => now(),
                'revoked_at' => null,
            ]
        );

        return response()->json(['success' => true, 'data' => $subscription]);
    }

    /**
     * Desuscribir (revocar) una suscripción por endpoint
     */
    public function unsubscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
        ]);

        $userId = Auth::id();
        $sub = PushSubscription::where('user_id', $userId)
            ->where('endpoint', $validated['endpoint'])
            ->first();

        if (!$sub) {
            return response()->json(['success' => false, 'message' => 'Suscripción no encontrada'], 404);
        }

        $sub->revoked_at = now();
        $sub->save();

        return response()->json(['success' => true]);
    }

    /**
     * Envía una notificación de prueba a todas las suscripciones del usuario
     */
    public function sendTest(Request $request)
    {
        $userId = Auth::id();
        $subs = PushSubscription::where('user_id', $userId)->whereNull('revoked_at')->get();

        if ($subs->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No hay suscripciones activas'], 404);
        }

        try {
            app(\App\Services\PushNotificationService::class)->sendToMany($subs, [
                'title' => 'Prueba de notificación',
                'body' => 'Las notificaciones push están configuradas correctamente en MiMarket',
                'url' => $request->input('url', '/dashboard-tienda/notificaciones'),
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            $details = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'openssl_version' => defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : null,
                'openssl_conf' => getenv('OPENSSL_CONF') ?: null,
                'os_family' => PHP_OS_FAMILY,
                'subs_count' => $subs->count(),
                'vapid_public_key_len' => strlen(config('services.vapid.public_key') ?? env('VAPID_PUBLIC_KEY')),
                'vapid_private_key_len' => strlen(config('services.vapid.private_key') ?? env('VAPID_PRIVATE_KEY')),
                'vapid_subject' => config('services.vapid.subject') ?? env('VAPID_SUBJECT'),
            ];

            if (str_contains($e->getMessage(), 'Unable to create the local key')) {
                $details['hint'] = 'OpenSSL no pudo crear la clave EC local. En Windows, habilita providers default/legacy en openssl.cnf o usa el fallback generado en storage/framework/openssl_fallback.cnf.';
            }

            Log::error('Error enviando push de prueba', $details);
            return response()->json(['success' => false, 'message' => 'Error enviando push', 'details' => $details], 500);
        }
    }
}