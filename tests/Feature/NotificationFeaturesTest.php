<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationFeaturesTest extends TestCase
{
    public function test_notification_center_and_profile_pages_are_available(): void
    {
        $this->get('/notificaciones')->assertOk();
        $this->get('/perfil')->assertOk();
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
        $admin = new User(['rol' => 'admin']);
        $admin->setAttribute('_id', '507f1f77bcf86cd799439011');
        Sanctum::actingAs($admin);

        $this->patchJson('/api/notificaciones/preferencias', [
            'recordatorioFrecuencia' => 'daily',
        ])->assertForbidden();
    }

    public function test_invalid_reminder_frequency_is_rejected_for_non_admins(): void
    {
        $user = new User(['rol' => 'usuario']);
        $user->setAttribute('_id', '507f1f77bcf86cd799439012');
        Sanctum::actingAs($user);

        $this->patchJson('/api/notificaciones/preferencias', [
            'recordatorioFrecuencia' => 'hourly',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('recordatorioFrecuencia');
    }
}
