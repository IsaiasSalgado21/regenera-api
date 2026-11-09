<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VoluntariadoDashboardTest extends TestCase
{
    public function test_dashboard_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/admin/voluntariados/507f1f77bcf86cd799439011/dashboard')
            ->assertUnauthorized();
    }

    public function test_dashboard_endpoint_rejects_users_without_management_role(): void
    {
        $usuario = new User(['rol' => 'usuario']);
        $usuario->setAttribute('_id', '507f1f77bcf86cd799439012');
        Sanctum::actingAs($usuario);

        $this->getJson('/api/admin/voluntariados/507f1f77bcf86cd799439011/dashboard')
            ->assertForbidden();
    }
}
