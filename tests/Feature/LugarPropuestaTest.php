<?php

namespace Tests\Feature;

use App\Models\Lugar;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\ObjectId;
use Tests\TestCase;

class LugarPropuestaTest extends TestCase
{
    private ?string $database = null;

    protected function setUp(): void
    {
        parent::setUp();
        $uri = getenv('MONGODB_TEST_URI');
        if (! $uri) {
            $this->markTestSkipped('Configura MONGODB_TEST_URI con una base MongoDB de pruebas.');
        }
        $this->database = 'regenera_lugar_test_'.bin2hex(random_bytes(6));
        config(['database.connections.mongodb.dsn' => $uri, 'database.connections.mongodb.database' => $this->database]);
        DB::purge('mongodb');
    }

    protected function tearDown(): void
    {
        if ($this->database !== null) {
            DB::connection('mongodb')->getMongoDB()->drop();
            DB::purge('mongodb');
        }
        parent::tearDown();
    }

    private function user(string $role, bool $verified = false): User
    {
        return User::create([
            'nombre' => 'Cuenta de prueba',
            'correo' => bin2hex(random_bytes(6)).'@example.test',
            'password' => 'password123',
            'rol' => $role,
            'organizadorVerificado' => $verified,
            'estado' => 'activo',
            'perfilCompleto' => true,
        ]);
    }

    private function proposal(): array
    {
        return ['nombre' => 'Lugar de prueba', 'direccion' => 'La Paz', 'latitud' => 24.1426, 'longitud' => -110.3128];
    }

    public function test_permissions_and_boundary_are_enforced(): void
    {
        $this->postJson('/api/lugares', $this->proposal())->assertUnauthorized();
        foreach ([$this->user('usuario'), $this->user('organizador')] as $user) {
            $this->withToken($user->createToken('test')->plainTextToken);
            $this->postJson('/api/lugares', $this->proposal())->assertForbidden();
        }
        $this->withToken($this->user('organizador', true)->createToken('test')->plainTextToken);
        $this->postJson('/api/lugares', [...$this->proposal(), 'latitud' => 32.5149, 'longitud' => -117.0382])
            ->assertUnprocessable()->assertJsonValidationErrors(['latitud', 'longitud']);
        $this->postJson('/api/lugares', [...$this->proposal(), 'latitud' => null])
            ->assertUnprocessable()->assertJsonValidationErrors('latitud');
    }

    public function test_verified_organizer_publishes_place_and_first_volunteer_without_quiz(): void
    {
        $organizer = $this->user('organizador', true);
        $organizerToken = $organizer->createToken('test')->plainTextToken;
        $this->withToken($organizerToken);
        $id = $this->postJson('/api/lugares', $this->proposal())
            ->assertCreated()->assertJsonPath('data.estado', 'activo')->json('data.id');
        $this->assertSame((string) $organizer->getKey(), (string) Lugar::findOrFail($id)->creadoPor);
        $this->getJson('/api/lugares')->assertOk()->assertJsonFragment(['id' => $id]);
        $this->getJson('/api/v1/mapa/lugares')->assertOk()->assertJsonFragment(['id' => $id]);
        $volunteer = [
            'titulo' => 'Actividad', 'descripcion' => 'Descripción', 'categoria' => 'limpieza',
            'lugarId' => $id, 'fechaInicio' => '2030-01-01', 'fechaFin' => '2030-01-01',
            'horaInicio' => '09:00', 'horaFin' => '10:00', 'cupo' => 10,
        ];
        $this->postJson('/api/admin/voluntariados', $volunteer)
            ->assertCreated()->assertJsonPath('data.estado', 'publicado');
        $this->withToken($this->user('organizador', true)->createToken('test')->plainTextToken);
        $this->postJson('/api/voluntariados', $volunteer)
            ->assertCreated()->assertJsonPath('voluntariado.estado', 'publicado');
    }

