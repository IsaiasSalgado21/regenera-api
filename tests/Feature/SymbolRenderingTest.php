<?php

namespace Tests\Feature;

use App\Support\VoluntariadoMaterialSymbol;
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

    private function renderVolunteerDetail(array $images, array $materials): string
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
            'ubicacion' => null,
            'isOwner' => false,
            'isAdmin' => false,
            'voluntariadoCancelado' => false,
            'voluntariadoFinalizado' => false,
            'inscritosCount' => 0,
        ])->render();
    }
}
