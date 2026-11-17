<?php

namespace Tests\Feature;

use App\Models\Cuestionario;
use App\Models\EventoGamificacion;
use App\Models\IntentoCuestionario;
use App\Models\Medalla;
use App\Models\User;
use App\Services\GamificacionService;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MedallaAdminTest extends TestCase
{
    private ?string $database = null;

    protected function setUp(): void
    {
        parent::setUp();
        $uri = getenv('MONGODB_TEST_URI');
        if (! $uri) {
            $this->markTestSkipped('Configura MONGODB_TEST_URI con una base MongoDB de pruebas.');
        }
        $this->database = 'regenera_medal_test_'.bin2hex(random_bytes(6));
        config(['database.connections.mongodb.dsn' => $uri, 'database.connections.mongodb.database' => $this->database]);
        DB::purge('mongodb');
        (require database_path('migrations'.DIRECTORY_SEPARATOR.'2026_11_16_210000_add_medalla_slug_index.php'))->up();
    }

    protected function tearDown(): void
    {
        if ($this->database !== null) {
            DB::connection('mongodb')->getMongoDB()->drop();
            DB::purge('mongodb');
        }
        parent::tearDown();
    }

    public function test_admin_manages_independent_medals_and_only_active_ones_can_be_linked(): void
    {
        $admin = $this->user('admin', 'admin');
        $medalla = ['slug' => 'mar', 'nombre' => 'Mar', 'descripcion' => 'Protección marina', 'icono' => 'mangrove'];
        Sanctum::actingAs($admin);
        $this->postJson('/api/admin/medallas', $medalla)->assertCreated();
        Sanctum::actingAs($admin);
        $this->postJson('/api/admin/medallas', $medalla)->assertConflict();

        $payload = [
            'slug' => 'mar-quiz', 'titulo' => 'Mar', 'descripcion' => 'Descripción',
            'guia' => 'Guía', 'audiencia' => ['usuario'], 'minimoAprobacion' => 60,
            'puntosAprobacion' => 0, 'medallaSlug' => 'mar',
            'preguntas' => [['id' => 'q1', 'texto' => 'Pregunta', 'opciones' => ['Sí', 'No'],
                'respuestaCorrecta' => 0, 'explicacion' => 'Explicación']],
        ];
        Sanctum::actingAs($admin);
        $this->postJson('/api/admin/cuestionarios', $payload)->assertCreated()
            ->assertJsonPath('data.medalla.slug', 'mar');
        $this->assertSame('mar', Cuestionario::where('slug', 'mar-quiz')->firstOrFail()->medalla['slug']);
        Sanctum::actingAs($admin);
        $this->deleteJson('/api/admin/medallas/mar')->assertOk();
        $this->assertFalse(Medalla::where('slug', 'mar')->firstOrFail()->activo);
        $this->assertSame('mar', Cuestionario::where('slug', 'mar-quiz')->firstOrFail()->medalla['slug']);

        $payload['slug'] = 'otro';
        Sanctum::actingAs($admin);
        $this->postJson('/api/admin/cuestionarios', $payload)->assertUnprocessable();
        $legacyPayload = $payload;
        unset($legacyPayload['medallaSlug']);
        $legacyPayload['medalla'] = $medalla;
        Sanctum::actingAs($admin);
        $this->postJson('/api/admin/cuestionarios', $legacyPayload)->assertUnprocessable();
        Sanctum::actingAs($admin);
        $this->putJson('/api/admin/cuestionarios/mar-quiz', $payload)->assertOk();

        $user = $this->user('usuario', 'usuario');
        Sanctum::actingAs($user);
        $this->getJson('/api/admin/medallas')->assertForbidden();
    }

    public function test_legacy_and_earned_medals_remain_visible_after_deactivation_without_new_awards(): void
    {
        $quiz = Cuestionario::create([
            'slug' => 'legado', 'version' => 1, 'titulo' => 'Legado', 'descripcion' => 'Descripción',
            'audiencia' => ['usuario'], 'activo' => true, 'puntosAprobacion' => 0,
            'medalla' => ['slug' => 'antigua', 'nombre' => 'Antigua', 'descripcion' => 'Medalla', 'icono' => 'leader'],
            'preguntas' => [],
        ]);
        EventoGamificacion::create([
            'usuarioId' => 'ganador', 'tipo' => 'medalla', 'referenciaId' => 'antigua', 'creadoEn' => now(),
        ]);
        $admin = $this->user('admin', 'admin');
        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/medallas')->assertOk()->assertJsonPath('data.0.slug', 'antigua');
        Sanctum::actingAs($admin);
        $this->deleteJson('/api/admin/medallas/antigua')->assertOk();
        $quiz->update(['activo' => false]);

        $winner = $this->user('ganador', 'usuario');
        Sanctum::actingAs($winner);
        $medals = $this->getJson('/api/gamificacion/progreso')->assertOk()->json('data.medallasObtenidas');
        $this->assertSame('antigua', $medals[0]['slug']);

        $other = $this->user('otro', 'usuario');
        $attempt = new IntentoCuestionario(['estado' => 'aprobado']);
        app(GamificacionService::class)->registrarAprobacion($other, $quiz, $attempt);
        $this->assertFalse(EventoGamificacion::where('usuarioId', 'otro')->where('tipo', 'medalla')->exists());
    }

    public function test_active_medal_is_awarded_and_unlinked_medal_can_be_deleted(): void
    {
        $admin = $this->user('admin', 'admin');
        Sanctum::actingAs($admin);
        $this->postJson('/api/admin/medallas', [
            'slug' => 'libre', 'nombre' => 'Libre', 'descripcion' => 'Sin vincular', 'icono' => 'leader',
        ])->assertCreated();
        Sanctum::actingAs($admin);
        $this->deleteJson('/api/admin/medallas/libre')->assertOk();
        $this->assertFalse(Medalla::where('slug', 'libre')->exists());

        Medalla::create(['slug' => 'activa', 'nombre' => 'Activa', 'descripcion' => 'Medalla', 'icono' => 'leader', 'activo' => true]);
        $quiz = new Cuestionario([
            'slug' => 'curso', 'titulo' => 'Curso', 'puntosAprobacion' => 0,
            'medalla' => ['slug' => 'activa', 'nombre' => 'Activa'],
        ]);
        app(GamificacionService::class)->registrarAprobacion($this->user('ganador', 'usuario'), $quiz, new IntentoCuestionario(['estado' => 'aprobado']));
        $this->assertTrue(EventoGamificacion::where('usuarioId', 'ganador')
            ->where('tipo', 'medalla')->where('referenciaId', 'activa')->exists());
    }

    public function test_earned_catalog_medal_remains_visible_without_a_quiz_snapshot(): void
    {
        $id = '507f1f77bcf86cd799439011';
        $user = $this->user($id, 'usuario');
        $user->nombre = 'Perfil de prueba';
        $user->save();
        Medalla::create(['slug' => 'historica', 'nombre' => 'Histórica', 'descripcion' => 'Medalla', 'icono' => 'leader', 'activo' => false]);
        EventoGamificacion::create(['usuarioId' => $id, 'tipo' => 'medalla', 'referenciaId' => 'historica', 'creadoEn' => now()]);

        Sanctum::actingAs($user);
        $this->getJson('/api/gamificacion/progreso')->assertOk()
            ->assertJsonPath('data.medallasObtenidas.0.slug', 'historica');
        $this->getJson('/api/perfiles/'.$id)->assertOk()
            ->assertJsonPath('data.medallas.0.slug', 'historica');
    }

    private function user(string $id, string $role): User
    {
        $user = new User(['rol' => $role, 'estado' => 'activo', 'perfilCompleto' => true]);
        $user->setAttribute('_id', $id);

        return $user;
    }
}