    public function test_existing_pending_proposals_still_require_approval(): void
    {
        $admin = $this->user('admin');
        $organizer = $this->user('organizador', true);
        $id = (string) Lugar::create([
            ...$this->proposal(), 'tipo' => 'otro', 'estado' => 'pendiente_de_aprobacion',
            'propuesto' => true, 'creadoPor' => new ObjectId((string) $organizer->getKey()),
        ])->getKey();
        $organizerToken = $organizer->createToken('test')->plainTextToken;
        $this->withToken($organizerToken);
        $this->getJson('/api/v1/mapa/lugares')->assertOk()->assertJsonMissing(['id' => $id]);
        $this->postJson('/api/voluntariados', [
            'titulo' => 'Actividad', 'descripcion' => 'Descripción', 'categoria' => 'limpieza',
            'lugarId' => $id, 'fechaInicio' => '2030-01-01', 'fechaFin' => '2030-01-01',
            'horaInicio' => '09:00', 'horaFin' => '10:00', 'cupo' => 10,
        ])->assertUnprocessable()->assertJsonValidationErrors('lugarId');
        $this->patchJson("/api/admin/lugares/{$id}/aprobar")->assertForbidden();

        $this->withToken($admin->createToken('test')->plainTextToken);
        $this->getJson('/api/admin/lugares')->assertOk()->assertJsonFragment(['id' => $id]);
        $this->getJson('/api/lugares')->assertOk()->assertJsonMissing(['id' => $id]);
        $this->patchJson("/api/admin/lugares/{$id}/aprobar")->assertOk()->assertJsonPath('data.estado', 'activo');
        $this->getJson('/api/lugares')->assertOk()->assertJsonFragment(['id' => $id]);
        $this->getJson('/api/v1/mapa/lugares')->assertOk()->assertJsonFragment(['id' => $id]);

        $this->withToken($organizerToken);
        $this->putJson("/api/lugares/{$id}", $this->proposal())->assertForbidden();
    }

    public function test_unverified_organizer_volunteer_still_requires_review(): void
    {
        $place = Lugar::create([
            ...$this->proposal(), 'tipo' => 'otro', 'estado' => 'activo',
        ]);
        $this->withToken($this->user('organizador')->createToken('test')->plainTextToken);
        $this->postJson('/api/voluntariados', [
            'titulo' => 'Actividad', 'descripcion' => 'Descripción', 'categoria' => 'limpieza',
            'lugarId' => (string) $place->getKey(), 'fechaInicio' => '2030-01-01', 'fechaFin' => '2030-01-01',
            'horaInicio' => '09:00', 'horaFin' => '10:00', 'cupo' => 10,
        ])->assertCreated()->assertJsonPath('voluntariado.estado', 'pendiente');
    }

    public function test_rejected_proposal_retains_reason_and_remains_unpublished(): void
    {
        $admin = $this->user('admin');
        $organizer = $this->user('organizador', true);
        $id = (string) Lugar::create([
            ...$this->proposal(), 'tipo' => 'otro', 'estado' => 'pendiente_de_aprobacion',
            'propuesto' => true, 'creadoPor' => new ObjectId((string) $organizer->getKey()),
        ])->getKey();
        $this->withToken($admin->createToken('test')->plainTextToken);
        $this->patchJson("/api/admin/lugares/{$id}/rechazar", [])->assertUnprocessable()->assertJsonValidationErrors('motivo');
        $this->patchJson("/api/admin/lugares/{$id}/rechazar", ['motivo' => 'Ubicación incorrecta'])
            ->assertOk()->assertJsonPath('data.estado', 'rechazado');
        $this->patchJson("/api/admin/lugares/{$id}/archivar")->assertUnprocessable();
        $this->patchJson("/api/admin/lugares/{$id}/aprobar")->assertForbidden();
        $this->assertSame('Ubicación incorrecta', Lugar::findOrFail($id)->motivoRechazo);
        $this->getJson('/api/v1/mapa/lugares')->assertOk()->assertJsonMissing(['id' => $id]);
        $this->getJson('/api/lugares')->assertOk()->assertJsonMissing(['id' => $id]);
    }
}
