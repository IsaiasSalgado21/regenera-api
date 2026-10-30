<?php

namespace App\Services;

class GridFsImageService
{
    public function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        // Conserva compatibilidad con imágenes externas ya existentes.
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return route('imagenes.mostrar', ['path' => ltrim($path, '/')]);
    }

    /**
     * Convierte una colección de rutas GridFS a URLs consumibles por el frontend.
     *
     * @param  iterable<int, mixed>  $paths
     * @return array<int, string>
     */
    public function urls(iterable $paths): array
    {
        return collect($paths)
            ->map(fn ($path) => $this->url(is_string($path) ? $path : null))
            ->filter()
            ->values()
            ->all();
    }
}
