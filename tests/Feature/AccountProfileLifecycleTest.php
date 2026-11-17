<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AccountProfileLifecycleTest extends TestCase
{
    private ?string $database = null;

    protected function setUp(): void
    {
        parent::setUp();
        $uri = getenv('MONGODB_TEST_URI');
        if (! $uri) {
            $this->markTestSkipped('Configura MONGODB_TEST_URI con una base MongoDB de pruebas.');
        }
        $this->database = 'regenera_profile_lifecycle_'.bin2hex(random_bytes(6));
        config(['database.connections.mongodb.dsn' => $uri, 'database.connections.mongodb.database' => $this->database]);
        DB::purge('mongodb');
        Carbon::setTestNow('2026-11-16 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        if ($this->database !== null) {
            DB::connection('mongodb')->getMongoDB()->drop();
            DB::purge('mongodb');
        }
        parent::tearDown();
    }

    private function profile(string $birth = '2000-01-01'): array
    {
        return [
            'correoSecundario' => 'secondary@example.test',
            'ciudad' => 'La Paz',
            'genero' => 'prefiero_no_decirlo',
            'fechaNacimiento' => $birth,
        ];
    }

    private function guardian(): array
    {
        return ['nombre' => 'Persona adulta', 'correo' => 'guardian@example.test', 'telefono' => '1234567890', 'fechaNacimiento' => '1980-01-01', 'autorizacion' => true];
    }

    private function registration(array $profile): array
    {
        return [...$profile, 'nombre' => 'Participante', 'correo' => 'person@example.test', 'password' => 'password123', 'password_confirmation' => 'password123'];
    }

    public function test_registration_enforces_age_guardian_and_secondary_email(): void
    {
        $this->postJson('/api/auth/register', $this->registration($this->profile('2013-11-17')))
            ->assertUnprocessable()->assertJsonValidationErrors('fechaNacimiento');
        $this->postJson('/api/auth/register', $this->registration($this->profile('2013-11-16')))
            ->assertUnprocessable()->assertJsonValidationErrors('tutor.nombre');
        $guardian = [...$this->guardian(), 'fechaNacimiento' => '2008-11-17'];
        $this->postJson('/api/auth/register', $this->registration([...$this->profile('2013-11-16'), 'tutor' => $guardian]))
            ->assertUnprocessable()->assertJsonValidationErrors('tutor.fechaNacimiento');
        $this->postJson('/api/auth/register', $this->registration([...$this->profile(), 'correoSecundario' => 'PERSON@example.test']))
            ->assertUnprocessable()->assertJsonValidationErrors('correoSecundario');
        $response = $this->postJson('/api/auth/register', $this->registration([...$this->profile('2013-11-16'), 'tutor' => $this->guardian()]))->assertCreated();
        $user = User::where('correo', 'person@example.test')->firstOrFail();
        $this->assertTrue($user->privacidad);
        $this->withToken($response->json('token'))->patchJson('/api/auth/detalles', [...$this->profile(), 'tutor' => $this->guardian(), 'privacidad' => false])
            ->assertUnprocessable()->assertJsonValidationErrors('privacidad');
        $this->getJson('/api/perfiles/'.$user->getKey())->assertOk()->assertJsonMissingPath('data.descripcion');
    }

    public function test_legacy_accounts_are_blocked_until_completion_and_underage_declarations_remain_blocked(): void
    {
        $user = User::create(['nombre' => 'Existente', 'correo' => 'legacy@example.test', 'password' => 'password123', 'rol' => 'usuario', 'estado' => 'activo']);
        $token = $user->createToken('test')->plainTextToken;
        $this->withToken($token)->getJson('/api/auth/me')->assertOk()->assertJsonPath('usuario.perfilCompleto', false);
        $this->withToken($token)->getJson('/api/gamificacion/progreso')->assertStatus(428);
        $this->withToken($token)->getJson('/api/voluntariados')->assertStatus(428);
        $this->withToken($token)->postJson('/api/auth/completar-perfil', $this->profile())->assertOk();
        $this->withToken($token)->getJson('/api/auth/me')->assertJsonPath('usuario.perfilCompleto', true);

        $other = User::create(['nombre' => 'Existente', 'correo' => 'legacy2@example.test', 'password' => 'password123', 'rol' => 'usuario', 'estado' => 'activo']);
        $otherToken = $other->createToken('test')->plainTextToken;
        $this->withToken($otherToken)->postJson('/api/auth/completar-perfil', $this->profile('2014-01-01'))->assertForbidden();
        $this->withToken($otherToken)->postJson('/api/auth/completar-perfil', $this->profile())->assertForbidden();
        $this->withoutHeader('Authorization')->postJson('/api/auth/login', ['correo' => 'legacy2@example.test', 'password' => 'password123'])
            ->assertForbidden()->assertJsonPath('message', 'No es posible acceder con esta cuenta. Contacta a Regenerabcs@mail.com.');
    }

    public function test_public_fields_and_birth_date_review_respect_privacy(): void
    {
        $user = User::create([...$this->profile('2008-11-17'), 'tutor' => $this->guardian(), 'nombre' => 'Usuario', 'correo' => 'person@example.test', 'password' => 'password123', 'rol' => 'usuario', 'estado' => 'activo', 'perfilCompleto' => true, 'privacidad' => true, 'descripcion' => 'Descripción']);
        $admin = User::create(['nombre' => 'Admin', 'correo' => 'admin@example.test', 'password' => 'password123', 'rol' => 'admin', 'estado' => 'activo', 'perfilCompleto' => true]);
        $token = $user->createToken('test')->plainTextToken;
        $this->withToken($token)->postJson('/api/auth/fecha-nacimiento', ['fechaNacimiento' => '2000-01-01'])->assertOk();
        $this->assertSame('2008-11-17', $user->fresh()->fechaNacimiento);
        $this->withToken($token)->patchJson('/api/admin/usuarios/'.$user->getKey().'/fecha-nacimiento', ['decision' => 'aprobar', 'fecha' => '2000-01-01'])->assertForbidden();
        $adminToken = $admin->createToken('test')->plainTextToken;
        $this->withToken($adminToken)->patchJson('/api/admin/usuarios/'.$user->getKey().'/fecha-nacimiento', ['decision' => 'aprobar', 'fecha' => '2000-01-02'])->assertStatus(409);
        $this->withToken($adminToken)->patchJson('/api/admin/usuarios/'.$user->getKey().'/fecha-nacimiento', ['decision' => 'aprobar', 'fecha' => '2000-01-01'])->assertOk();
        $this->assertTrue($user->fresh()->privacidad);
        $this->getJson('/api/perfiles/'.$user->getKey())->assertJsonMissingPath('data.descripcion')->assertJsonMissingPath('data.correoSecundario')->assertJsonMissingPath('data.tutor');
        $this->withToken($token)->patchJson('/api/auth/detalles', [...$this->profile(), 'privacidad' => false, 'descripcion' => 'Descripción'])->assertOk();
        $this->getJson('/api/perfiles/'.$user->getKey())->assertJsonPath('data.descripcion', 'Descripción')->assertJsonMissingPath('data.correoSecundario');
    }

    public function test_privacy_remains_enabled_after_a_minor_becomes_an_adult(): void
    {
        $user = User::create([...$this->profile('2008-11-17'), 'tutor' => $this->guardian(), 'nombre' => 'Usuario', 'correo' => 'person@example.test', 'password' => 'password123', 'rol' => 'usuario', 'estado' => 'activo', 'perfilCompleto' => true, 'privacidad' => true, 'descripcion' => 'Descripción']);
        Carbon::setTestNow('2026-11-17 12:00:00');
        $this->getJson('/api/perfiles/'.$user->getKey())->assertOk()->assertJsonMissingPath('data.descripcion');
        $this->assertTrue($user->fresh()->privacidad);
    }

    public function test_admin_cannot_approve_minor_birth_date_without_guardian_data(): void
    {
        $user = User::create([...$this->profile(), 'nombre' => 'Usuario', 'correo' => 'person@example.test', 'password' => 'password123', 'rol' => 'usuario', 'estado' => 'activo', 'perfilCompleto' => true, 'privacidad' => false]);
        $admin = User::create(['nombre' => 'Admin', 'correo' => 'admin@example.test', 'password' => 'password123', 'rol' => 'admin', 'estado' => 'activo', 'perfilCompleto' => true]);
        $this->withToken($user->createToken('test')->plainTextToken)
            ->postJson('/api/auth/fecha-nacimiento', ['fechaNacimiento' => '2010-01-01'])->assertOk();
        $this->withToken($admin->createToken('test')->plainTextToken)
            ->patchJson('/api/admin/usuarios/'.$user->getKey().'/fecha-nacimiento', ['decision' => 'aprobar', 'fecha' => '2010-01-01'])
            ->assertUnprocessable()->assertJsonValidationErrors('tutor.nombre');
        $this->assertSame('2000-01-01', $user->fresh()->fechaNacimiento);
        $this->assertFalse($user->fresh()->privacidad);
    }

    public function test_admin_can_correct_profile_and_reset_another_accounts_password(): void
    {
        $user = User::create(['nombre' => 'Existente', 'correo' => 'person@example.test', 'password' => 'password123', 'rol' => 'usuario', 'estado' => 'activo', 'menorEdadMinima' => true]);
        $oldToken = $user->createToken('test')->plainTextToken;
        $admin = User::create(['nombre' => 'Admin', 'correo' => 'admin@example.test', 'password' => 'password123', 'rol' => 'admin', 'estado' => 'activo', 'perfilCompleto' => true]);
        $this->withToken($admin->createToken('test')->plainTextToken)
            ->patchJson('/api/admin/usuarios/'.$user->getKey(), [
                'password' => 'newpassword123',
                'datosPerfil' => [...$this->profile('2010-01-01'), 'tutor' => $this->guardian()],
            ])->assertOk()->assertJsonPath('data.privacidad', true);
        $this->assertFalse($user->fresh()->menorEdadMinima);
        $this->assertTrue($user->fresh()->perfilCompleto);
        $this->withToken($oldToken)->getJson('/api/auth/me')->assertUnauthorized();
        $this->withoutHeader('Authorization')->postJson('/api/auth/login', ['correo' => 'person@example.test', 'password' => 'newpassword123'])->assertOk();
    }
}
