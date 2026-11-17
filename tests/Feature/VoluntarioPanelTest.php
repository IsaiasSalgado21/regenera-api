<?php

namespace Tests\Feature;

use App\Models\Inscripcion;
use App\Models\User;
use App\Models\Voluntariado;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use MongoDB\BSON\ObjectId;
use Tests\TestCase;

class VoluntarioPanelTest extends TestCase
{
    private ?string $database = null;

    protected function setUp(): void
    {
        parent::setUp();
        $uri = getenv('MONGODB_TEST_URI');
        if (! $uri) {
            $this->markTestSkipped('Configura MONGODB_TEST_URI con una base MongoDB de pruebas.');
        }
        $this->database = 'regenera_volunteer_panel_test_'.bin2hex(random_bytes(6));
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

    public function test_personal_proposals_and_enrollments_are_limited_to_the_signed_in_user(): void
    {
        $userId = '507f1f77bcf86cd799439011';
        $otherId = '507f1f77bcf86cd799439012';
        $proposal = Voluntariado::create([
            'titulo' => 'Mi propuesta', 'estado' => 'pendiente', 'organizadorId' => new ObjectId($userId),
        ]);
        Voluntariado::create([
            'titulo' => 'Propuesta ajena', 'estado' => 'pendiente', 'organizadorId' => $otherId,
        ]);
        $published = Voluntariado::create([
            'titulo' => 'Actividad pública', 'estado' => 'publicado', 'organizadorId' => $otherId,
            'fechaInicio' => '2099-01-01', 'fechaFin' => '2099-01-01',
            'horaInicio' => '09:00', 'horaFin' => '11:00',
        ]);
        foreach ([$userId, $otherId] as $id) {
            Inscripcion::create([
                'usuarioId' => $id, 'voluntariadoId' => (string) $published->getKey(),
                'estado' => 'inscrita', 'fechaInscripcion' => now(),
            ]);
        }
        $user = new User(['rol' => 'usuario', 'estado' => 'activo', 'perfilCompleto' => true]);
        $user->setAttribute('_id', $userId);
        Sanctum::actingAs($user);
        $data = $this->getJson('/api/voluntariados')->assertOk()->json();

        $this->assertCount(1, $data['misVoluntariados']);
        $this->assertSame((string) $proposal->getKey(), $data['misVoluntariados'][0]['id']);
        $this->assertCount(1, $data['misInscripciones']);
        $this->assertSame('inscrita', $data['misInscripciones'][0]['estadoInscripcion']);
        $this->assertSame((string) $published->getKey(), $data['misInscripciones'][0]['id']);
    }
}
