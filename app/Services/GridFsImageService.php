<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

    /**
     * @return array<int, string>
     */
    public function paths(iterable|string|null $images, ?string $legacyImage = null): array
    {
        if (is_string($images) && $images !== '') {
            return [$images];
        }

        if (is_iterable($images)) {
            $paths = collect($images)
                ->filter(fn ($path) => is_string($path) && $path !== '')
                ->values()
                ->all();
            if ($paths) {
                return $paths;
            }
        }

        return $legacyImage ? [$legacyImage] : [];
    }

    /**
     * @param  array<int, string>  $existing
     * @param  array<int, UploadedFile>  $uploads
     * @param  array<int, int|string>  $removedIndexes
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    public function applyCollectionChanges(
        array $existing,
        array $uploads,
        array $removedIndexes,
        int $maximum,
        string $directory,
        string $label,
    ): array {
        $existing = array_values($existing);
        $removedIndexes = array_values(array_unique(array_map('intval', $removedIndexes)));

        foreach ($removedIndexes as $index) {
            if (! array_key_exists($index, $existing)) {
                throw ValidationException::withMessages([
                    'imagenesEliminar' => 'Una de las imágenes seleccionadas para quitar ya no existe.',
                ]);
            }
        }

        $retained = array_values(array_filter(
            $existing,
            fn ($image, $index) => ! in_array($index, $removedIndexes, true),
            ARRAY_FILTER_USE_BOTH,
        ));
        if (count($retained) + count($uploads) > $maximum) {
            throw ValidationException::withMessages([
                'imagenes' => "Puedes conservar hasta {$maximum} imágenes por {$label}.",
            ]);
        }

        $newPaths = [];
        foreach ($uploads as $upload) {
            $path = Storage::disk('gridfs')->putFile($directory, $upload);
            if (! is_string($path)) {
                throw new \RuntimeException('No se pudo guardar una imagen en GridFS.');
            }
            $newPaths[] = $path;
        }

        $removed = array_values(array_filter(
            $existing,
            fn ($image, $index) => in_array($index, $removedIndexes, true),
            ARRAY_FILTER_USE_BOTH,
        ));

        return [[...$retained, ...$newPaths], $removed];
    }

    public function deleteStored(iterable $paths): void
    {
        foreach ($paths as $path) {
            if (is_string($path) && $path !== '' && ! filter_var($path, FILTER_VALIDATE_URL)) {
                Storage::disk('gridfs')->delete($path);
            }
        }
    }

    public function storeUploaded(UploadedFile $upload, string $directory): string
    {
        $path = Storage::disk('gridfs')->putFile($directory, $upload);
        if (! is_string($path)) {
            throw new \RuntimeException('No se pudo guardar una imagen en GridFS.');
        }

        return $path;
    }
}
