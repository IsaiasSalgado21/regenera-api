<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\Cuestionario;
use App\Models\EventoGamificacion;
use App\Models\Inscripcion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileProgressTest extends TestCase
{
    private ?string $testDatabase = null;

    protected function setUp(): void
    {
        parent::setUp();

        $uri = getenv('MONGODB_TEST_URI');
        if (! $uri) {
            $this->markTestSkipped('Configura MONGODB_TEST_URI con una base MongoDB de pruebas.');
        }

        $this->testDatabase = 'regenera_profile_test_'.bin2hex(random_bytes(6));
        config([
            'database.connections.mongodb.dsn' => $uri,
            'database.connections.mongodb.database' => $this->testDatabase,
        ]);
        DB::purge('mongodb');
    }

    protected function tearDown(): void
    {
        if ($this->testDatabase !== null) {
            DB::connection('mongodb')->getMongoDB()->drop();
            DB::purge('mongodb');
        }

        parent::tearDown();
    }

    public function test_progress_returns_real_earned_and_locked_medals_for_the_authenticated_user(): void
    {
        $this->quiz('seguridad-limpieza', 'Seguridad', 'seguro', 1);
        $this->quiz('seguridad-limpieza', 'Seguridad actualizada', 'seguro', 2);
        $this->quiz('restauracion-entorno', 'Restauración', 'restauracion', 1);
        $this->quiz('organizacion-responsable', 'Organización', 'lider', 1, ['organizador'], true);
        EventoGamificacion::create([
            'usuarioId' => 'usuario-actual',
            'tipo' => 'medalla',
            'referenciaId' => 'seguro',
            'creadoEn' => now(),
        ]);
        EventoGamificacion::create([
            'usuarioId' => 'otro-usuario',
            'tipo' => 'medalla',
            'referenciaId' => 'restauracion',
            'creadoEn' => now(),
        ]);
        Sanctum::actingAs($this->user('usuario-actual'));

        $response = $this->getJson('/api/gamificacion/progreso')->assertOk();
        $medals = collect($response->json('data.medallas'))->keyBy('slug');

        $this->assertCount(3, $medals);
        $this->assertTrue($medals['seguro']['desbloqueada']);
        $this->assertSame('Aprobar: Seguridad actualizada', $medals['seguro']['requisito']);
        $this->assertNotNull($medals['seguro']['fechaObtencion']);
        $this->assertFalse($medals['restauracion']['desbloqueada']);
        $this->assertContains('Tener una asistencia confirmada en una actividad finalizada con inscripción válida.', $medals['restauracion']['requisitos']);
        $this->assertFalse($medals['lider']['disponibleParaRol']);
        $this->assertContains('Tener una cuenta de organizador verificada.', $medals['lider']['requisitos']);
    }

    public function test_enrollments_and_attendances_are_scoped_to_the_authenticated_user(): void
    {
        foreach (['usuario-actual', 'otro-usuario'] as $id) {
            Inscripcion::create(['usuarioId' => $id, 'voluntariadoId' => 'actividad-'.$id, 'estado' => 'inscrita', 'fechaInscripcion' => now()]);
            Asistencia::create(['usuarioId' => $id, 'voluntariadoId' => 'actividad-'.$id, 'confirmada' => true, 'fecha' => now()]);
        }
        $user = $this->user('usuario-actual');
        Sanctum::actingAs($user);

        $enrollments = $this->getJson('/api/inscripciones')->assertOk()->json('inscripciones');
        Sanctum::actingAs($user);
        $attendances = $this->getJson('/api/asistencias')->assertOk()->json('asistencias');
        $this->assertCount(1, $enrollments);
        $this->assertSame('usuario-actual', $enrollments[0]['usuarioId']);
        $this->assertCount(1, $attendances);
        $this->assertSame('usuario-actual', $attendances[0]['usuarioId']);
    }

    private function user(string $id): User
    {
        $user = new User(['rol' => 'usuario', 'estado' => 'activo']);
        $user->setAttribute('_id', $id);

        return $user;
    }

    private function quiz(string $slug, string $title, string $medalSlug, int $version, array $audience = ['usuario'], bool $verified = false): void
    {
        Cuestionario::create([
            'slug' => $slug,
            'version' => $version,
            'titulo' => $title,
            'descripcion' => 'Descripción de prueba',
            'audiencia' => $audience,
            'requiereOrganizadorVerificado' => $verified,
            'activo' => true,
            'medalla' => ['slug' => $medalSlug, 'nombre' => $title, 'descripcion' => 'Medalla de prueba'],
            'preguntas' => [],
        ]);
    }
}
