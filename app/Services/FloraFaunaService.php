<?php

namespace App\Services;

use App\Models\FloraFauna;
use Illuminate\Support\Str;

class FloraFaunaService
{
    public function ubicacionVisible(FloraFauna $registro, bool $esAdmin = false): ?array
    {
        $ubicacion = $registro->ubicacion;

        if ($ubicacion instanceof \Traversable) {
            $ubicacion = iterator_to_array($ubicacion);
        } elseif (is_object($ubicacion)) {
            $ubicacion = get_object_vars($ubicacion);
        }

        if (! is_array($ubicacion)) {
            return null;
        }

        if (! $esAdmin && $this->esSensible($registro)) {
            if (isset($ubicacion['latitud']) && is_numeric($ubicacion['latitud'])) {
                $ubicacion['latitud'] = round((float) $ubicacion['latitud'], 2);
            }

            if (isset($ubicacion['longitud']) && is_numeric($ubicacion['longitud'])) {
                $ubicacion['longitud'] = round((float) $ubicacion['longitud'], 2);
            }
        }

        return $ubicacion;
    }

    public function present(FloraFauna $registro, bool $esAdmin = false, bool $mostrarCreador = false): array
    {
        return [
            'id' => (string) $registro->_id,
            'nombre' => $registro->nombre,
            'nombreCientifico' => $registro->nombreCientifico,
            'tipo' => $registro->tipo,
            'descripcion' => $registro->descripcion,
            'habitat' => $registro->habitat,
            'estadoConservacion' => $registro->getAttribute('estadoConservacion')
                ?? $registro->getAttribute('estadoConservation'),
            'estado' => $registro->estado,
            'imagenes' => $this->imagenes($registro),
            'ubicacion' => $this->ubicacionVisible($registro, $esAdmin),
            'lugaresRelacionados' => collect($registro->lugaresRelacionados ?? [])
                ->map(fn ($id) => (string) $id)
                ->values()
                ->all(),
            'fuenteInformacion' => $registro->fuenteInformacion,
            'creadoPor' => $esAdmin || $mostrarCreador ? (string) $registro->creadoPor : null,
            'zonaAproximada' => ! $esAdmin && $this->esSensible($registro),
        ];
    }

    public function imagenes(FloraFauna $registro): array
    {
        return collect($registro->imagenes ?? [])
            ->values()
            ->all();
    }

    private function esSensible(FloraFauna $registro): bool
    {
        $estado = $registro->getAttribute('estadoConservacion')
            ?? $registro->getAttribute('estadoConservation');
        $normalizado = Str::of((string) $estado)
            ->lower()
            ->ascii()
            ->replace([' ', '-'], '_')
            ->toString();

        return in_array($normalizado, [
            'en_peligro',
            'en_peligro_critico',
            'vulnerable',
            'en_peligro_de_extincion',
        ], true);
    }
}
