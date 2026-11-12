<?php

namespace Tests\Feature;

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
}
