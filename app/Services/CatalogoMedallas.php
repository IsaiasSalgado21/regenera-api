<?php

namespace App\Services;

use App\Models\Cuestionario;
use App\Models\Medalla;
use Illuminate\Support\Collection;

class CatalogoMedallas
{
    public function todas(): Collection
    {
        $catalogo = collect();
        $versiones = Cuestionario::query()->orderByDesc('version')->get();
        foreach ($versiones as $quiz) {
            $medalla = $quiz->medalla ?? [];
            $slug = $medalla['slug'] ?? null;
            if (! is_string($slug) || $slug === '' || $catalogo->has($slug)) {
                continue;
            }
            $catalogo->put($slug, [
                'slug' => $slug,
                'nombre' => (string) ($medalla['nombre'] ?? $slug),
                'descripcion' => (string) ($medalla['descripcion'] ?? ''),
                'icono' => (string) ($medalla['icono'] ?? 'award'),
                'activo' => true,
                'legada' => true,
            ]);
        }

        foreach (Medalla::query()->get() as $medalla) {
            $catalogo->put((string) $medalla->slug, [
                'slug' => (string) $medalla->slug,
                'nombre' => (string) $medalla->nombre,
                'descripcion' => (string) $medalla->descripcion,
                'icono' => (string) $medalla->icono,
                'activo' => (bool) $medalla->activo,
                'legada' => false,
            ]);
        }

        return $catalogo;
    }
}
