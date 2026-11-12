<?php

namespace Tests\Feature;

use App\Models\Inscripcion;
use App\Models\User;
use App\Models\Voluntariado;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InscripcionCupoTest extends TestCase
{
    private ?string $testDatabase = null;

    protected function setUp(): void
    {
        parent::setUp();

        $uri = getenv('MONGODB_TEST_URI');
        if (! $uri) {
            $this->markTestSkipped('Configura MONGODB_TEST_URI con una base MongoDB standalone de pruebas.');
        }

        $this->testDatabase = 'regenera_cupos_test_'.bin2hex(random_bytes(6));
        config([
            'database.connections.mongodb.dsn' => $uri,
            'database.connections.mongodb.database' => $this->testDatabase,
        ]);
        DB::purge('mongodb');
    }

    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow();
        if ($this->testDatabase !== null) {
            DB::connection('mongodb')->getMongoDB()->drop();
            DB::purge('mongodb');
        }

        parent::tearDown();
    }

    public function test_registration_claims_the_last_available_place(): void
    {
        $voluntariado = $this->voluntariado(2, 1);
        $this->inscripcionActiva($voluntariado, $this->usuarioId());

        $this->registrar($voluntariado, $this->usuario())->assertCreated();

        $this->assertSame(2, (int) $voluntariado->fresh()->inscritosActivos);
    }

    public function test_registration_is_rejected_when_there_are_no_places(): void
    {
        $voluntariado = $this->voluntariado(1, 1);
        $this->inscripcionActiva($voluntariado, $this->usuarioId());

        $this->registrar($voluntariado, $this->usuario())->assertStatus(409);

        $this->assertSame(1, (int) $voluntariado->fresh()->inscritosActivos);
    }

    public function test_only_one_of_two_requests_contending_for_the_last_place_succeeds(): void
    {
        $voluntariado = $this->voluntariado(1, 0);

        $this->registrar($voluntariado, $this->usuario())->assertCreated();
        $this->registrar($voluntariado, $this->usuario())->assertStatus(409);

        $this->assertSame(1, (int) $voluntariado->fresh()->inscritosActivos);
    }

    public function test_cancellation_releases_the_place_for_a_new_registration(): void
    {
        $voluntariado = $this->voluntariado(1, 1);
        $usuarioId = $this->usuarioId();
        $inscripcion = $this->inscripcionActiva($voluntariado, $usuarioId);
        $usuario = $this->usuario($usuarioId);
        Sanctum::actingAs($usuario);

        $this->postJson('/api/inscripciones/'.$inscripcion->getKey().'/cancelar')->assertOk();

        $this->assertSame('cancelada', $inscripcion->fresh()->estado);
        $this->assertSame(0, (int) $voluntariado->fresh()->inscritosActivos);
        $this->registrar($voluntariado, $this->usuario())->assertCreated();
        $this->assertSame(1, (int) $voluntariado->fresh()->inscritosActivos);

        Sanctum::actingAs($usuario);
        $this->postJson('/api/inscripciones/'.$inscripcion->getKey().'/cancelar')->assertOk();
        $this->assertSame(1, (int) $voluntariado->fresh()->inscritosActivos);
    }

    public function test_confirming_attendance_does_not_change_registration_state_or_capacity(): void
    {
        $now = now('America/Mazatlan')->setDate(2026, 11, 11)->setTime(12, 0);
        \Carbon\Carbon::setTestNow($now);
        $voluntariado = $this->voluntariado(1, 1);
        $voluntariado->forceFill([
            'fechaInicio' => $now->copy()->startOfDay(),
            'fechaFin' => $now->copy()->startOfDay(),
            'horaInicio' => '11:00',
            'horaFin' => '13:00',
        ])->save();
        $usuarioId = $this->usuarioId();
        $inscripcion = $this->inscripcionActiva($voluntariado, $usuarioId);
        Sanctum::actingAs($this->usuario($usuarioId));

        $this->postJson('/api/asistencias', [
            'metodo' => 'QR',
            'tokenAsistencia' => $voluntariado->codigoCorto,
        ])->assertCreated();

        $this->assertSame('inscrita', $inscripcion->fresh()->estado);
        $this->assertSame(1, (int) $voluntariado->fresh()->inscritosActivos);
    }

    public function test_admin_cannot_reduce_capacity_below_active_registrations(): void
    {
        $voluntariado = $this->voluntariado(3, 2);
        $this->inscripcionActiva($voluntariado, $this->usuarioId());
        $this->inscripcionActiva($voluntariado, $this->usuarioId());
        $admin = $this->usuario(rol: 'admin');
        Sanctum::actingAs($admin);

        $this->patchJson('/api/admin/voluntariados/'.$voluntariado->getKey(), ['cupo' => 1])
            ->assertStatus(409);

        $this->assertSame(3, (int) $voluntariado->fresh()->cupo);
    }

    public function test_repeated_reservation_and_release_do_not_change_the_count_twice(): void
    {
        $voluntariado = $this->voluntariado(1, 0);
        $cupos = app(\App\Services\VoluntariadoCupoService::class);
        $inscripcionId = bin2hex(random_bytes(16));

        $this->assertTrue($cupos->reservar($voluntariado, $inscripcionId));
        $this->assertTrue($cupos->reservar($voluntariado, $inscripcionId));
        $this->assertSame(1, (int) $voluntariado->fresh()->inscritosActivos);
        $this->assertTrue($cupos->liberar($voluntariado, $inscripcionId));
        $this->assertFalse($cupos->liberar($voluntariado, $inscripcionId));
        $this->assertSame(0, (int) $voluntariado->fresh()->inscritosActivos);
    }

    public function test_pending_registration_resumes_without_reserving_twice(): void
    {
        $voluntariado = $this->voluntariado(1, 0);
        $usuario = $this->usuario();
        $usuarioId = (string) $usuario->getAuthIdentifier();
        $inscripcionId = bin2hex(random_bytes(16));
        $inscripcion = new Inscripcion([
            'usuarioId' => $usuarioId,
            'voluntariadoId' => (string) $voluntariado->getKey(),
            'claveRegistroActiva' => $usuarioId.'|'.$voluntariado->getKey(),
            'fechaInscripcion' => now(),
            'estado' => 'reservando',
        ]);
        $inscripcion->setAttribute('_id', $inscripcionId);
        $inscripcion->save();
        app(\App\Services\VoluntariadoCupoService::class)->reservar($voluntariado, $inscripcionId);

        $this->registrar($voluntariado, $usuario)->assertCreated();

        $this->assertSame(1, (int) $voluntariado->fresh()->inscritosActivos);
        $this->assertSame('inscrita', $inscripcion->fresh()->estado);
    }

    private function voluntariado(int $cupo, int $inscritosActivos): Voluntariado
    {
        $fecha = now()->addDay()->startOfDay();
        $voluntariado = Voluntariado::create([
            'titulo' => 'Prueba de cupos',
            'estado' => 'publicado',
            'organizadorId' => $this->usuarioId(),
            'fechaInicio' => $fecha,
            'fechaFin' => $fecha,
            'horaInicio' => '09:00',
            'horaFin' => '17:00',
            'cupo' => $cupo,
        ]);
        $voluntariado->forceFill(['inscritosActivos' => $inscritosActivos])->save();

        return $voluntariado->fresh();
    }

    private function inscripcionActiva(Voluntariado $voluntariado, string $usuarioId): Inscripcion
    {
        $inscripcion = Inscripcion::create([
            'usuarioId' => $usuarioId,
            'voluntariadoId' => (string) $voluntariado->getKey(),
            'claveRegistroActiva' => $usuarioId.'|'.$voluntariado->getKey(),
            'fechaInscripcion' => now(),
            'estado' => 'inscrita',
        ]);
        DB::connection('mongodb')->getMongoDB()->selectCollection('voluntariados')->updateOne(
            ['_id' => $voluntariado->getKey()],
            ['$addToSet' => ['reservasCupo' => (string) $inscripcion->getKey()]],
        );

        return $inscripcion;
    }

    private function registrar(Voluntariado $voluntariado, User $usuario)
    {
        Sanctum::actingAs($usuario);

        return $this->postJson('/api/inscripciones', [
            'voluntariadoId' => (string) $voluntariado->getKey(),
        ]);
    }

    private function usuario(?string $id = null, string $rol = 'usuario'): User
    {
        $usuario = new User(['rol' => $rol, 'estado' => 'activo']);
        $usuario->setAttribute('_id', $id ?? $this->usuarioId());

        return $usuario;
    }

    private function usuarioId(): string
    {
        return bin2hex(random_bytes(12));
    }
}