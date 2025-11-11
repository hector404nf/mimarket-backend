<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushNotificationService
{
    protected function getWebPush(): WebPush
    {
        // Ensure OpenSSL can create EC keys on Windows by setting OPENSSL_CONF
        try {
            $fallbackPath = storage_path('framework/openssl_fallback.cnf');

            // Always prepare a fallback config that enables default and legacy providers
            if (!is_file($fallbackPath)) {
                @file_put_contents($fallbackPath, implode(PHP_EOL, [
                    'openssl_conf = openssl_init',
                    '',
                    '[openssl_init]',
                    'providers = providers_sect',
                    'alg_section = algorithm_sect',
                    '',
                    '[providers_sect]',
                    'default = default_sect',
                    'legacy = legacy_sect',
                    '',
                    '[default_sect]',
                    'activate = 1',
                    '',
                    '[legacy_sect]',
                    'activate = 1',
                ]));
            }

            // On Windows, force using the fallback config to avoid EC key creation errors
            if (PHP_OS_FAMILY === 'Windows' && is_file($fallbackPath)) {
                putenv('OPENSSL_CONF=' . $fallbackPath);
            } else {
                $opensslConf = env('OPENSSL_CONF');
                if ($opensslConf && is_file($opensslConf)) {
                    putenv('OPENSSL_CONF=' . $opensslConf);
                } elseif (is_file($fallbackPath)) {
                    putenv('OPENSSL_CONF=' . $fallbackPath);
                }
            }
        } catch (\Throwable $e) {
            // non-fatal: continue without overriding OPENSSL_CONF
        }

        // Diagnostics
        try {
            \Log::info('WebPush OpenSSL setup', [
                'openssl_version' => defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : null,
                'openssl_conf' => getenv('OPENSSL_CONF') ?: null,
                'os_family' => PHP_OS_FAMILY,
            ]);
        } catch (\Throwable $e) {
            // ignore logging errors
        }

        $publicKey = config('services.vapid.public_key') ?? env('VAPID_PUBLIC_KEY');
        $privateKey = config('services.vapid.private_key') ?? env('VAPID_PRIVATE_KEY');
        $subject = config('services.vapid.subject') ?? env('VAPID_SUBJECT', 'mailto:admin@localhost');

        if (!$publicKey || !$privateKey) {
            throw new \RuntimeException('VAPID keys no configuradas');
        }

        return new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);
    }

    /**
     * Envía una notificación a múltiples suscripciones
     * $payload: ['title' => string, 'body' => string, 'url' => string, 'icon' => string|null]
     */
    public function sendToMany($subscriptions, array $payload): void
    {
        $webPush = $this->getWebPush();

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->p256dh,
                'authToken' => $sub->auth,
                'contentEncoding' => 'aes128gcm',
            ]);

            $webPush->sendOneNotification($subscription, json_encode($payload));
        }

        foreach ($webPush->flush() as $report) {
            // Log de resultados para diagnóstico
            try {
                $endpoint = method_exists($report, 'getRequest') ? $report->getRequest()->getUri()->__toString() : ($report->getEndpoint() ?? 'unknown');
            } catch (\Throwable $e) {
                $endpoint = 'unknown';
            }

            if ($report->isSuccess()) {
                \Log::info('WebPush enviado correctamente', [
                    'endpoint' => $endpoint,
                ]);
            } else {
                \Log::warning('WebPush fallo en envío', [
                    'endpoint' => $endpoint,
                    'reason' => method_exists($report, 'getReason') ? $report->getReason() : 'unknown',
                ]);

                // Marcar como revocado si falla definitivamente
                $sub = PushSubscription::where('endpoint', $endpoint)->first();
                if ($sub) {
                    $sub->revoked_at = now();
                    $sub->save();
                }
            }
        }
    }

    /**
     * Envía una notificación a las suscripciones activas de un usuario
     */
    public function sendToUser(int $userId, array $payload): void
    {
        $subs = PushSubscription::where('user_id', $userId)->whereNull('revoked_at')->get();
        if ($subs->isNotEmpty()) {
            $this->sendToMany($subs, $payload);
        }
    }
}