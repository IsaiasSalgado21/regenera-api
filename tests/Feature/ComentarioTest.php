<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Comentario;
use App\Models\User;
use App\Models\Voluntariado;
use Database\Seeders\ComentariosDemoSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ComentarioTest extends TestCase
{
    private ?string $database = null;

    protected function setUp(): void
    {
        parent::setUp();
        $uri = getenv('MONGODB_TEST_URI');
        if (! $uri) {
            $this->markTestSkipped('Configura MONGODB_TEST_URI con una base MongoDB de pruebas.');
        }

        $this->database = 'regenera_comentarios_test_'.bin2hex(random_bytes(6));
        config([
            'database.connections.mongodb.dsn' => $uri,
            'database.connections.mongodb.database' => $this->database,
        ]);
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

    public function test_volunteer_does_not_see_another_volunteers_private_comment(): void
    {
        $owner = $this->crearUsuario('organizador');
        $voluntariado = $this->crearVoluntariado($owner);
        $autor = $this->crearUsuario('usuario');
        $otro = $this->crearUsuario('usuario');
        $this->crearComentario($voluntariado, $autor, 'Privado de otra persona', true, false);
        $this->crearComentario($voluntariado, $autor, 'Comentario público', false, true);

        Sanctum::actingAs($otro);

        $this->getJson($this->rutaComentarios($voluntariado))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.contenido', 'Comentario público')
            ->assertJsonMissingPath('data.0.autor.correo')
            ->assertJsonMissingPath('data.0.autor.telefono')
            ->assertJsonMissingPath('data.0.autor.latitud')
            ->assertJsonMissingPath('data.0.autor.longitud');
    }

    public function test_volunteer_sees_their_own_private_comments(): void
    {
        $owner = $this->crearUsuario('organizador');
        $voluntariado = $this->crearVoluntariado($owner);
        $voluntario = $this->crearUsuario('usuario');
        $this->crearComentario($voluntariado, $voluntario, 'Mi duda privada', true, false);

        Sanctum::actingAs($voluntario);

        $this->getJson($this->rutaComentarios($voluntariado))
            ->assertOk()
            ->assertJsonPath('data.0.contenido', 'Mi duda privada')
            ->assertJsonPath('data.0.es_privado', true);
    }

    public function test_owner_organizer_sees_all_comments(): void
    {
        $owner = $this->crearUsuario('organizador');
        $voluntariado = $this->crearVoluntariado($owner);
        $autor = $this->crearUsuario('usuario');
        $this->crearComentario($voluntariado, $autor, 'Duda privada', true, false);
        $this->crearComentario($voluntariado, $autor, 'Duda pública', false, true);
        $this->crearComentario($voluntariado, $autor, 'Comentario oculto', false, false);

        Sanctum::actingAs($owner);

        $this->getJson($this->rutaComentarios($voluntariado))
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_volunteer_cannot_moderate_or_delete_another_users_comment(): void
    {
        $owner = $this->crearUsuario('organizador');
        $voluntariado = $this->crearVoluntariado($owner);
        $autor = $this->crearUsuario('usuario');
        $otro = $this->crearUsuario('usuario');
        $comentario = $this->crearComentario($voluntariado, $autor, 'Comentario público', false, true);
        Sanctum::actingAs($otro);

        $this->patchJson('/api/comentarios/'.$comentario->getKey().'/visibilidad', [
            'es_publicado' => false,
        ])->assertForbidden();

        $this->deleteJson('/api/comentarios/'.$comentario->getKey())
            ->assertForbidden();

        $this->assertNotNull(Comentario::find($comentario->getKey()));
    }

    public function test_owner_organizer_can_hide_a_public_comment_and_action_is_audited(): void
    {
        $owner = $this->crearUsuario('organizador');
        $voluntariado = $this->crearVoluntariado($owner);
        $autor = $this->crearUsuario('usuario');
        $comentario = $this->crearComentario($voluntariado, $autor, 'Comentario público', false, true);
        Sanctum::actingAs($owner);
        $this->postJson('/api/comentarios/'.$comentario->getKey().'/responder', [
            'contenido' => 'Respuesta pública',
        ])->assertCreated();

        $this->patchJson('/api/comentarios/'.$comentario->getKey().'/visibilidad', [
            'es_publicado' => false,
        ])->assertOk()
            ->assertJsonPath('data.es_publicado', false)
            ->assertJsonPath('data.puede_moderar', true);

        $this->assertSame(
            'moderar_comentario',
            Auditoria::query()->where('entidad', 'comentario')->firstOrFail()->accion,
        );

        $otro = $this->crearUsuario('usuario');
        Sanctum::actingAs($otro);

        $this->getJson($this->rutaComentarios($voluntariado))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        Sanctum::actingAs($owner);

        $this->getJson($this->rutaComentarios($voluntariado))
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_owner_organizer_can_publish_a_private_question(): void
    {
        $owner = $this->crearUsuario('organizador');
        $voluntariado = $this->crearVoluntariado($owner);
        $autor = $this->crearUsuario('usuario');
        $comentario = $this->crearComentario($voluntariado, $autor, 'Duda privada', true, false);
        Sanctum::actingAs($owner);

        $this->patchJson('/api/comentarios/'.$comentario->getKey().'/publicar')
            ->assertOk()
            ->assertJsonPath('data.es_privado', false)
            ->assertJsonPath('data.es_publicado', true)
            ->assertJsonPath('data.puede_publicar', false);

        $otroVoluntario = $this->crearUsuario('usuario');
        Sanctum::actingAs($otroVoluntario);

        $this->getJson($this->rutaComentarios($voluntariado))
            ->assertOk()
            ->assertJsonPath('data.0.contenido', 'Duda privada')
            ->assertJsonPath('data.0.es_publicado', true);
    }

    public function test_private_default_forces_volunteer_comments_to_be_private(): void
    {
        $owner = $this->crearUsuario('organizador');
        $voluntariado = $this->crearVoluntariado($owner, 'privado');
        $voluntario = $this->crearUsuario('usuario');
        Sanctum::actingAs($voluntario);

        $this->postJson($this->rutaComentarios($voluntariado), [
            'contenido' => 'Comentario enviado como público',
            'es_privado' => false,
        ])->assertCreated()
            ->assertJsonPath('data.es_privado', true)
            ->assertJsonPath('data.es_publicado', false);
    }

    public function test_only_owner_or_admin_can_update_default_comment_visibility(): void
    {
        $owner = $this->crearUsuario('organizador');
        $voluntariado = $this->crearVoluntariado($owner);
        Sanctum::actingAs($owner);

        $this->patchJson('/api/voluntariados/'.$voluntariado->getKey().'/config-comentarios', [
            'comentarios_por_defecto' => 'privado',
        ])->assertOk()
            ->assertJsonPath('data.comentarios_por_defecto', 'privado');

        $otroOrganizador = $this->crearUsuario('organizador');
        Sanctum::actingAs($otroOrganizador);

        $this->patchJson('/api/voluntariados/'.$voluntariado->getKey().'/config-comentarios', [
            'comentarios_por_defecto' => 'publico',
        ])->assertForbidden();
    }

    public function test_private_reply_remains_visible_to_author_after_question_is_published(): void
    {
        $owner = $this->crearUsuario('organizador');
        $voluntariado = $this->crearVoluntariado($owner);
        $autor = $this->crearUsuario('usuario');
        $otroVoluntario = $this->crearUsuario('usuario');
        $comentario = $this->crearComentario($voluntariado, $autor, 'Duda privada', true, false);
        Sanctum::actingAs($owner);

        $respuesta = $this->postJson('/api/comentarios/'.$comentario->getKey().'/responder', [
            'contenido' => 'Respuesta privada',
        ])->assertCreated()
            ->assertJsonPath('data.contenido', 'Respuesta privada')
            ->assertJsonPath('data.parent_id', (string) $comentario->getKey())
            ->assertJsonPath('data.es_privado', true)
            ->assertJsonPath('data.es_publicado', false);

        Sanctum::actingAs($autor);
        $this->getJson($this->rutaComentarios($voluntariado))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $respuesta->json('data.id'),
                'parent_id' => (string) $comentario->getKey(),
                'contenido' => 'Respuesta privada',
            ]);

        Sanctum::actingAs($otroVoluntario);
        $this->getJson($this->rutaComentarios($voluntariado))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        Sanctum::actingAs($owner);
        $this->patchJson('/api/comentarios/'.$comentario->getKey().'/publicar')
            ->assertOk();

        Sanctum::actingAs($autor);
        $this->getJson($this->rutaComentarios($voluntariado))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $respuesta->json('data.id')]);

        Sanctum::actingAs($otroVoluntario);
        $this->getJson($this->rutaComentarios($voluntariado))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonMissing(['id' => $respuesta->json('data.id')]);
    }

    public function test_reply_cannot_be_nested_under_another_reply(): void
    {
        $owner = $this->crearUsuario('organizador');
        $voluntariado = $this->crearVoluntariado($owner);
        $autor = $this->crearUsuario('usuario');
        $comentario = $this->crearComentario($voluntariado, $autor, 'Comentario raíz', false, true);
        Sanctum::actingAs($owner);

        $respuesta = $this->postJson('/api/comentarios/'.$comentario->getKey().'/responder', [
            'contenido' => 'Primera respuesta',
        ])->assertCreated();

        $this->postJson('/api/comentarios/'.$respuesta->json('data.id').'/responder', [
            'contenido' => 'Respuesta anidada no permitida',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('comentario');
    }

    public function test_public_reply_is_visible_to_other_volunteers_with_parent_reference(): void
    {
        $owner = $this->crearUsuario('organizador');
        $voluntariado = $this->crearVoluntariado($owner);
        $autor = $this->crearUsuario('usuario');
        $otroVoluntario = $this->crearUsuario('usuario');
        $comentario = $this->crearComentario($voluntariado, $autor, 'Pregunta pública', false, true);
        Sanctum::actingAs($owner);

        $respuesta = $this->postJson('/api/comentarios/'.$comentario->getKey().'/responder', [
            'contenido' => 'Respuesta para todas las personas',
        ])->assertCreated()
            ->assertJsonPath('data.parent_id', (string) $comentario->getKey())
            ->assertJsonPath('data.es_publicado', true);

        Sanctum::actingAs($otroVoluntario);
        $this->getJson($this->rutaComentarios($voluntariado))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'id' => $respuesta->json('data.id'),
                'parent_id' => (string) $comentario->getKey(),
                'contenido' => 'Respuesta para todas las personas',
            ]);
    }

    public function test_deleted_comment_is_removed_for_volunteer_and_organizer(): void
    {
        $owner = $this->crearUsuario('organizador');
        $voluntariado = $this->crearVoluntariado($owner);
        $autor = $this->crearUsuario('usuario');
        $comentario = $this->crearComentario($voluntariado, $autor, 'Comentario para eliminar', false, true);
        Sanctum::actingAs($owner);
        $respuesta = $this->postJson('/api/comentarios/'.$comentario->getKey().'/responder', [
            'contenido' => 'Respuesta que debe eliminarse con el padre',
        ])->assertCreated();

        $this->deleteJson('/api/comentarios/'.$comentario->getKey())
            ->assertOk();

        $this->assertNull(Comentario::find($comentario->getKey()));
        $this->assertNull(Comentario::find($respuesta->json('data.id')));

        Sanctum::actingAs($autor);
        $this->getJson($this->rutaComentarios($voluntariado))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        Sanctum::actingAs($owner);
        $this->getJson($this->rutaComentarios($voluntariado))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_demo_seeder_creates_ten_comments_and_two_replies_idempotently(): void
    {
        $comentariosPrincipales = array_merge(
            array_column(ComentariosDemoSeeder::DUDAS, 'contenido'),
            ComentariosDemoSeeder::POSITIVOS,
        );
        $contenidosDemo = array_merge($comentariosPrincipales, array_column(ComentariosDemoSeeder::RESPUESTAS, 'contenido'));

        $this->seed(ComentariosDemoSeeder::class);
        $voluntariadoDemo = Voluntariado::query()
            ->where('titulo', ComentariosDemoSeeder::DEMO_VOLUNTEER_TITLE)
            ->firstOrFail();
        $this->assertSame(5, Comentario::query()->whereIn('contenido', array_column(ComentariosDemoSeeder::DUDAS, 'contenido'))->count());
        $this->assertSame(5, Comentario::query()->whereIn('contenido', ComentariosDemoSeeder::POSITIVOS)->count());
        $this->assertSame(10, Comentario::query()->whereIn('contenido', $comentariosPrincipales)->count());
        $this->assertSame(
            3,
            Comentario::query()
                ->whereIn('contenido', array_column(ComentariosDemoSeeder::DUDAS, 'contenido'))
                ->where('es_privado', true)
                ->where('es_publicado', false)
                ->count(),
        );
        $this->assertSame(
            5,
            Comentario::query()
                ->whereIn('contenido', ComentariosDemoSeeder::POSITIVOS)
                ->where('es_privado', false)
                ->where('es_publicado', true)
                ->count(),
        );
        $this->assertSame(12, Comentario::query()->whereIn('contenido', $contenidosDemo)->count());
        $this->assertSame(
            12,
            Comentario::query()
                ->whereIn('contenido', $contenidosDemo)
                ->where('voluntariado_id', $voluntariadoDemo->getKey())
                ->count(),
        );
        $preguntaPrivada = Comentario::query()
            ->where('contenido', ComentariosDemoSeeder::DUDAS[0]['contenido'])
            ->firstOrFail();
        $respuestaPrivada = Comentario::query()
            ->where('contenido', ComentariosDemoSeeder::RESPUESTAS[0]['contenido'])
            ->firstOrFail();
        $this->assertSame((string) $preguntaPrivada->getKey(), (string) $respuestaPrivada->parent_id);

        $this->seed(ComentariosDemoSeeder::class);

        $this->assertSame(12, Comentario::query()->whereIn('contenido', $contenidosDemo)->count());
        $this->assertSame(12, Comentario::query()->count());
    }

    private function crearUsuario(string $rol): User
    {
        return User::create([
            'nombre' => 'Usuario de prueba',
            'correo' => Str::uuid().'@example.test',
            'password' => 'ComentarioTest123!',
            'rol' => $rol,
            'estado' => 'activo',
            'perfilCompleto' => true,
        ]);
    }

    private function crearVoluntariado(User $organizador, string $config = 'publico'): Voluntariado
    {
        return Voluntariado::create([
            'titulo' => 'Actividad de prueba',
            'organizadorId' => (string) $organizador->getAuthIdentifier(),
            'estado' => 'publicado',
            'categoria' => 'Conservación',
            'comentarios_por_defecto' => $config,
        ]);
    }

    private function crearComentario(
        Voluntariado $voluntariado,
        User $autor,
        string $contenido,
        bool $esPrivado,
        bool $esPublicado,
    ): Comentario {
        return Comentario::create([
            'voluntariado_id' => $voluntariado->getKey(),
            'user_id' => $autor->getKey(),
            'contenido' => $contenido,
            'es_privado' => $esPrivado,
            'es_publicado' => $esPublicado,
        ]);
    }

    private function rutaComentarios(Voluntariado $voluntariado): string
    {
        return '/api/voluntariados/'.$voluntariado->getKey().'/comentarios';
    }
}
