<?php

namespace Tests\Unit;

use App\Models\Notificacion;
use App\Services\WebPushService;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\Subscription;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class WebPushServiceTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_sends_to_the_notification_users_subscriptions_with_the_expected_payload(): void
    {
        $this->configureVapid();
        $notification = $this->notification();
        $subscriptions = [
            $this->storedSubscription('https://push.example.test/device-one', 'key-one', 'auth-one'),
            $this->storedSubscription('https://push.example.test/device-two', 'key-two', 'auth-two'),
        ];

        $subscriptionQuery = Mockery::mock();
        $subscriptionQuery->shouldReceive('where')
            ->once()
            ->with('usuarioId', 'recipient-1')
            ->andReturnSelf();
        $subscriptionQuery->shouldReceive('get')->once()->andReturn(collect($subscriptions));
        Mockery::mock('alias:App\\Models\\PushSubscription')
            ->shouldReceive('query')
            ->once()
            ->andReturn($subscriptionQuery);

        $expectedPayload = [
            'title' => 'Aviso de prueba',
            'body' => 'Mensaje de prueba',
            'url' => url('/notificaciones?pushNotification=notification-1'),
        ];
        $expectedEndpoints = array_column(array_map(
            fn (object $subscription): array => [
                'endpoint' => $subscription->endpoint,
                'publicKey' => $subscription->publicKey,
                'authToken' => $subscription->authToken,
            ],
            $subscriptions,
        ), 'endpoint');
        $sentEndpoints = [];

        $webPush = Mockery::mock('overload:Minishlink\\WebPush\\WebPush');
        $webPush->shouldReceive('queueNotification')
            ->twice()
            ->withArgs(function (Subscription $subscription, string $payload) use (&$sentEndpoints, $expectedEndpoints, $expectedPayload): bool {
                $endpoint = $subscription->getEndpoint();
                $sentEndpoints[] = $endpoint;

                return in_array($endpoint, $expectedEndpoints, true)
                    && json_decode($payload, true, 512, JSON_THROW_ON_ERROR) === $expectedPayload;
            });
        $success = Mockery::mock(MessageSentReport::class);
        $success->shouldReceive('isSuccess')->once()->andReturn(true);
        $webPush->shouldReceive('flush')->once()->andReturn([$success]);

        (new WebPushService)->sendToNotification($notification);

        sort($sentEndpoints);
        sort($expectedEndpoints);
        $this->assertSame($expectedEndpoints, $sentEndpoints);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_deletes_expired_subscriptions(): void
    {
        $this->configureVapid();
        $storedSubscription = $this->storedSubscription('https://push.example.test/expired', 'key', 'auth');
        $fetchQuery = Mockery::mock();
        $fetchQuery->shouldReceive('where')->once()->with('usuarioId', 'recipient-1')->andReturnSelf();
        $fetchQuery->shouldReceive('get')->once()->andReturn(collect([$storedSubscription]));
        $deleteQuery = Mockery::mock();
        $deleteQuery->shouldReceive('where')->once()
            ->with('endpoint', $storedSubscription->endpoint)
            ->andReturnSelf();
        $deleteQuery->shouldReceive('delete')->once()->andReturn(1);
        Mockery::mock('alias:App\\Models\\PushSubscription')
            ->shouldReceive('query')
            ->twice()
            ->andReturn($fetchQuery, $deleteQuery);

        $webPush = Mockery::mock('overload:Minishlink\\WebPush\\WebPush');
        $webPush->shouldReceive('queueNotification')->once();
        $expired = Mockery::mock(MessageSentReport::class);
        $expired->shouldReceive('isSuccess')->once()->andReturn(false);
        $expired->shouldReceive('isSubscriptionExpired')->once()->andReturn(true);
        $expired->shouldReceive('getEndpoint')->once()->andReturn($storedSubscription->endpoint);
        $webPush->shouldReceive('flush')->once()->andReturn([$expired]);

        (new WebPushService)->sendToNotification($this->notification());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_logs_failed_delivery_reports(): void
    {
        $this->configureVapid();
        $storedSubscription = $this->storedSubscription('https://push.example.test/failure', 'key', 'auth');
        $query = Mockery::mock();
        $query->shouldReceive('where')->once()->with('usuarioId', 'recipient-1')->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn(collect([$storedSubscription]));
        Mockery::mock('alias:App\\Models\\PushSubscription')
            ->shouldReceive('query')->once()->andReturn($query);

        $webPush = Mockery::mock('overload:Minishlink\\WebPush\\WebPush');
        $webPush->shouldReceive('queueNotification')->once();
        $failure = Mockery::mock(MessageSentReport::class);
        $failure->shouldReceive('isSuccess')->once()->andReturn(false);
        $failure->shouldReceive('isSubscriptionExpired')->once()->andReturn(false);
        $failure->shouldReceive('getEndpoint')->once()->andReturn($storedSubscription->endpoint);
        $failure->shouldReceive('getReason')->once()->andReturn('Push service unavailable');
        $webPush->shouldReceive('flush')->once()->andReturn([$failure]);
        Log::shouldReceive('warning')->once()->with(
            'Web push delivery failed.',
            Mockery::on(fn (array $context): bool => $context['notification_id'] === 'notification-1'
                && $context['endpoint'] === $storedSubscription->endpoint
                && $context['reason'] === 'Push service unavailable'),
        );

        (new WebPushService)->sendToNotification($this->notification());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_logs_transport_exceptions_without_propagating_them(): void
    {
        $this->configureVapid();
        $storedSubscription = $this->storedSubscription('https://push.example.test/exception', 'key', 'auth');
        $query = Mockery::mock();
        $query->shouldReceive('where')->once()->with('usuarioId', 'recipient-1')->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn(collect([$storedSubscription]));
        Mockery::mock('alias:App\\Models\\PushSubscription')
            ->shouldReceive('query')->once()->andReturn($query);

        $exception = new \RuntimeException('Transport setup failed');
        $webPush = Mockery::mock('overload:Minishlink\\WebPush\\WebPush');
        $webPush->shouldReceive('queueNotification')->once()->andThrow($exception);
        Log::shouldReceive('error')->once()->with(
            'Web push delivery could not be completed.',
            Mockery::on(fn (array $context): bool => $context['notification_id'] === 'notification-1'
                && $context['exception'] === $exception),
        );

        (new WebPushService)->sendToNotification($this->notification());
    }

    public function test_does_not_query_or_send_when_vapid_configuration_is_incomplete(): void
    {
        Log::shouldReceive('error')->never();
        Log::shouldReceive('warning')->never();

        foreach (['public_key', 'private_key', 'subject'] as $missingSetting) {
            $this->configureVapid();
            config(['services.webpush.'.$missingSetting => null]);

            $service = new WebPushService;
            $this->assertFalse($service->available());
            $service->sendToNotification($this->notification());
        }
    }

    private function configureVapid(): void
    {
        config([
            'services.webpush.subject' => 'mailto:push@example.test',
            'services.webpush.public_key' => 'test-public-key',
            'services.webpush.private_key' => 'test-private-key',
        ]);
    }

    private function notification(): Notificacion
    {
        $notification = new Notificacion([
            'usuarioId' => 'recipient-1',
            'titulo' => 'Aviso de prueba',
            'mensaje' => 'Mensaje de prueba',
        ]);
        $notification->setAttribute('_id', 'notification-1');

        return $notification;
    }

    private function storedSubscription(string $endpoint, string $publicKey, string $authToken): object
    {
        return (object) [
            'endpoint' => $endpoint,
            'publicKey' => $publicKey,
            'authToken' => $authToken,
            'contentEncoding' => 'aes128gcm',
        ];
    }
}