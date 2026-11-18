<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\PushSubscription;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationFeaturesTest extends TestCase
{
    private ?string $pushTestDatabase = null;

    protected function tearDown(): void
    {
        if ($this->pushTestDatabase !== null) {
            DB::connection('mongodb')->getMongoDB()->drop();
            DB::purge('mongodb');
        }

        parent::tearDown();
    }

    public function test_notification_center_and_profile_pages_are_available(): void
    {
        $this->get('/notificaciones')->assertOk();
        $this->get('/perfil')->assertOk();
    }

    public function test_navigation_uses_an_accessible_notification_bell_and_the_logo_links_home(): void
    {
        $html = $this->get('/notificaciones')->assertOk()->getContent();
        $document = new \DOMDocument;
        $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);

        $this->assertSame(1, $xpath->query('//a[contains(concat(" ", normalize-space(@class), " "), " logo ") and @href="'.url('/').'"]')->length);
        $this->assertSame(0, $xpath->query('//nav[contains(concat(" ", normalize-space(@class), " "), " nav ") or @id="mobileMenu"]/a[normalize-space(.)="Inicio"]')->length);
        $buttons = $xpath->query('//button[@data-notification-toggle]');
        $this->assertSame(2, $buttons->length);
        foreach ($buttons as $button) {
            $this->assertSame('Abrir notificaciones', $button->getAttribute('aria-label'));
            $this->assertSame(1, $xpath->query('./svg[@aria-hidden="true"]', $button)->length);
            $this->assertSame(1, $xpath->query('./span[@data-notification-badge]', $button)->length);
            $this->assertStringNotContainsString('Notificaciones', $button->textContent);
        }
    }

    public function test_notification_endpoints_require_authentication(): void
    {
        $this->getJson('/api/notificaciones')->assertUnauthorized();
        $this->getJson('/api/notificaciones/no-leidas')->assertUnauthorized();
        $this->getJson('/api/notificaciones/preferencias')->assertUnauthorized();
        $this->patchJson('/api/notificaciones/preferencias', [
            'recordatorioFrecuencia' => 'daily',
        ])->assertUnauthorized();
        $this->postJson('/api/notificaciones/push/suscripcion', [])->assertUnauthorized();
    }

    public function test_authenticated_user_can_save_a_push_subscription(): void
    {
        $this->configurePushTestDatabase();
        $userId = '507f1f77bcf86cd799439021';
        $user = $this->pushTestUser($userId);
        Sanctum::actingAs($user);
        $payload = $this->pushSubscriptionPayload('save');

        $this->postJson('/api/notificaciones/push/suscripcion', $payload)
            ->assertOk()
            ->assertJsonPath('message', 'Notificaciones push activadas en este dispositivo.');

        $saved = PushSubscription::query()->where('endpoint', $payload['endpoint'])->firstOrFail();
        $this->assertSame($userId, (string) $saved->usuarioId);
        $this->assertSame($payload['keys']['p256dh'], $saved->publicKey);
        $this->assertSame($payload['keys']['auth'], $saved->authToken);
        $this->assertSame('aes128gcm', $saved->contentEncoding);
    }

    public function test_push_subscription_rejects_invalid_data(): void
    {
        $this->configurePushAvailability();
        Sanctum::actingAs($this->pushTestUser('507f1f77bcf86cd799439022'));

        $this->postJson('/api/notificaciones/push/suscripcion', [
            'endpoint' => 'not-a-url',
            'keys' => [],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['endpoint', 'keys.p256dh', 'keys.auth']);
    }

    public function test_user_cannot_modify_another_users_push_subscription(): void
    {
        $this->configurePushTestDatabase();
        $ownerId = 'push-owner-test';
        $payload = $this->pushSubscriptionPayload('owned');
        PushSubscription::create([
            'usuarioId' => $ownerId,
            'endpoint' => $payload['endpoint'],
            'publicKey' => $payload['keys']['p256dh'],
            'authToken' => $payload['keys']['auth'],
            'contentEncoding' => 'aes128gcm',
        ]);
        Sanctum::actingAs($this->pushTestUser('push-other-user-test'));

        $this->postJson('/api/notificaciones/push/suscripcion', $payload)->assertForbidden();
        $this->deleteJson('/api/notificaciones/push/suscripcion', [
            'endpoint' => $payload['endpoint'],
        ])->assertOk();

        $this->assertSame(1, PushSubscription::query()->where('endpoint', $payload['endpoint'])->count());
    }

    public function test_user_can_delete_own_push_subscription(): void
    {
        $this->configurePushTestDatabase();
        $payload = $this->pushSubscriptionPayload('delete-own');
        $user = $this->pushTestUser('507f1f77bcf86cd799439025');
        Sanctum::actingAs($user);
        $this->postJson('/api/notificaciones/push/suscripcion', $payload)->assertOk();

        $this->deleteJson('/api/notificaciones/push/suscripcion', [
            'endpoint' => $payload['endpoint'],
        ])->assertOk()
            ->assertJsonPath('message', 'Notificaciones push desactivadas en este dispositivo.');

        $this->assertNull(PushSubscription::query()->where('endpoint', $payload['endpoint'])->first());
    }

    public function test_deleting_nonexistent_push_subscription_is_safe(): void
    {
        $this->configurePushTestDatabase();
        Sanctum::actingAs($this->pushTestUser('507f1f77bcf86cd799439026'));

        $this->deleteJson('/api/notificaciones/push/suscripcion', [
            'endpoint' => 'https://push.example.test/nonexistent',
        ])->assertOk();

        $this->assertSame(0, PushSubscription::query()->count());
    }

    private function configurePushTestDatabase(): void
    {
        $uri = getenv('MONGODB_TEST_URI');
        if (! $uri) {
            $this->markTestSkipped('Configura MONGODB_TEST_URI con una base MongoDB de pruebas.');
        }

        $this->pushTestDatabase = 'regenera_push_test_'.bin2hex(random_bytes(6));
        config([
            'database.connections.mongodb.dsn' => $uri,
            'database.connections.mongodb.database' => $this->pushTestDatabase,
        ]);
        DB::purge('mongodb');
        $this->configurePushAvailability();
    }

    private function configurePushAvailability(): void
    {
        config([
            'services.webpush.subject' => 'mailto:push@example.test',
            'services.webpush.public_key' => 'test-public-key',
            'services.webpush.private_key' => 'test-private-key',
        ]);
    }

    private function pushTestUser(string $id): User
    {
        $user = new User(['rol' => 'usuario', 'perfilCompleto' => true]);
        $user->setAttribute('_id', $id);

        return $user;
    }

    private function pushSubscriptionPayload(string $suffix): array
    {
        return [
            'endpoint' => 'https://push.example.test/'.$suffix,
            'keys' => ['p256dh' => 'test-p256dh-key', 'auth' => 'test-auth-token'],
            'contentEncoding' => 'aes128gcm',
        ];
    }

    public function test_enrollment_read_and_write_endpoints_require_authentication(): void
    {
        $this->getJson('/api/inscripciones')->assertUnauthorized();
        $this->getJson('/api/inscripciones/voluntariado/507f1f77bcf86cd799439011/mia')->assertUnauthorized();
        $this->getJson('/api/inscripciones/507f1f77bcf86cd799439011')->assertUnauthorized();
        $this->patchJson('/api/inscripciones/507f1f77bcf86cd799439011', [
            'estado' => 'cancelada',
        ])->assertUnauthorized();
        $this->postJson('/api/inscripciones/507f1f77bcf86cd799439011/cancelar')->assertUnauthorized();
        $this->deleteJson('/api/inscripciones/507f1f77bcf86cd799439011')->assertUnauthorized();
    }

    public function test_admins_cannot_set_participant_reminder_preferences(): void
    {
        $admin = new User(['rol' => 'admin', 'perfilCompleto' => true]);
        $admin->setAttribute('_id', '507f1f77bcf86cd799439011');
        Sanctum::actingAs($admin);

        $this->patchJson('/api/notificaciones/preferencias', [
            'recordatorioFrecuencia' => 'daily',
        ])->assertForbidden();
    }

    public function test_invalid_reminder_frequency_is_rejected_for_non_admins(): void
    {
        $user = new User(['rol' => 'usuario', 'perfilCompleto' => true]);
        $user->setAttribute('_id', '507f1f77bcf86cd799439012');
        Sanctum::actingAs($user);

        $this->patchJson('/api/notificaciones/preferencias', [
            'recordatorioFrecuencia' => 'hourly',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('recordatorioFrecuencia');
    }
}
