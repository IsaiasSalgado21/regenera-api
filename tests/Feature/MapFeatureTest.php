<?php

namespace Tests\Feature;

use Tests\TestCase;

class MapFeatureTest extends TestCase
{
    public function test_map_page_loads_without_server_supplied_marker_data(): void
    {
        $this->get('/mapa')
            ->assertOk()
            ->assertSee('Voluntariados')
            ->assertSee('Lugares')
            ->assertSee('Flora y fauna')
            ->assertSee('environmentMap')
            ->assertDontSee('regeneraMapMarkers');
    }

    public function test_user_identity_and_enrollment_require_a_sanctum_token(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
        $this->withHeader('Authorization', 'Bearer invalid|token')
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
        $this->postJson('/api/inscripciones', [
            'voluntariadoId' => '507f1f77bcf86cd799439011',
        ])->assertUnauthorized();
    }
}
