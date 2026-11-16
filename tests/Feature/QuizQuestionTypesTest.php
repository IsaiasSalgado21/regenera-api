<?php

namespace Tests\Feature;

use App\Models\Cuestionario;
use App\Models\IntentoCuestionario;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuizQuestionTypesTest extends TestCase
{
    private ?string $database = null;

    protected function setUp(): void
    {
        parent::setUp();
        $uri = getenv('MONGODB_TEST_URI');
        if (! $uri) {
            $this->markTestSkipped('Configura MONGODB_TEST_URI con una base MongoDB de pruebas.');
        }
        $this->database = 'regenera_quiz_types_test_'.bin2hex(random_bytes(6));
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

    public function test_new_question_types_are_scored_and_resumed_with_the_original_version(): void
    {
        $user = new User(['rol' => 'usuario', 'estado' => 'activo']);
        $user->setAttribute('_id', 'test-user');
        Sanctum::actingAs($user);
        $quiz = [
            'slug' => 'test', 'version' => 1, 'titulo' => 'Versión original',
            'descripcion' => 'Descripción', 'audiencia' => ['usuario'], 'activo' => true,
            'minimoAprobacion' => 60, 'puntosAprobacion' => 0,
            'preguntas' => [
                ['id' => 'multiple', 'tipo' => 'multiple', 'peso' => 3, 'texto' => 'Elige',
                    'opciones' => ['A', 'B', 'C'], 'respuestasCorrectas' => [0, 1], 'explicacion' => 'Explicación'],
                ['id' => 'corta', 'tipo' => 'corta', 'peso' => 1, 'texto' => 'Escribe',
                    'respuestasAceptadas' => ['Bahía Magdalena'], 'explicacion' => 'Explicación'],
            ],
        ];
        Cuestionario::create($quiz);
        $start = $this->postJson('/api/cuestionarios/test/intentos', [])->assertCreated()->json('data');
        $attempt = IntentoCuestionario::findOrFail($start['intentoId']);
        Cuestionario::where('slug', 'test')->where('version', 1)->update(['activo' => false]);
        $quiz['version'] = 2;
        $quiz['titulo'] = 'Versión nueva';
        $quiz['preguntas'] = [['id' => 'otra', 'texto' => 'Otra', 'opciones' => ['A', 'B'], 'respuestaCorrecta' => 0, 'explicacion' => 'Explicación']];
        Cuestionario::create($quiz);

        Sanctum::actingAs($user);
        $resume = $this->postJson('/api/cuestionarios/test/intentos', [])->assertCreated()->json('data');
        $this->assertSame('Versión original', $resume['cuestionario']['titulo']);
        $this->assertSame([], $resume['preguntas'][1]['opciones']);
        $order = $attempt->opcionesOrden['multiple'];
        $firstCorrect = array_search(0, $order, true);
        Sanctum::actingAs($user);
        $this->postJson('/api/cuestionarios/intentos/'.$start['intentoId'].'/respuestas', [
            'preguntaId' => 'multiple', 'respuesta' => [$firstCorrect, $firstCorrect],
        ])->assertUnprocessable();
        Sanctum::actingAs($user);
        $this->postJson('/api/cuestionarios/intentos/'.$start['intentoId'].'/respuestas', [
            'preguntaId' => 'multiple', 'respuesta' => [$firstCorrect],
        ])->assertOk()->assertJsonPath('data.retroalimentacion.credito', 0.5);
        Sanctum::actingAs($user);
        $result = $this->postJson('/api/cuestionarios/intentos/'.$start['intentoId'].'/respuestas', [
            'preguntaId' => 'corta', 'respuesta' => '  BAHIA  MAGDALENA ',
        ])->assertOk()->assertJsonPath('data.estado', 'aprobado');
        $this->assertEquals(62.5, $result->json('data.puntuacion'));
        $this->assertSame(1, $result->json('data.preguntasCorrectas'));
    }

    public function test_admin_can_save_new_types_and_reject_invalid_answers(): void
    {
        $admin = new User(['rol' => 'admin', 'estado' => 'activo']);
        $admin->setAttribute('_id', 'test-admin');
        $payload = [
            'slug' => 'nuevo', 'titulo' => 'Nuevo', 'descripcion' => 'Descripción',
            'guia' => 'Guía', 'audiencia' => ['usuario'], 'minimoAprobacion' => 60,
            'puntosAprobacion' => 5,
            'medalla' => ['slug' => 'medalla', 'nombre' => 'Medalla', 'descripcion' => 'Descripción', 'icono' => 'leader'],
            'preguntas' => [
                ['id' => 'multiple', 'tipo' => 'multiple', 'peso' => 3, 'texto' => 'Elige',
                    'opciones' => ['A', 'B', 'C'], 'respuestasCorrectas' => [0, 1], 'explicacion' => 'Explicación'],
                ['id' => 'corta', 'tipo' => 'corta', 'peso' => 1, 'texto' => 'Escribe',
                    'respuestasAceptadas' => ['Bahía Magdalena'], 'explicacion' => 'Explicación'],
                ['id' => 'vf', 'tipo' => 'verdadero_falso', 'peso' => 1, 'texto' => '¿Sí?',
                    'opciones' => ['Verdadero', 'Falso'], 'respuestaCorrecta' => 0, 'explicacion' => 'Explicación'],
            ],
        ];
        Sanctum::actingAs($admin);
        $this->postJson('/api/admin/cuestionarios', $payload)->assertCreated();
        $stored = Cuestionario::where('slug', 'nuevo')->firstOrFail();
        $this->assertSame([0, 1], $stored->preguntas[0]['respuestasCorrectas']);
        $this->assertSame(['Bahía Magdalena'], $stored->preguntas[1]['respuestasAceptadas']);
        Sanctum::actingAs($admin);
        $catalog = $this->getJson('/api/admin/cuestionarios')->assertOk()->json('data');
        $this->assertSame('nuevo', $catalog[0]['slug']);
        $payload['titulo'] = 'Nueva versión';
        Sanctum::actingAs($admin);
        $this->putJson('/api/admin/cuestionarios/nuevo', $payload)->assertOk()->assertJsonPath('data.version', 2);
        $this->assertFalse((bool) $stored->fresh()->activo);
        $payload['slug'] = 'incorrecto';
        $payload['preguntas'][0]['respuestasCorrectas'] = [0, 0];
        Sanctum::actingAs($admin);
        $this->postJson('/api/admin/cuestionarios', $payload)->assertUnprocessable();
    }

    public function test_admin_statistics_include_each_current_quiz_and_historical_attempt_titles(): void
    {
        $admin = new User(['rol' => 'admin', 'estado' => 'activo']);
        $admin->setAttribute('_id', 'test-admin');
        $quiz = [
            'slug' => 'mar', 'version' => 1, 'titulo' => 'Título anterior',
            'activo' => false, 'preguntas' => [], 'puntosAprobacion' => 10,
            'minimoAprobacion' => 70, 'medalla' => ['nombre' => 'Mar'],
        ];
        Cuestionario::create($quiz);
        $quiz['version'] = 2;
        $quiz['titulo'] = 'Título actual';
        $quiz['activo'] = true;
        $quiz['preguntas'] = [['id' => 'una']];
        Cuestionario::create($quiz);
        Cuestionario::create([...$quiz, 'slug' => 'sin-intentos', 'titulo' => 'Sin intentos']);
        IntentoCuestionario::create([
            'usuarioId' => 'test-user', 'cuestionarioSlug' => 'mar',
            'cuestionarioVersion' => 1, 'estado' => 'aprobado', 'puntuacion' => 80,
        ]);
        IntentoCuestionario::create([
            'usuarioId' => 'test-user', 'cuestionarioSlug' => 'mar',
            'cuestionarioVersion' => 2, 'estado' => 'en_progreso',
        ]);
        Sanctum::actingAs($admin);
        $data = $this->getJson('/api/admin/gamificacion/estadisticas')->assertOk()->json('data');
        $courses = collect($data['cuestionarios'])->keyBy('slug');
        $this->assertCount(2, $courses);
        $this->assertSame('Título actual', $courses['mar']['titulo']);
        $this->assertSame(1, $courses['mar']['totalPreguntas']);
        $this->assertSame(2, $courses['mar']['intentos']);
        $this->assertSame(1, $courses['mar']['aprobados']);
        $this->assertSame(1, $courses['mar']['enCurso']);
        $this->assertSame(0, $courses['sin-intentos']['intentos']);
        $this->assertSame(2, $data['totalIntentos']);
        $this->assertSame(1, $data['totalAprobados']);
        $this->assertContains('Título anterior', array_column($data['intentosRecientes'], 'cuestionarioTitulo'));
    }
}
