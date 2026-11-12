<?php

namespace App\Support;

use Illuminate\Support\Str;

class VoluntariadoMaterialSymbol
{
    public static function for(string $material): ?string
    {
        $normalized = Str::lower(Str::ascii($material));
        $symbols = [
            'guantes' => '/\bguantes?\b/',
            'bloqueador-solar' => '/\b(?:bloqueador(?:es)?|protector(?:es)? solar(?:es)?|proteccion solar)\b/',
            'agua' => '/\bagua\b/',
            'gorra' => '/\b(?:gorras?|sombreros?)\b/',
            'bolsa-residuos' => '/\bbolsas?\b/',
            'calzado' => '/\b(?:calzado|zapatos?|tenis)\b/',
        ];

        foreach ($symbols as $symbol => $pattern) {
            if (preg_match($pattern, $normalized)) {
                return $symbol;
            }
        }

        return null;
    }
}
