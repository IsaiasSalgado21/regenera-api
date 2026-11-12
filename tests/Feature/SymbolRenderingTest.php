<?php

namespace Tests\Feature;

use App\Support\VoluntariadoMaterialSymbol;
use App\Models\FloraFauna;
use App\Models\Lugar;
use App\Models\User;
use App\Models\Voluntariado;
use App\Services\BajaCaliforniaSurBoundary;
use App\Services\VoluntariadoUbicacion;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SymbolRenderingTest extends TestCase
{
    public function test_home_card_renders_symbols_and_preserves_volunteer_details(): void
    {
        $html = view('index', [
            'voluntariados' => [[
                'id' => 'example',
                'inicioIso' => '',
                'finIso' => '',
                'imagen' => '',
                'fecha' => '12/11/2026',
                'categoria' => 'limpieza',
                'titulo' => 'Limpieza local',
                'organizadorNombre' => 'Equipo',
                'lugar' => 'La Paz',
                'ubicacion' => ['latitud' => 24.15, 'longitud' => -110.32],
                'cupo' => 10,
                'descripcion' => 'Actividad ambiental',
            ]],
            'especies' => [],
            'articulos' => [],
        ])->render();

        foreach (['calendario', 'ubicacion', 'participantes'] as $symbol) {
            $this->assertStringContainsString("img/simbolos/{$symbol}.png", $html);
        }
        $this->assertStringContainsString('Limpieza local', $html);
        $this->assertStringContainsString('La Paz', $html);
        $this->assertStringContainsString('Cupo: 10', $html);
        $this->assertStringContainsString('query=24.15,-110.32', $html);
        $this->assertStringContainsString('/mapa?voluntariado=example', $html);
    }

    public function test_detail_displays_matching_material_symbols_and_keeps_custom_materials(): void
    {
        $html = $this->renderVolunteerDetail(
            ['https://example.test/cover.jpg'],
            ['Guantes reutilizables', 'Bolsas para residuos', 'Pinzas recolectoras'],
        );

        $this->assertMatchesRegularExpression('~guantes\.png[^>]*>\\s*Guantes reutilizables~', $html);
        $this->assertMatchesRegularExpression('~bolsa-residuos\.png[^>]*>\\s*Bolsas para residuos~', $html);
        $this->assertMatchesRegularExpression('~✦</span>\\s*Pinzas recolectoras~u', $html);
        $this->assertStringContainsString('data-gallery-main', $html);
        $this->assertStringNotContainsString('volunteer-gallery-main', $html);
        $this->assertStringNotContainsString('data-gallery-step=', $html);
    }

    public function test_detail_keeps_multiple_image_controls_and_handles_no_image(): void
    {
        $multiple = $this->renderVolunteerDetail(['https://example.test/one.jpg', 'https://example.test/two.jpg'], []);
        $this->assertStringContainsString('data-gallery-index="0"', $multiple);
        $this->assertStringContainsString('data-gallery-index="1"', $multiple);
        $this->assertStringContainsString('data-lightbox-step="1"', $multiple);

        $empty = $this->renderVolunteerDetail([], []);
        $this->assertStringContainsString('volunteer-detail-placeholder', $empty);
        $this->assertStringNotContainsString('data-gallery-open', $empty);
        $this->assertStringNotContainsString('data-gallery-lightbox', $empty);
    }

    public function test_material_matching_is_case_and_accent_insensitive_without_partial_word_matches(): void
    {
        $this->assertSame('guantes', VoluntariadoMaterialSymbol::for('GUANTES de jardinería'));
        $this->assertSame('agua', VoluntariadoMaterialSymbol::for('Agua potable'));
        $this->assertSame('bloqueador-solar', VoluntariadoMaterialSymbol::for('Protección solar'));
        $this->assertNull(VoluntariadoMaterialSymbol::for('Paraguas'));
        $this->assertNull(VoluntariadoMaterialSymbol::for('Pinzas recolectoras'));
    }

    public function test_volunteer_location_links_require_coordinates(): void
    {
        $html = $this->renderVolunteerDetail([], [], ['latitud' => 24.15, 'longitud' => -110.32]);
        $this->assertStringContainsString('google.com/maps/search/?api=1&amp;query=24.15,-110.32', $html);
        $this->assertStringContainsString('/mapa?voluntariado=example', $html);

        $withoutLocation = $this->renderVolunteerDetail([], []);
        $this->assertStringNotContainsString('Abrir en Google Maps', $withoutLocation);
        $this->assertStringNotContainsString('Ver en mapa de Regenera', $withoutLocation);
    }

    public function test_species_map_links_keep_protected_zones_out_of_google_maps(): void
    {
        $species = new FloraFauna([
            'nombre' => 'Especie de prueba',
            'tipo' => 'fauna',
            'ubicacion' => ['latitud' => 24.15, 'longitud' => -110.32],
        ]);
        $locations = [
            ['nombre' => 'Especie', 'tipo' => 'Distribución registrada', 'latitud' => 24.15, 'longitud' => -110.32, 'parametro' => 'especie', 'id' => 'species-id', 'aproximada' => true],
            ['nombre' => 'Lugar', 'tipo' => 'Lugar relacionado', 'latitud' => 24.16, 'longitud' => -110.33, 'parametro' => 'lugar', 'id' => 'place-id', 'aproximada' => false],
        ];
        $html = view('flora.show', [
            'especie' => $species,
            'lugaresRelacionados' => collect(),
            'mapLocations' => $locations,
            'mapMode' => 'locations',
        ])->render();

        $this->assertStringContainsString('Zona aproximada protegida', $html);
        $this->assertStringContainsString('/mapa?especie=species-id', $html);
        $this->assertStringContainsString('/mapa?lugar=place-id', $html);
        $this->assertStringNotContainsString('query=24.15,-110.32', $html);
        $this->assertStringContainsString('query=24.16,-110.33', $html);
    }

    public function test_saved_volunteer_location_prefers_event_coordinates_and_rejects_invalid_values(): void
    {
        $resolver = new VoluntariadoUbicacion(new BajaCaliforniaSurBoundary);
        $volunteer = new Voluntariado(['latitud' => 24.3217, 'longitud' => -110.3248]);
        $place = new Lugar(['latitud' => 24.15, 'longitud' => -110.32]);

        $this->assertSame(['latitud' => 24.3217, 'longitud' => -110.3248], $resolver->resolver($volunteer, $place));
        $volunteer->latitud = 0;
        $this->assertSame(['latitud' => 24.15, 'longitud' => -110.32], $resolver->resolver($volunteer, $place));
        $place->longitud = null;
        $this->assertNull($resolver->resolver($volunteer, $place));
    }

    public function test_focused_map_location_respects_access_and_matches_volunteer_detail(): void
    {
        $uri = getenv('MONGODB_TEST_URI');
        if (! $uri) {
            $this->markTestSkipped('Configura MONGODB_TEST_URI con una base MongoDB de pruebas.');
        }

        config([
            'database.connections.mongodb.dsn' => $uri,
            'database.connections.mongodb.database' => 'regenera_map_test_'.bin2hex(random_bytes(6)),
        ]);
        DB::purge('mongodb');

        try {
            $owner = new User(['rol' => 'usuario', 'estado' => 'activo']);
            $owner->setAttribute('_id', 'owner-test');
            $place = Lugar::create([
                'nombre' => 'Lugar archivado',
                'estado' => 'archivado',
                'latitud' => 24.15,
                'longitud' => -110.32,
            ]);
            $volunteer = Voluntariado::create([
                'titulo' => 'Actividad de prueba',
                'estado' => 'cancelado',
                'organizadorId' => (string) $owner->getAuthIdentifier(),
                'lugarId' => (string) $place->getKey(),
                'fechaInicio' => now()->addDays(3),
                'fechaFin' => now()->addDays(3),
                'horaInicio' => '09:00',
                'horaFin' => '11:00',
                'cupo' => 5,
            ]);
            $url = '/api/v1/mapa/voluntariados?voluntariado_id='.$volunteer->getKey();

            $this->getJson($url)->assertOk()
                ->assertJsonPath('data.0.lat', 24.15)
                ->assertJsonPath('data.0.lng', -110.32);
            $this->get('/voluntariados/'.$volunteer->getKey())->assertOk()
                ->assertSee('query=24.15,-110.32', false);

            $volunteer->forceFill(['estado' => 'publicado', 'latitud' => 24.3217, 'longitud' => -110.3248])->save();
            $this->getJson('/api/voluntariados')->assertOk()
                ->assertJsonPath('voluntariados.0.ubicacionMapa.latitud', 24.3217);
            $volunteer->forceFill(['estado' => 'pendiente'])->save();
            $this->getJson($url)->assertForbidden();
            Sanctum::actingAs($owner);
            $this->getJson($url)->assertOk()->assertJsonPath('data.0.lat', 24.3217);
            $volunteer->forceFill(['estado' => 'cancelado'])->save();
            $this->get('/voluntariados/'.$volunteer->getKey())->assertOk()
                ->assertSee('query=24.3217,-110.3248', false);
        } finally {
            DB::connection('mongodb')->getMongoDB()->drop();
            DB::purge('mongodb');
        }
    }

    private function renderVolunteerDetail(array $images, array $materials, ?array $location = null): string
    {
        $volunteer = (object) [
            '_id' => 'example',
            'organizadorId' => 'organizer',
            'titulo' => 'Limpieza local',
            'descripcion' => 'Actividad ambiental',
            'categoria' => 'limpieza',
            'imagenes' => $images,
            'requisitos' => [],
            'materiales' => $materials,
            'fechaInicio' => null,
            'fechaFin' => null,
            'horaInicio' => null,
            'horaFin' => null,
            'estado' => 'publicado',
            'cupo' => 10,
        ];

        return view('voluntariados.show', [
            'voluntariado' => $volunteer,
            'organizadorNombre' => 'Equipo',
            'inicioFechaHora' => null,
            'finFechaHora' => null,
            'lugar' => null,
            'ubicacion' => $location,
            'isOwner' => false,
            'isAdmin' => false,
            'voluntariadoCancelado' => false,
            'voluntariadoFinalizado' => false,
            'inscritosCount' => 0,
        ])->render();
    }
}
