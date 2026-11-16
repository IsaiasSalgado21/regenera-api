<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EvaluacionCuestionario
{
    public function preguntasPublicas(array $preguntas): array
    {
        return collect($preguntas)
            ->map(function (array $pregunta): array {
                $publica = [
                    'id' => (string) $pregunta['id'],
                    'texto' => (string) $pregunta['texto'],
                    'opciones' => array_values($pregunta['opciones'] ?? []),
                ];
                if (isset($pregunta['tipo'])) {
                    $publica['tipo'] = $pregunta['tipo'];
                }

                return $publica;
            })
            ->values()
            ->all();
    }

    public function evaluarRespuesta(array $pregunta, int|array|string $opcion): array
    {
        $tipo = $pregunta['tipo'] ?? 'unica';
        if ($tipo === 'corta') {
            if (! is_string($opcion) || trim($opcion) === '') {
                throw ValidationException::withMessages(['respuesta' => 'Escribe una respuesta.']);
            }
            $respuestas = $pregunta['respuestasAceptadas'];
            $correcta = collect($respuestas)->contains(
                fn (string $respuesta) => $this->normalizarRespuesta($respuesta) === $this->normalizarRespuesta($opcion),
            );

            return [
                'correcta' => $correcta,
                'credito' => $correcta ? 1.0 : 0.0,
                'respuestaCorrecta' => implode(' / ', $respuestas),
                'explicacion' => (string) $pregunta['explicacion'],
            ];
        }

        if ($tipo === 'multiple') {
            $options = $pregunta['opciones'] ?? [];
            $correct = $pregunta['respuestasCorrectas'] ?? [];
            if (! is_array($opcion) || $opcion === [] || count($opcion) !== count(array_unique($opcion))
                || collect($opcion)->contains(fn ($index) => ! is_int($index) || ! array_key_exists($index, $options))) {
                throw ValidationException::withMessages(['respuesta' => 'Selecciona opciones válidas sin repetir.']);
            }
            $hits = count(array_intersect($opcion, $correct));
            $errors = count(array_diff($opcion, $correct));
            $credit = max(0.0, ($hits - $errors) / count($correct));

            return [
                'correcta' => $hits === count($correct) && $errors === 0,
                'credito' => $credit,
                'respuestaCorrecta' => implode(' / ', array_map(fn ($index) => $options[$index], $correct)),
                'explicacion' => (string) $pregunta['explicacion'],
            ];
        }

        if (! is_int($opcion)) {
            throw ValidationException::withMessages(['opcion' => 'Selecciona una de las opciones disponibles.']);
        }
        if (! array_key_exists($opcion, $pregunta['opciones'] ?? [])) {
            throw ValidationException::withMessages([
                'opcion' => 'Selecciona una de las opciones disponibles.',
            ]);
        }

        $correcta = (int) $pregunta['respuestaCorrecta'];

        $result = [
            'correcta' => $opcion === $correcta,
            'respuestaCorrecta' => (string) $pregunta['opciones'][$correcta],
            'explicacion' => (string) $pregunta['explicacion'],
        ];
        if (isset($pregunta['tipo'])) {
            $result['credito'] = $result['correcta'] ? 1.0 : 0.0;
        }

        return $result;
    }

    private function normalizarRespuesta(string $respuesta): string
    {
        return preg_replace('/\s+/u', ' ', trim(mb_strtolower(Str::ascii($respuesta))));
    }

    public function puntajePonderado(array $preguntas, array $respuestas): float
    {
        $total = 0;
        $obtained = 0.0;
        foreach ($preguntas as $pregunta) {
            $weight = (int) ($pregunta['peso'] ?? 1);
            $answer = $respuestas[$pregunta['id']];
            $credit = $answer['credito'] ?? ((bool) $answer['correcta'] ? 1.0 : 0.0);
            $total += $weight;
            $obtained += $weight * $credit;
        }

        return $total ? round($obtained / $total * 100, 2) : 0.0;
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