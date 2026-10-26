<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CrudAccessTest extends TestCase
{
    public function test_place_and_flora_fauna_writes_require_authentication(): void
    {
        $this->postJson('/api/lugares', [])->assertUnauthorized();
        $this->putJson('/api/lugares/507f1f77bcf86cd799439011', [])->assertUnauthorized();
        $this->postJson('/api/flora-fauna', [])->assertUnauthorized();
        $this->putJson('/api/flora-fauna/507f1f77bcf86cd799439011', [])->assertUnauthorized();
        $this->getJson('/api/admin/lugares')->assertUnauthorized();
        $this->getJson('/api/admin/flora-fauna')->assertUnauthorized();
    }

    public function test_admin_routes_reject_authenticated_non_admin_users(): void
    {
        $organizador = new User(['rol' => 'organizador', 'organizadorVerificado' => false]);
        $organizador->setAttribute('_id', '507f1f77bcf86cd799439011');
        Sanctum::actingAs($organizador);

        $this->getJson('/api/admin/lugares')->assertForbidden();
        Sanctum::actingAs($organizador);
        $this->getJson('/api/admin/flora-fauna')->assertForbidden();
    }

    public function test_create_requests_reject_client_supplied_owner_and_state(): void
    {
        $organizador = new User(['rol' => 'organizador', 'organizadorVerificado' => false]);
        $organizador->setAttribute('_id', '507f1f77bcf86cd799439011');
        Sanctum::actingAs($organizador);

        $this->postJson('/api/lugares', [
            'nombre' => 'Lugar de prueba',
            'municipio' => 'La Paz',
            'tipo' => 'playa',
            'estado' => 'archivado',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('estado');

        Sanctum::actingAs($organizador);
        $this->postJson('/api/flora-fauna', [
            'nombre' => 'Especie de prueba',
            'tipo' => 'fauna',
            'descripcion' => 'Descripción de prueba.',
            'creadoPor' => '507f1f77bcf86cd799439012',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('creadoPor');
    }
}
