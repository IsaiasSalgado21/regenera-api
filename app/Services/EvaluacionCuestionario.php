<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class EvaluacionCuestionario
{
    public function preguntasPublicas(array $preguntas): array
    {
        return collect($preguntas)
            ->map(fn (array $pregunta) => [
                'id' => (string) $pregunta['id'],
                'texto' => (string) $pregunta['texto'],
                'opciones' => array_values($pregunta['opciones']),
            ])
            ->values()
            ->all();
    }

    public function evaluarRespuesta(array $pregunta, int $opcion): array
    {
        if (! array_key_exists($opcion, $pregunta['opciones'] ?? [])) {
            throw ValidationException::withMessages([
                'opcion' => 'Selecciona una de las opciones disponibles.',
            ]);
        }

        $correcta = (int) $pregunta['respuestaCorrecta'];

        return [
            'correcta' => $opcion === $correcta,
            'respuestaCorrecta' => (string) $pregunta['opciones'][$correcta],
            'explicacion' => (string) $pregunta['explicacion'],
        ];
    }

    public function puntaje(int $correctas, int $total): float
    {
        if ($total < 1) {
            return 0.0;
        }

        return round(($correctas / $total) * 100, 2);
    }

    public function aprobado(float $puntaje, float $minimo): bool
    {
        return $puntaje >= $minimo;
    }
}