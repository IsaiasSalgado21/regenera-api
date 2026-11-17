<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Inscripcion;
use App\Models\Voluntariado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileAccountSettingsTest extends TestCase
{
    private ?string $database = null;

    protected function setUp(): void
    {
        parent::setUp();
        $uri = getenv('MONGODB_TEST_URI');
        if (! $uri) {
            $this->markTestSkipped('Configura MONGODB_TEST_URI con una base MongoDB de pruebas.');
        }
        $this->database = 'regenera_account_test_'.bin2hex(random_bytes(6));
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

    public function test_profile_edit_changes_only_the_authenticated_users_name(): void
    {
        $user = User::create(['nombre' => 'Original', 'correo' => 'test@example.test', 'password' => 'password123', 'rol' => 'usuario', 'estado' => 'activo', 'perfilCompleto' => true]);
        $other = User::create(['nombre' => 'Otra persona', 'correo' => 'other@example.test', 'password' => 'password123', 'rol' => 'usuario', 'estado' => 'activo']);
        $token = $user->createToken('test')->plainTextToken;
        $this->withToken($token)->patchJson('/api/auth/perfil', [
            'nombre' => ' Nombre actualizado ', 'rol' => 'admin', 'id' => (string) $other->getKey(),
        ])->assertOk()->assertJsonPath('usuario.nombre', 'Nombre actualizado');
        $this->assertSame('usuario', $user->fresh()->rol);
        $this->assertSame('Otra persona', $other->fresh()->nombre);
        $this->withToken($token)->patchJson('/api/auth/perfil', ['nombre' => '   '])->assertUnprocessable();
    }

    public function test_password_change_requires_current_password_and_revokes_other_tokens(): void
    {
        $user = User::create(['nombre' => 'Usuario', 'correo' => 'test@example.test', 'password' => 'password123', 'rol' => 'usuario', 'estado' => 'activo', 'perfilCompleto' => true]);
        $current = $user->createToken('current')->plainTextToken;
        $other = $user->createToken('other')->plainTextToken;
        $payload = ['password_actual' => 'incorrecta', 'password' => 'nuevapassword', 'password_confirmation' => 'nuevapassword'];
        $this->withToken($current)->patchJson('/api/auth/contrasena', $payload)->assertUnprocessable();
        $this->assertTrue(Hash::check('password123', $user->fresh()->password));
        $this->withToken($other)->getJson('/api/auth/me')->assertOk();
        $payload['password_actual'] = 'password123';
        $payload['password_confirmation'] = 'no-coincide';
        $this->withToken($current)->patchJson('/api/auth/contrasena', $payload)->assertUnprocessable();
        $payload['password_confirmation'] = 'nuevapassword';
        $this->withToken($current)->patchJson('/api/auth/contrasena', $payload)->assertOk();
        $this->assertTrue(Hash::check('nuevapassword', $user->fresh()->password));
        $this->withToken($current)->getJson('/api/auth/me')->assertOk();
        $this->withToken($other)->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_email_change_requires_password_and_rejects_an_existing_email(): void
    {
        $user = User::create(['nombre' => 'Usuario', 'correo' => 'test@example.test', 'password' => 'password123', 'rol' => 'usuario', 'estado' => 'activo', 'perfilCompleto' => true]);
        User::create(['nombre' => 'Otra persona', 'correo' => 'taken@example.test', 'password' => 'password123', 'rol' => 'usuario', 'estado' => 'activo']);
        $token = $user->createToken('test')->plainTextToken;
        $this->withToken($token)->patchJson('/api/auth/correo', [
            'correo' => 'new@example.test', 'password_actual' => 'incorrecta',
        ])->assertUnprocessable();
        $this->withToken($token)->patchJson('/api/auth/correo', [
            'correo' => 'TAKEN@example.test', 'password_actual' => 'password123',
        ])->assertUnprocessable();
        $this->assertSame('test@example.test', $user->fresh()->correo);
        $this->withToken($token)->patchJson('/api/auth/correo', [
            'correo' => 'NEW@example.test', 'password_actual' => 'password123',
        ])->assertOk()->assertJsonPath('usuario.correo', 'new@example.test');
        $this->assertSame('new@example.test', $user->fresh()->correo);
    }

    public function test_volunteer_dashboard_only_shows_private_emails_to_admins_or_the_owner(): void
    {
        $organizer = User::create(['nombre' => 'Organizadora', 'correo' => 'organizer@example.test', 'password' => 'password123', 'rol' => 'organizador', 'estado' => 'activo', 'perfilCompleto' => true]);
        $participant = User::create(['nombre' => 'Participante', 'correo' => 'participant@example.test', 'password' => 'password123', 'rol' => 'usuario', 'estado' => 'activo', 'perfilCompleto' => true]);
        $admin = User::create(['nombre' => 'Admin', 'correo' => 'admin@example.test', 'password' => 'password123', 'rol' => 'admin', 'estado' => 'activo', 'perfilCompleto' => true]);
        $volunteer = Voluntariado::create([
            'titulo' => 'Actividad',
            'organizadorId' => (string) $organizer->getKey(),
            'estado' => 'publicado',
            'cupo' => 5,
        ]);
        Inscripcion::create([
            'usuarioId' => (string) $participant->getKey(),
            'voluntariadoId' => (string) $volunteer->getKey(),
            'estado' => 'inscrita',
            'fechaInscripcion' => now(),
        ]);

        $url = '/api/admin/voluntariados/'.$volunteer->getKey().'/dashboard';
        $this->withToken($organizer->createToken('test')->plainTextToken)
            ->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.participantes.0.correo', null)
            ->assertJsonPath('data.voluntariado.organizador.correo', 'organizer@example.test');
        $this->withToken($admin->createToken('test')->plainTextToken)
            ->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.participantes.0.correo', 'participant@example.test');
        $this->withToken($participant->createToken('test')->plainTextToken)
            ->getJson($url)
            ->assertForbidden();
    }
}
