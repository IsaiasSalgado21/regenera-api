<?php

namespace Tests\Unit;

use App\Models\FloraFauna;
use App\Services\FloraFaunaService;
use PHPUnit\Framework\TestCase;

class FloraFaunaServiceTest extends TestCase
{
    public function test_sensitive_locations_are_rounded_for_non_admins_only(): void
    {
        $registro = new FloraFauna([
            'estadoConservacion' => 'en_peligro',
            'ubicacion' => [
                'nombre' => 'Bahía de prueba',
                'latitud' => 24.1234,
                'longitud' => -110.5678,
            ],
        ]);
        $service = new FloraFaunaService;

        $this->assertSame(24.12, $service->ubicacionVisible($registro)['latitud']);
        $this->assertSame(-110.57, $service->ubicacionVisible($registro)['longitud']);
        $this->assertSame(24.1234, $service->ubicacionVisible($registro, true)['latitud']);
        $this->assertSame(-110.5678, $service->ubicacionVisible($registro, true)['longitud']);
    }
}
