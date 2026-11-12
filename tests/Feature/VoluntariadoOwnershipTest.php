<?php

namespace Tests\Feature;

use App\Models\Asistencia;
use App\Models\User;
use App\Models\Notificacion;
use App\Models\Inscripcion;
use App\Models\Voluntariado;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VoluntariadoOwnershipTest extends TestCase
{
    private ?string $testDatabase = null;

    protected function setUp(): void
    {
        parent::setUp();

        $uri = getenv('MONGODB_TEST_URI');
        if (! $uri) {
            $this->markTestSkipped('Configura MONGODB_TEST_URI con una base MongoDB de pruebas.');
        }

        $this->testDatabase = 'regenera_owner_test_'.bin2hex(random_bytes(6));
        config([
            'database.connections.mongodb.dsn' => $uri,
            'database.connections.mongodb.database' => $this->testDatabase,
        ]);
        DB::purge('mongodb');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        if ($this->testDatabase !== null) {
            DB::connection('mongodb')->getMongoDB()->drop();
            DB::purge('mongodb');
        }

        parent::tearDown();
    }

    public function test_owner_can_read_own_pending_and_rejected_proposals(): void
    {
        $owner = $this->user();
        $pending = $this->voluntariado($owner, 'pendiente');
        $rejected = $this->voluntariado($owner, 'rechazado');
        Sanctum::actingAs($owner);

        $this->getJson('/api/voluntariados/'.$pending->getKey())->assertOk();
        $this->getJson('/api/voluntariados/'.$rejected->getKey())->assertOk();
    }

    public function test_other_users_cannot_read_pending_or_rejected_proposals(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $pending = $this->voluntariado($owner, 'pendiente');
        $rejected = $this->voluntariado($owner, 'rechazado');
        Sanctum::actingAs($other);

        $this->getJson('/api/voluntariados/'.$pending->getKey())->assertNotFound();
        $this->getJson('/api/voluntariados/'.$rejected->getKey())->assertNotFound();
    }

    public function test_owner_list_is_separate_and_public_list_contains_only_other_published_records(): void
    {
        $owner = $this->user();
        $this->voluntariado($owner, 'pendiente');
        $this->voluntariado($owner, 'publicado');
        $this->voluntariado($owner, 'rechazado');
        $publicadoAjeno = $this->voluntariado($this->user(), 'publicado');
        Sanctum::actingAs($owner);

        $response = $this->getJson('/api/voluntariados')->assertOk();

        $response->assertJsonCount(1, 'voluntariados')
            ->assertJsonPath('voluntariados.0.id', (string) $publicadoAjeno->getKey())
            ->assertJsonCount(3, 'misVoluntariados');
    }

    public function test_organizer_sees_own_inactive_events_enrollments_and_other_available_events(): void
    {
        $organizer = $this->user('organizador', true);
        $this->voluntariado($organizer, 'publicado');
        $cancelled = $this->voluntariado($organizer, 'cancelado');
        $finished = $this->voluntariado($organizer, 'finalizado');
        $participantOwner = $this->user();
        $available = $this->voluntariado($participantOwner, 'publicado');
        Inscripcion::create([
            'usuarioId' => (string) $organizer->getAuthIdentifier(),
            'voluntariadoId' => (string) $available->getKey(),
            'claveRegistroActiva' => (string) $organizer->getAuthIdentifier().'|'.$available->getKey(),
            'fechaInscripcion' => now(),
            'estado' => 'inscrita',
        ]);
        Sanctum::actingAs($organizer);

        $this->getJson('/api/voluntariados')->assertOk()
            ->assertJsonCount(1, 'voluntariados')
            ->assertJsonPath('voluntariados.0.id', (string) $available->getKey())
            ->assertJsonCount(3, 'misVoluntariados')
            ->assertJsonCount(1, 'misInscripciones')
            ->assertJsonFragment([
                'id' => (string) $cancelled->getKey(),
                'estado' => 'cancelado',
            ])
            ->assertJsonFragment([
                'id' => (string) $finished->getKey(),
                'estado' => 'finalizado',
            ]);
    }

    public function test_admin_can_read_private_proposals(): void
    {
        $pending = $this->voluntariado($this->user(), 'pendiente');
        $admin = $this->user('admin');
        Sanctum::actingAs($admin);

        $this->getJson('/api/voluntariados/'.$pending->getKey())->assertOk();
        $this->getJson('/api/admin/voluntariados')
            ->assertOk()
            ->assertJsonFragment(['id' => (string) $pending->getKey(), 'estado' => 'pendiente']);
    }

    public function test_admin_personal_volunteers_endpoint_returns_every_volunteer(): void
    {
        $published = $this->voluntariado($this->user(), 'publicado');
        $pending = $this->voluntariado($this->user(), 'pendiente');
        $cancelled = $this->voluntariado($this->user(), 'cancelado');
        $admin = $this->user('admin');
        Sanctum::actingAs($admin);

        $this->getJson('/api/voluntariados')->assertOk()
            ->assertJsonPath('esAdmin', true)
            ->assertJsonCount(3, 'todosVoluntariados')
            ->assertJsonFragment(['id' => (string) $published->getKey(), 'estado' => 'publicado'])
            ->assertJsonFragment(['id' => (string) $pending->getKey(), 'estado' => 'pendiente'])
            ->assertJsonFragment(['id' => (string) $cancelled->getKey(), 'estado' => 'cancelado']);
    }

    public function test_owner_or_admin_can_open_own_private_volunteer_map_point(): void
    {
        $owner = $this->user('organizador', true);
        $private = $this->voluntariado($owner, 'pendiente');
        $private->forceFill([
            'latitud' => 24.3217,
            'longitud' => -110.3248,
        ])->save();

        Sanctum::actingAs($owner);
        $this->getJson('/api/v1/mapa/voluntariados?voluntariado_id='.$private->getKey())
            ->assertOk()
            ->assertJsonPath('data.0.id', (string) $private->getKey());

        Sanctum::actingAs($this->user());
        $this->getJson('/api/v1/mapa/voluntariados?voluntariado_id='.$private->getKey())
            ->assertForbidden();

        Sanctum::actingAs($this->user('admin'));
        $this->getJson('/api/v1/mapa/voluntariados?voluntariado_id='.$private->getKey())
            ->assertOk()
            ->assertJsonPath('data.0.id', (string) $private->getKey());
    }

    public function test_verified_organizer_can_read_own_pending_proposal(): void
    {
        $owner = $this->user('organizador', true);
        $pending = $this->voluntariado($owner, 'pendiente');
        Sanctum::actingAs($owner);

        $this->getJson('/api/voluntariados/'.$pending->getKey())->assertOk();
    }

    public function test_normal_user_submission_is_pending_and_notifies_the_owner(): void
    {
        $owner = $this->user();
        Sanctum::actingAs($owner);

        $this->postJson('/api/voluntariados', [
            'titulo' => 'Propuesta de usuario normal',
            'descripcion' => 'Una propuesta enviada a revisión.',
            'categoria' => 'limpieza',
            'lugarId' => '507f1f77bcf86cd799439011',
            'fechaInicio' => now()->addDays(3)->format('Y-m-d H:i:s'),
            'fechaFin' => now()->addDays(3)->addHours(2)->format('Y-m-d H:i:s'),
            'horaInicio' => '09:00',
            'horaFin' => '11:00',
            'cupo' => 3,
        ])->assertCreated()->assertJsonPath('voluntariado.estado', 'pendiente');

        $this->assertSame(1, Notificacion::where('tipo', 'voluntariado_enviado_revision')->count());
    }

    public function test_registration_after_activity_started_returns_the_exact_message(): void
    {
        $owner = $this->user();
        $voluntariado = Voluntariado::create([
            'titulo' => 'Actividad iniciada',
            'descripcion' => 'Prueba',
            'estado' => 'publicado',
            'organizadorId' => (string) $owner->getAuthIdentifier(),
            'fechaInicio' => now()->subHours(2),
            'fechaFin' => now()->addHours(2),
            'horaInicio' => now()->subHours(2)->format('H:i'),
            'horaFin' => now()->addHours(2)->format('H:i'),
            'cupo' => 3,
        ]);
        $participante = $this->user();
        Sanctum::actingAs($participante);

        $this->postJson('/api/inscripciones', [
            'voluntariadoId' => (string) $voluntariado->getKey(),
        ])->assertStatus(409)->assertJsonPath(
            'message',
            'La actividad ya inició; ya no es posible inscribirse a esta participación',
        );
    }

    public function test_owner_cannot_register_for_own_volunteer(): void
    {
        $owner = $this->user('organizador', true);
        $voluntariado = $this->voluntariado($owner, 'publicado');
        Sanctum::actingAs($owner);

        $this->postJson('/api/inscripciones', [
            'voluntariadoId' => (string) $voluntariado->getKey(),
        ])->assertForbidden()
            ->assertJsonPath('message', 'No puedes inscribirte a un voluntariado que organizas.');
    }

    public function test_owner_can_cancel_own_volunteer_before_it_starts(): void
    {
        $owner = $this->user('organizador', true);
        $voluntariado = $this->voluntariado($owner, 'publicado');
        Sanctum::actingAs($owner);

        $this->patchJson('/api/voluntariados/'.$voluntariado->getKey(), [
            'estado' => 'cancelado',
        ])->assertOk();

        $this->assertSame('cancelado', $voluntariado->fresh()->estado);
    }

    public function test_only_admin_can_manage_or_delete_a_volunteer_after_it_starts(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 12:00:00', 'America/Mazatlan'));
        $owner = $this->user('organizador', true);
        $voluntariado = $this->voluntariado($owner, 'publicado');
        $voluntariado->forceFill([
            'fechaInicio' => Carbon::parse('2026-11-11', 'UTC'),
            'fechaFin' => Carbon::parse('2026-11-11', 'UTC'),
            'horaInicio' => '09:00',
            'horaFin' => '11:00',
        ])->save();
        Sanctum::actingAs($owner);

        $this->patchJson('/api/admin/voluntariados/'.$voluntariado->getKey(), [
            'titulo' => 'Cambio no permitido',
        ])->assertStatus(409);
        $this->patchJson('/api/voluntariados/'.$voluntariado->getKey(), [
            'estado' => 'cancelado',
        ])->assertStatus(409);
        $this->deleteJson('/api/admin/voluntariados/'.$voluntariado->getKey())
            ->assertStatus(409);

        Sanctum::actingAs($this->user('admin'));
        $this->patchJson('/api/admin/voluntariados/'.$voluntariado->getKey(), [
            'titulo' => 'Cambio autorizado por administración',
        ])->assertOk();
        $this->patchJson('/api/admin/voluntariados/'.$voluntariado->getKey(), [
            'estado' => 'cancelado',
        ])->assertOk();
        $this->deleteJson('/api/admin/voluntariados/'.$voluntariado->getKey())
            ->assertOk();
    }

    public function test_normal_owner_can_read_approved_volunteer_activity_but_other_user_cannot(): void
    {
        $owner = $this->user();
        $voluntariado = $this->voluntariado($owner, 'publicado');
        Sanctum::actingAs($owner);
        $this->getJson('/api/admin/voluntariados/'.$voluntariado->getKey().'/dashboard')->assertOk();

        Sanctum::actingAs($this->user());
        $this->getJson('/api/admin/voluntariados/'.$voluntariado->getKey().'/dashboard')->assertForbidden();
    }

    public function test_normal_owner_can_mark_attendance_for_an_enrolled_participant(): void
    {
        $owner = $this->user();
        $voluntariado = Voluntariado::create([
            'titulo' => 'Actividad en curso',
            'descripcion' => 'Prueba',
            'estado' => 'publicado',
            'organizadorId' => (string) $owner->getAuthIdentifier(),
            'fechaInicio' => now()->startOfDay(),
            'fechaFin' => now()->endOfDay(),
            'horaInicio' => '00:00',
            'horaFin' => '23:59',
            'cupo' => 3,
        ]);
        $participant = $this->user();
        $participant->forceFill(['password' => 'test-password'])->save();
        Inscripcion::create([
            'usuarioId' => (string) $participant->getAuthIdentifier(),
            'voluntariadoId' => (string) $voluntariado->getKey(),
            'claveRegistroActiva' => (string) $participant->getAuthIdentifier().'|'.$voluntariado->getKey(),
            'fechaInscripcion' => now(),
            'estado' => 'inscrita',
        ]);
        Sanctum::actingAs($owner);

        $this->postJson('/api/asistencias', [
            'metodo' => 'manual',
            'usuarioId' => (string) $participant->getAuthIdentifier(),
            'voluntariadoId' => (string) $voluntariado->getKey(),
        ])->assertCreated();
    }

    public function test_owner_can_accept_attendance_review_without_removing_registration(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 12:00:00', 'America/Mazatlan'));
        [$owner, $participant, $voluntariado, $inscripcion] = $this->endedEnrolledActivity();
        Sanctum::actingAs($participant);

        $requestResponse = $this->postJson('/api/asistencias/solicitudes', [
            'voluntariadoId' => (string) $voluntariado->getKey(),
        ])->assertCreated();
        $requestId = $requestResponse->json('solicitud.id');

        Sanctum::actingAs($owner);
        $this->patchJson('/api/asistencias/solicitudes/'.$requestId.'/responder', [
            'decision' => 'aceptar',
        ])->assertOk();

        $this->assertSame('inscrita', $inscripcion->fresh()->estado);
        $this->assertSame(1, Asistencia::count());
        $this->assertSame('aceptada', data_get(Notificacion::find($requestId)->datos, 'estadoSolicitud'));
    }

    public function test_owner_can_reject_attendance_review_with_a_reason(): void
    {
        [$owner, $participant, $voluntariado] = $this->endedEnrolledActivity();
        Sanctum::actingAs($participant);
        $requestId = $this->postJson('/api/asistencias/solicitudes', [
            'voluntariadoId' => (string) $voluntariado->getKey(),
        ])->assertCreated()->json('solicitud.id');

        Sanctum::actingAs($owner);
        $this->patchJson('/api/asistencias/solicitudes/'.$requestId.'/responder', [
            'decision' => 'rechazar',
            'motivo' => 'No pudimos verificar tu participación.',
        ])->assertOk();

        $solicitud = Notificacion::find($requestId);
        $this->assertSame('rechazada', data_get($solicitud->datos, 'estadoSolicitud'));
        $this->assertSame('No pudimos verificar tu participación.', data_get($solicitud->datos, 'motivoRespuesta'));
        $this->assertSame(0, Asistencia::count());
    }

    private function voluntariado(User $owner, string $estado): Voluntariado
    {
        return Voluntariado::create([
            'titulo' => 'Propuesta '.$estado,
            'descripcion' => 'Descripción de prueba',
            'estado' => $estado,
            'organizadorId' => (string) $owner->getAuthIdentifier(),
            'fechaInicio' => now()->addDays(3),
            'fechaFin' => now()->addDays(3)->addHours(2),
            'horaInicio' => '09:00',
            'horaFin' => '11:00',
            'cupo' => 5,
        ]);
    }

    private function endedEnrolledActivity(): array
    {
        Carbon::setTestNow(Carbon::parse('2026-11-11 12:00:00', 'America/Mazatlan'));
        $owner = $this->user();
        $participant = $this->user();
        $voluntariado = Voluntariado::create([
            'titulo' => 'Actividad finalizada',
            'descripcion' => 'Prueba de revisión de asistencia',
            'estado' => 'publicado',
            'organizadorId' => (string) $owner->getAuthIdentifier(),
            'fechaInicio' => now()->copy()->startOfDay(),
            'fechaFin' => now()->copy()->startOfDay(),
            'horaInicio' => '09:00',
            'horaFin' => '11:00',
            'cupo' => 3,
        ]);
        $inscripcion = Inscripcion::create([
            'usuarioId' => (string) $participant->getAuthIdentifier(),
            'voluntariadoId' => (string) $voluntariado->getKey(),
            'claveRegistroActiva' => (string) $participant->getAuthIdentifier().'|'.$voluntariado->getKey(),
            'fechaInscripcion' => now()->subDay(),
            'estado' => 'inscrita',
        ]);

        return [$owner, $participant, $voluntariado, $inscripcion];
    }

    private function user(string $rol = 'usuario', bool $verificado = false): User
    {
        $user = new User([
            'rol' => $rol,
            'estado' => 'activo',
            'organizadorVerificado' => $verificado,
        ]);
        $user->setAttribute('_id', bin2hex(random_bytes(12)));

        return $user;
    }
}