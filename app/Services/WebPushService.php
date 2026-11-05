<?php

namespace App\Services;

use App\Models\Notificacion;
use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    public function publicKey(): ?string
    {
        return config('services.webpush.public_key') ?: null;
    }

    public function available(): bool
    {
        return $this->publicKey() !== null
            && (bool) config('services.webpush.private_key')
            && (bool) config('services.webpush.subject');
    }

    public function sendToNotification(Notificacion $notification): void
    {
        if (! $this->available()) {
            return;
        }

        try {
            $subscriptions = PushSubscription::query()
                ->where('usuarioId', $notification->usuarioId)
                ->get();

            if ($subscriptions->isEmpty()) {
                return;
            }

            $webPush = new WebPush([
                'VAPID' => [
                    'subject' => config('services.webpush.subject'),
                    'publicKey' => config('services.webpush.public_key'),
                    'privateKey' => config('services.webpush.private_key'),
                ],
            ], ['TTL' => 86400, 'urgency' => 'normal']);

            $payload = json_encode([
                'title' => $notification->titulo,
                'body' => $notification->mensaje,
                'url' => url('/notificaciones?pushNotification='.(string) $notification->_id),
            ], JSON_THROW_ON_ERROR);

            foreach ($subscriptions as $storedSubscription) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $storedSubscription->endpoint,
                        'keys' => [
                            'p256dh' => $storedSubscription->publicKey,
                            'auth' => $storedSubscription->authToken,
                        ],
                        'contentEncoding' => $storedSubscription->contentEncoding ?: 'aes128gcm',
                    ]),
                    $payload,
                );
            }

            foreach ($webPush->flush() as $report) {
                if ($report->isSuccess()) {
                    continue;
                }

                if ($report->isSubscriptionExpired()) {
                    PushSubscription::query()->where('endpoint', $report->getEndpoint())->delete();

                    continue;
                }

                Log::warning('Web push delivery failed.', [
                    'notification_id' => (string) $notification->_id,
                    'endpoint' => $report->getEndpoint(),
                    'reason' => $report->getReason(),
                ]);
            }
        } catch (\Throwable $exception) {
            Log::error('Web push delivery could not be completed.', [
                'notification_id' => (string) $notification->_id,
                'exception' => $exception,
            ]);
        }
    }
}
