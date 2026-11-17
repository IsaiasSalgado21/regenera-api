<?php

namespace Tests\Feature;

use App\Models\Articulo;
use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ArticuloAdminDeleteTest extends TestCase
{
    private ?string $database = null;

    protected function setUp(): void
    {
        parent::setUp();
        $uri = getenv('MONGODB_TEST_URI');
        if (! $uri) {
            $this->markTestSkipped('Configura MONGODB_TEST_URI con una base MongoDB de pruebas.');
        }
        $this->database = 'regenera_article_test_'.bin2hex(random_bytes(6));
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

    public function test_admin_deletes_articles_in_each_state_and_records_the_real_previous_state(): void
    {
        $admin = new User(['rol' => 'admin', 'estado' => 'activo', 'perfilCompleto' => true]);
        $admin->setAttribute('_id', '507f1f77bcf86cd799439011');
        foreach (['borrador', 'pendiente', 'publicado', 'rechazado'] as $estado) {
            $article = Articulo::create(['titulo' => 'Prueba', 'contenido' => 'Contenido', 'estado' => $estado]);
            Sanctum::actingAs($admin);
            $this->deleteJson('/api/admin/articulos/'.$article->getKey())->assertOk();
            $this->assertNull(Articulo::find($article->getKey()));
            $audit = Auditoria::query()->orderByDesc('created_at')->get()
                ->first(fn ($item) => (string) $item->entidadId === (string) $article->getKey());
            $this->assertSame('eliminar', $audit->accion);
            $this->assertSame($estado, $audit->detalles['estado_anterior']);
        }
    }
}
