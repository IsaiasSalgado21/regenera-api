<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Voluntariado;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use MongoDB\BSON\ObjectId;
use Tests\TestCase;

class ImpactLogAccessTest extends TestCase
{
    private ?string $database = null;

    protected function setUp(): void
    {
        parent::setUp();
        $uri = getenv('MONGODB_TEST_URI');
        if (! $uri) {
            $this->markTestSkipped('Configura MONGODB_TEST_URI con una base MongoDB de pruebas.');
        }
        $this->database = 'regenera_impact_log_test_'.bin2hex(random_bytes(6));
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

    public function test_organizer_log_includes_only_own_activities_for_both_mongodb_id_formats(): void
    {
        $ownerId = '507f1f77bcf86cd799439011';
        $activities = [];
        foreach ([$ownerId, new ObjectId($ownerId), '507f1f77bcf86cd799439012'] as $index => $owner) {
            $activities[] = Voluntariado::create([
                'titulo' => "Actividad $index",
                'organizadorId' => $owner,
                'estado' => 'finalizado',
                'categoria' => 'conservacion',
                'fechaInicio' => '2025-01-01',
                'fechaFin' => '2025-01-01',
                'horaInicio' => '08:00',
                'horaFin' => '10:00',
            ]);
        }
        $organizador = new User(['rol' => 'organizador', 'estado' => 'activo', 'perfilCompleto' => true]);
        $organizador->setAttribute('_id', $ownerId);
        Sanctum::actingAs($organizador);
        $own = $this->getJson('/api/organizador/bitacora-impacto')->assertOk()->json('data');
        $this->assertSame(2, $own['kpis']['voluntariados']);
        $this->assertCount(2, $own['historial']);
        Sanctum::actingAs($organizador);
        $this->getJson('/api/admin/voluntariados/'.$activities[2]->getKey().'/dashboard')->assertForbidden();

        $admin = new User(['rol' => 'admin', 'estado' => 'activo', 'perfilCompleto' => true]);
        $admin->setAttribute('_id', '507f1f77bcf86cd799439013');
        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/bitacora-impacto')->assertOk()
            ->assertJsonPath('data.kpis.voluntariados', 3);
    }

    public function test_impact_of_pending_activity_is_only_visible_to_owner_or_admin(): void
    {
        $activity = Voluntariado::create([
            'titulo' => 'Pendiente', 'organizadorId' => '507f1f77bcf86cd799439011',
            'estado' => 'pendiente', 'categoria' => 'conservacion',
        ]);
        $url = '/api/voluntariados/'.$activity->getKey().'/impactos?soloTipos=1';
        $this->getJson($url)->assertNotFound();

        $other = new User(['rol' => 'organizador', 'estado' => 'activo', 'perfilCompleto' => true]);
        $other->setAttribute('_id', '507f1f77bcf86cd799439012');
        Sanctum::actingAs($other);
        $this->getJson($url)->assertNotFound();

        $owner = new User(['rol' => 'organizador', 'estado' => 'activo', 'perfilCompleto' => true]);
        $owner->setAttribute('_id', '507f1f77bcf86cd799439011');
        Sanctum::actingAs($owner);
        $this->getJson($url)->assertOk();
    }
}
