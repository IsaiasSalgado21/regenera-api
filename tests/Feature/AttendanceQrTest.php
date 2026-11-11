<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Voluntariado;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceQrTest extends TestCase
{
    public function test_qr_attendance_endpoint_requires_authentication(): void
    {
        $this->postJson('/api/asistencias', [
            'metodo' => 'QR',
            'tokenAsistencia' => str_repeat('a', 64),
        ])->assertUnauthorized();
    }

    public function test_qr_attendance_does_not_accept_client_supplied_user_or_volunteer_ids(): void
    {
        $usuario = new User(['rol' => 'usuario', 'estado' => 'activo']);
        $usuario->setAttribute('_id', '507f1f77bcf86cd799439011');
        Sanctum::actingAs($usuario);

        $this->postJson('/api/asistencias', [
            'metodo' => 'QR',
            'tokenAsistencia' => str_repeat('a', 64),
            'usuarioId' => '507f1f77bcf86cd799439012',
            'voluntariadoId' => '507f1f77bcf86cd799439013',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['usuarioId', 'voluntariadoId']);
    }

    public function test_new_volunteer_gets_a_private_random_active_attendance_token(): void
    {
        $voluntariado = new Voluntariado;
        Voluntariado::getEventDispatcher()->dispatch(
            'eloquent.creating: '.Voluntariado::class,
            [$voluntariado],
        );

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{64}$/', $voluntariado->tokenAsistencia);
        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{8}$/', $voluntariado->codigoCorto);
        $this->assertTrue($voluntariado->codigoAsistenciaActivo);
        $this->assertArrayNotHasKey('tokenAsistencia', $voluntariado->toArray());
    }

    public function test_attendance_qr_toggle_requires_management_role(): void
    {
        $path = '/api/admin/voluntariados/507f1f77bcf86cd799439013/codigo-asistencia';
        $this->patchJson($path, ['codigoAsistenciaActivo' => false])
            ->assertUnauthorized();

        $usuario = new User(['rol' => 'usuario', 'estado' => 'activo']);
        $usuario->setAttribute('_id', '507f1f77bcf86cd799439012');
        Sanctum::actingAs($usuario);

        $this->patchJson($path, ['codigoAsistenciaActivo' => false])
            ->assertForbidden();
    }

    public function test_attendance_scan_page_is_available(): void
    {
        $this->get('/asistencia')
            ->assertOk()
            ->assertSee('Registrar asistencia');
    }
}
