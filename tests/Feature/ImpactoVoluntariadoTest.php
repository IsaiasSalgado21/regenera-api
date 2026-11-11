<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ImpactoVoluntariadoTest extends TestCase
{
    public function test_registering_impact_requires_authentication(): void
    {
        $this->postJson('/api/admin/voluntariados/507f1f77bcf86cd799439011/impactos', [
            'tipo' => 'plantas_sembradas',
            'cantidad' => 10,
        ])->assertUnauthorized();
    }

    public function test_only_organizers_and_admins_can_open_the_impact_recording_route(): void
    {
        $usuario = new User(['rol' => 'usuario', 'estado' => 'activo']);
        $usuario->setAttribute('_id', '507f1f77bcf86cd799439012');
        Sanctum::actingAs($usuario);

        $this->postJson('/api/admin/voluntariados/507f1f77bcf86cd799439011/impactos', [
            'tipo' => 'plantas_sembradas',
            'cantidad' => 10,
        ])->assertForbidden();
    }
}
