<?php

namespace App\Services;

use App\Models\Lugar;
use App\Models\Voluntariado;

class VoluntariadoUbicacion
{
    public function __construct(private readonly BajaCaliforniaSurBoundary $boundary) {}

    public function resolver(Voluntariado $voluntariado, ?Lugar $lugar): ?array
    {
        foreach ([
            [$voluntariado->latitud, $voluntariado->longitud],
            [$lugar?->latitud, $lugar?->longitud],
        ] as [$latitud, $longitud]) {
            if ($this->boundary->contains($latitud, $longitud)) {
                return ['latitud' => (float) $latitud, 'longitud' => (float) $longitud];
            }
        }

        return null;
    }
}
