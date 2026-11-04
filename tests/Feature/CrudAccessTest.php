<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Http\UploadedFile;
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
        $this->getJson('/api/admin/voluntariados')->assertUnauthorized();
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

    public function test_place_and_flora_fauna_locations_must_be_inside_baja_california_sur(): void
    {
        $admin = new User(['rol' => 'admin']);
        $admin->setAttribute('_id', '507f1f77bcf86cd799439011');
        Sanctum::actingAs($admin);

        $place = [
            'nombre' => 'Lugar fuera de BCS',
            'municipio' => 'Tijuana',
            'tipo' => 'playa',
            'latitud' => 32.5149,
            'longitud' => -117.0382,
        ];
        $this->postJson('/api/lugares', $place)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['latitud', 'longitud']);
        Sanctum::actingAs($admin);
        $this->putJson('/api/lugares/507f1f77bcf86cd799439012', $place)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['latitud', 'longitud']);

        $flora = [
            'nombre' => 'Especie fuera de BCS',
            'tipo' => 'fauna',
            'descripcion' => 'Ubicación fuera de Baja California Sur.',
            'ubicacion' => [
                'latitud' => 32.5149,
                'longitud' => -117.0382,
            ],
        ];
        Sanctum::actingAs($admin);
        $this->postJson('/api/flora-fauna', $flora)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ubicacion');
        Sanctum::actingAs($admin);
        $this->putJson('/api/flora-fauna/507f1f77bcf86cd799439012', $flora)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ubicacion');
    }

    public function test_image_upload_limits_are_enforced_for_articles_flora_and_volunteering(): void
    {
        $admin = new User(['rol' => 'admin']);
        $admin->setAttribute('_id', '507f1f77bcf86cd799439011');
        Sanctum::actingAs($admin);
        $this->withHeader('Accept', 'application/json');

        $articleImages = array_map(
            fn ($index) => UploadedFile::fake()->image("article-{$index}.jpg"),
            range(1, 11),
        );
        $this->post('/api/articulos', [
            'titulo' => 'Artículo de prueba',
            'contenido' => 'Contenido de prueba.',
            'categoria' => 'Conservación',
            'imagenes' => $articleImages,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('imagenes');

        Sanctum::actingAs($admin);
        $floraImages = array_map(
            fn ($index) => UploadedFile::fake()->image("flora-{$index}.jpg"),
            range(1, 6),
        );
        $this->post('/api/flora-fauna', [
            'nombre' => 'Especie de prueba',
            'tipo' => 'flora',
            'descripcion' => 'Descripción de prueba.',
            'imagenes' => $floraImages,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('imagenes');

        Sanctum::actingAs($admin);
        $volunteerImages = array_map(
            fn ($index) => UploadedFile::fake()->image("volunteer-{$index}.jpg"),
            range(1, 11),
        );
        $this->post('/api/admin/voluntariados', [
            'titulo' => 'Voluntariado de prueba',
            'descripcion' => 'Descripción de prueba.',
            'categoria' => 'Conservación',
            'lugarId' => '507f1f77bcf86cd799439012',
            'organizadorId' => '507f1f77bcf86cd799439013',
            'fechaInicio' => '2026-06-01',
            'fechaFin' => '2026-06-01',
            'horaInicio' => '09:00',
            'horaFin' => '10:00',
            'cupo' => 10,
            'imagenes' => $volunteerImages,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('imagenes');
    }
}
