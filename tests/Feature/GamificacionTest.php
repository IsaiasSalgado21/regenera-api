<?php

namespace Tests\Feature;

use App\Models\EventoGamificacion;
use App\Models\Cuestionario;
use App\Models\IntentoCuestionario;
use App\Models\Lugar;
use App\Models\User;
use App\Models\Voluntariado;
use Carbon\Carbon;
use Database\Seeders\GamificacionSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GamificacionTest extends TestCase
{
    private ?string $testDatabase = null;

    protected function setUp(): void
    {
        parent::setUp();

        $uri = getenv('MONGODB_TEST_URI');
        if (! $uri) {
            $this->markTestSkipped('Configura MONGODB_TEST_URI con una base MongoDB standalone de pruebas.');
        }

        $this->testDatabase = 'regenera_gamificacion_test_'.bin2hex(random_bytes(6));
        config([
            'database.connections.mongodb.dsn' => $uri,
            'database.connections.mongodb.database' => $this->testDatabase,
        ]);
        DB::purge('mongodb');
        (new GamificacionSeeder)->run();
        $indexes = iterator_to_array(DB::connection('mongodb')->getDatabase()
            ->selectCollection('eventos_gamificacion')
            ->listIndexes());
        $uniqueIndex = collect($indexes)->first(fn ($index) => $index->getName() === 'eventos_gamificacion_clave_unique');
        $this->assertNotNull($uniqueIndex);
        $this->assertTrue($uniqueIndex->isUnique());
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

    public function test_catalog_hides_correct_answers_and_is_filtered_by_role(): void
    {
        $usuario = $this->user('usuario');
        $this->withToken($usuario->createToken('gamification-test')->plainTextToken);

        $response = $this->getJson('/api/cuestionarios')->assertOk();
        $catalog = $response->json('data');
        $payload = json_encode($catalog, JSON_THROW_ON_ERROR);

        $this->assertCount(3, $catalog);
        $this->assertStringNotContainsString('respuestaCorrecta', $payload);
        $this->assertStringNotContainsString('explicacion', $payload);
    }

    public function test_unverified_organizer_cannot_start_the_organizer_questionnaire(): void
    {
        $organizador = $this->user('organizador', false);
        $this->withToken($organizador->createToken('unverified-organizer')->plainTextToken);

        $catalog = $this->getJson('/api/cuestionarios')->assertOk()->json('data');
        $this->assertNotContains('organizacion-responsable', array_column($catalog, 'slug'));
        $this->postJson('/api/cuestionarios/organizacion-responsable/intentos')
            ->assertForbidden();
    }

    public function test_admin_statistics_are_private_and_never_include_answer_keys(): void
    {
        $usuario = $this->user('usuario');
        $this->withToken($usuario->createToken('ordinary-user')->plainTextToken);
        $this->getJson('/api/admin/gamificacion/estadisticas')->assertForbidden();

        $admin = $this->user('admin');
        $this->withToken($admin->createToken('admin-stats')->plainTextToken);
        $response = $this->getJson('/api/admin/gamificacion/estadisticas')->assertOk();
        $this->assertCount(4, $response->json('data.cuestionarios'));
        $this->assertStringNotContainsString('respuestaCorrecta', json_encode($response->json(), JSON_THROW_ON_ERROR));
    }

    public function test_admin_can_create_update_and_deactivate_versioned_quizzes(): void
    {
        $admin = $this->user('admin');
        $this->withToken($admin->createToken('quiz-crud-test')->plainTextToken);
        $payload = $this->quizPayload();

        $created = $this->postJson('/api/admin/cuestionarios', $payload)
            ->assertCreated()
            ->assertJsonPath('data.version', 1)
            ->json('data');
        $this->assertSame('Guía de preparación.', $created['guia']);

        $updatedPayload = $this->quizPayload();
        $updatedPayload['titulo'] = 'Cuestionario de prueba editado';
        $updated = $this->putJson('/api/admin/cuestionarios/qa-cuestionario', $updatedPayload)
            ->assertOk()
            ->assertJsonPath('data.version', 2)
            ->json('data');
        $this->assertSame('Cuestionario de prueba editado', $updated['titulo']);
        $this->assertFalse((bool) Cuestionario::query()->where('slug', 'qa-cuestionario')->where('version', 1)->first()->activo);

        $this->deleteJson('/api/admin/cuestionarios/qa-cuestionario')->assertOk();
        $latest = Cuestionario::query()->where('slug', 'qa-cuestionario')->orderByDesc('version')->first();
        $this->assertFalse((bool) $latest->activo);
    }

    public function test_admin_quiz_rejects_answer_index_outside_options(): void
    {
        $admin = $this->user('admin');
        $this->withToken($admin->createToken('quiz-validation-test')->plainTextToken);
        $payload = $this->quizPayload();
        $payload['preguntas'][0]['respuestaCorrecta'] = 2;

        $this->postJson('/api/admin/cuestionarios', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('preguntas.0.respuestaCorrecta');
    }

    public function test_progress_repairs_legacy_reward_documents_with_empty_date_fields(): void
    {
        $usuario = $this->user('usuario');
        $collection = DB::connection('mongodb')->getDatabase()->selectCollection('eventos_gamificacion');
        $collection->insertOne([
            'usuarioId' => (string) $usuario->getAuthIdentifier(),
            'tipo' => 'puntos',
            'cantidad' => 5,
            'motivo' => 'Puntos históricos',
            'referenciaTipo' => 'voluntariado',
            'referenciaId' => 'legacy-event',
            'claveUnica' => 'legacy:'.$usuario->getAuthIdentifier(),
            'creadoEn' => new \stdClass,
            'created_at' => new \stdClass,
            'updated_at' => new \stdClass,
        ]);
        $this->withToken($usuario->createToken('legacy-progress-test')->plainTextToken);

        $this->getJson('/api/gamificacion/progreso')
            ->assertOk()
            ->assertJsonPath('data.puntos', 5);

        $stored = $collection->findOne(['claveUnica' => 'legacy:'.$usuario->getAuthIdentifier()]);
        $this->assertIsObject($stored['creadoEn']);
        $this->assertTrue(method_exists($stored['creadoEn'], 'toDateTime'));
    }

    public function test_first_registration_is_blocked_until_security_quiz_is_approved(): void
    {
        $usuario = $this->user('usuario');
        $organizador = $this->user('organizador', true);
        $voluntariado = $this->voluntariado($organizador);
        $this->withToken($usuario->createToken('gamification-test')->plainTextToken);

        $this->postJson('/api/inscripciones', ['voluntariadoId' => (string) $voluntariado->_id])
            ->assertStatus(428)
            ->assertJsonPath('code', 'cuestionario_seguridad_requerido');

        $attempt = $this->postJson('/api/cuestionarios/seguridad-limpieza/intentos')
            ->assertCreated()
            ->json('data');
        foreach ($attempt['preguntas'] as $question) {
            $answerResponse = $this->postJson('/api/cuestionarios/intentos/'.$attempt['intentoId'].'/respuestas', [
                'preguntaId' => $question['id'],
                'opcion' => $this->correctOptionShown($attempt['intentoId'], $question['id']),
            ]);
            $this->assertSame(200, $answerResponse->status(), $answerResponse->getContent());
            $result = $answerResponse->json('data');
        }

        $this->assertSame('aprobado', $result['estado']);
        $this->postJson('/api/inscripciones', ['voluntariadoId' => (string) $voluntariado->_id])
            ->assertCreated();
    }

    public function test_replaying_a_passed_answer_does_not_duplicate_points_or_medals(): void
    {
        $usuario = $this->user('usuario');
        $this->withToken($usuario->createToken('gamification-test')->plainTextToken);
        $attempt = $this->postJson('/api/cuestionarios/economia-circular/intentos')
            ->assertCreated()
            ->json('data');

        $lastResult = null;
        foreach ($attempt['preguntas'] as $question) {
            $answerResponse = $this->postJson('/api/cuestionarios/intentos/'.$attempt['intentoId'].'/respuestas', [
                'preguntaId' => $question['id'],
                'opcion' => $this->correctOptionShown($attempt['intentoId'], $question['id']),
            ]);
            $this->assertSame(200, $answerResponse->status(), $answerResponse->getContent());
            $lastResult = $answerResponse->json('data');
        }
        $this->assertSame('aprobado', $lastResult['estado'] ?? null, json_encode($lastResult));
        $lastQuestion = end($attempt['preguntas']);
        $this->postJson('/api/cuestionarios/intentos/'.$attempt['intentoId'].'/respuestas', [
            'preguntaId' => $lastQuestion['id'],
            'opcion' => $this->correctOptionShown($attempt['intentoId'], $lastQuestion['id']),
        ])->assertOk();

        $rawEvents = iterator_to_array(DB::connection('mongodb')->getDatabase()
            ->selectCollection('eventos_gamificacion')
            ->find(['usuarioId' => (string) $usuario->getAuthIdentifier()]));
        $this->assertCount(2, $rawEvents, json_encode($rawEvents));
        $this->assertSame(2, EventoGamificacion::query()->count(), json_encode($rawEvents));

        $pointEvents = EventoGamificacion::query()
            ->where('usuarioId', (string) $usuario->getAuthIdentifier())
            ->where('tipo', 'puntos')
            ->get(['cantidad', 'claveUnica']);
        $this->assertSame(20, (int) $pointEvents->sum('cantidad'), json_encode($pointEvents->toArray()));
        $this->assertSame(1, EventoGamificacion::query()
            ->where('usuarioId', (string) $usuario->getAuthIdentifier())
            ->where('tipo', 'medalla')
            ->count());
        $this->getJson('/api/gamificacion/progreso')
            ->assertOk()
            ->assertJsonPath('data.puntos', 20)
            ->assertJsonPath('data.medallas.2.desbloqueada', true);
    }

    public function test_confirmed_attendance_awards_points_once_but_registration_alone_does_not(): void
    {
        $usuario = $this->user('usuario');
        $organizador = $this->user('organizador', true);
        $voluntariado = $this->voluntariado($organizador);
        $service = app(\App\Services\GamificacionService::class);

        $this->assertSame(0, EventoGamificacion::query()->count());
        \App\Models\Inscripcion::create([
            'usuarioId' => (string) $usuario->getAuthIdentifier(),
            'voluntariadoId' => (string) $voluntariado->_id,
            'estado' => 'inscrita',
        ]);
        $this->assertSame(0, EventoGamificacion::query()->count());
        \App\Models\Asistencia::create([
            'usuarioId' => (string) $usuario->getAuthIdentifier(),
            'voluntariadoId' => (string) $voluntariado->_id,
            'confirmada' => true,
            'metodo' => 'QR',
            'fecha' => now(),
            'hora' => now()->format('H:i:s'),
        ]);

        $this->assertTrue($service->registrarParticipacion($usuario, $voluntariado));
        $this->assertFalse($service->registrarParticipacion($usuario, $voluntariado));
        $this->assertSame(50, (int) EventoGamificacion::query()->sum('cantidad'));
    }

    public function test_restoration_quiz_requires_confirmed_attendance_at_a_finished_activity(): void
    {
        $usuario = $this->user('usuario');
        $organizador = $this->user('organizador', true);
        $voluntariado = $this->voluntariado($organizador);
        $date = now('America/Mazatlan')->subDay()->startOfDay();
        $voluntariado->forceFill([
            'estado' => 'finalizado',
            'fechaInicio' => Carbon::parse($date->format('Y-m-d'), 'UTC'),
            'fechaFin' => Carbon::parse($date->format('Y-m-d'), 'UTC'),
            'horaInicio' => '08:00',
            'horaFin' => '10:00',
        ])->save();
        $this->withToken($usuario->createToken('restoration-test')->plainTextToken);

        $catalog = $this->getJson('/api/cuestionarios')->assertOk()->json('data');
        $restoration = collect($catalog)->firstWhere('slug', 'restauracion-entorno');
        $this->assertFalse($restoration['disponible']);
        $this->postJson('/api/cuestionarios/restauracion-entorno/intentos', [
            'actividadId' => (string) $voluntariado->_id,
        ])->assertForbidden();

        \App\Models\Inscripcion::create([
            'usuarioId' => (string) $usuario->getAuthIdentifier(),
            'voluntariadoId' => (string) $voluntariado->_id,
            'estado' => 'inscrita',
        ]);
        \App\Models\Asistencia::create([
            'usuarioId' => (string) $usuario->getAuthIdentifier(),
            'voluntariadoId' => (string) $voluntariado->_id,
            'confirmada' => true,
            'metodo' => 'manual',
            'fecha' => $date,
            'hora' => '08:00:00',
        ]);

        $catalog = $this->getJson('/api/cuestionarios')->assertOk()->json('data');
        $restoration = collect($catalog)->firstWhere('slug', 'restauracion-entorno');
        $this->assertTrue($restoration['disponible']);
        $this->assertSame((string) $voluntariado->_id, $restoration['actividadesElegibles'][0]['id']);
        $this->postJson('/api/cuestionarios/restauracion-entorno/intentos', [
            'actividadId' => (string) $voluntariado->_id,
        ])->assertCreated();
    }

    public function test_verified_organizer_must_pass_certification_before_first_publication(): void
    {
        $organizador = $this->user('organizador', true);
        $lugar = Lugar::create([
            'nombre' => 'Lugar de prueba de gamificación',
            'municipio' => 'La Paz',
            'direccion' => 'Zona de prueba',
            'latitud' => 24.14,
            'longitud' => -110.31,
            'tipo' => 'playa',
            'estado' => 'activo',
            'creadoPor' => (string) $organizador->getAuthIdentifier(),
        ]);
        $this->withToken($organizador->createToken('organizer-certification-test')->plainTextToken);
        $activity = $this->activityPayload($lugar);

        $this->postJson('/api/voluntariados', $activity)
            ->assertStatus(428)
            ->assertJsonPath('code', 'certificacion_organizador_requerida');

        $attempt = $this->postJson('/api/cuestionarios/organizacion-responsable/intentos')
            ->assertCreated()
            ->json('data');
        foreach ($attempt['preguntas'] as $question) {
            $this->postJson('/api/cuestionarios/intentos/'.$attempt['intentoId'].'/respuestas', [
                'preguntaId' => $question['id'],
                'opcion' => $this->correctOptionShown($attempt['intentoId'], $question['id']),
            ])->assertOk();
        }

        $this->postJson('/api/voluntariados', $activity)
            ->assertCreated()
            ->assertJsonPath('voluntariado.estado', 'publicado');
    }

    private function user(string $role, bool $verified = false): User
    {
        return User::create([
            'nombre' => 'Gamificacion '.$role,
            'correo' => 'gamificacion.'.$role.'@test.local',
            'password' => Hash::make('Test12345!'),
            'rol' => $role,
            'organizadorVerificado' => $verified,
            'fechaRegistro' => now(),
            'estado' => 'activo',
        ]);
    }

    private function voluntariado(User $organizador): Voluntariado
    {
        $date = now('America/Mazatlan')->addDay()->startOfDay();

        return Voluntariado::create([
            'titulo' => 'Actividad de prueba de gamificación',
            'categoria' => 'limpieza-de-playas',
            'organizadorId' => (string) $organizador->getAuthIdentifier(),
            'lugarId' => 'lugar-test',
            'fechaInicio' => Carbon::parse($date->format('Y-m-d'), 'UTC'),
            'fechaFin' => Carbon::parse($date->format('Y-m-d'), 'UTC'),
            'horaInicio' => '08:00',
            'horaFin' => '10:00',
            'cupo' => 5,
            'estado' => 'publicado',
        ]);
    }

    private function activityPayload(Lugar $lugar): array
    {
        $date = now('America/Mazatlan')->addDays(3)->toDateString();

        return [
            'titulo' => 'Actividad de prueba de certificación',
            'descripcion' => 'Actividad de prueba para validar el cuestionario obligatorio de organizadores.',
            'categoria' => 'limpieza-de-playas',
            'lugarId' => (string) $lugar->_id,
            'fechaInicio' => $date,
            'fechaFin' => $date,
            'horaInicio' => '08:00',
            'horaFin' => '10:00',
            'cupo' => 10,
            'requisitos' => [],
            'materiales' => [],
        ];
    }

    private function correctOptionShown(string $attemptId, string $questionId): int
    {
        $attempt = IntentoCuestionario::find($attemptId);
        $quiz = Cuestionario::query()
            ->where('slug', $attempt->cuestionarioSlug)
            ->where('version', $attempt->cuestionarioVersion)
            ->first();
        $question = collect($quiz->preguntas)->firstWhere('id', $questionId);
        $order = $attempt->opcionesOrden[$questionId];

        return (int) array_search((int) $question['respuestaCorrecta'], $order, true);
    }

    private function quizPayload(): array
    {
        return [
            'slug' => 'qa-cuestionario',
            'titulo' => 'Cuestionario de prueba',
            'descripcion' => 'Prueba de CRUD versionado.',
            'guia' => 'Guía de preparación.',
            'audiencia' => ['usuario'],
            'requiereOrganizadorVerificado' => false,
            'minimoAprobacion' => 80,
            'puntosAprobacion' => 15,
            'preguntas' => [[
                'id' => 'pregunta-unica',
                'texto' => '¿Cuál es la respuesta correcta?',
                'opciones' => ['La primera', 'La segunda'],
                'respuestaCorrecta' => 0,
                'explicacion' => 'Esta explicación enseña el concepto.',
            ]],
            'medalla' => [
                'slug' => 'medalla-qa',
                'nombre' => 'Medalla QA',
                'descripcion' => 'Medalla de prueba.',
                'icono' => 'award',
            ],
        ];
    }
}