<?php

namespace App\Services;

use RuntimeException;

class BajaCaliforniaSurBoundary
{
    private static ?array $geometry = null;

    public function contains(mixed $latitude, mixed $longitude): bool
    {
        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return false;
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;
        $geometry = $this->geometry();
        $bounds = $geometry['bounds'];

        if (
            $latitude < $bounds[1]
            || $latitude > $bounds[3]
            || $longitude < $bounds[0]
            || $longitude > $bounds[2]
        ) {
            return false;
        }

        foreach ($geometry['polygons'] as $polygon) {
            $polygonBounds = $polygon['bounds'];
            if (
                $longitude < $polygonBounds[0]
                || $latitude < $polygonBounds[1]
                || $longitude > $polygonBounds[2]
                || $latitude > $polygonBounds[3]
            ) {
                continue;
            }

            if ($this->pointInRing($longitude, $latitude, $polygon['rings'][0])) {
                $insideHole = false;
                foreach (array_slice($polygon['rings'], 1) as $hole) {
                    if ($this->pointInRing($longitude, $latitude, $hole)) {
                        $insideHole = true;
                        break;
                    }
                }

                if (! $insideHole) {
                    return true;
                }
            }
        }

        return false;
    }

    private function pointInRing(float $longitude, float $latitude, array $ring): bool
    {
        $inside = false;
        $pointCount = count($ring);

        for ($index = 0, $previous = $pointCount - 1; $index < $pointCount; $previous = $index++) {
            [$startLongitude, $startLatitude] = $ring[$index];
            [$endLongitude, $endLatitude] = $ring[$previous];
            $cross = ($longitude - $startLongitude) * ($endLatitude - $startLatitude)
                - ($latitude - $startLatitude) * ($endLongitude - $startLongitude);
            $withinSegment = $longitude >= min($startLongitude, $endLongitude) - 1e-10
                && $longitude <= max($startLongitude, $endLongitude) + 1e-10
                && $latitude >= min($startLatitude, $endLatitude) - 1e-10
                && $latitude <= max($startLatitude, $endLatitude) + 1e-10;

            if (abs($cross) < 1e-10 && $withinSegment) {
                return true;
            }

            $intersects = ($startLatitude > $latitude) !== ($endLatitude > $latitude)
                && $longitude < ($endLongitude - $startLongitude) * ($latitude - $startLatitude)
                    / ($endLatitude - $startLatitude) + $startLongitude;
            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    private function geometry(): array
    {
        if (self::$geometry !== null) {
            return self::$geometry;
        }

        $path = resource_path('data/baja-california-sur.json');
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException('No se pudo leer el límite geográfico de Baja California Sur.');
        }

        $boundary = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (($boundary['geometry']['type'] ?? null) !== 'MultiPolygon') {
            throw new RuntimeException('El límite geográfico de Baja California Sur no tiene un formato válido.');
        }

        $coordinates = $boundary['geometry']['coordinates'];
        $polygons = array_map(function (array $rings): array {
            $longitudes = array_column($rings[0], 0);
            $latitudes = array_column($rings[0], 1);
            $bounds = [
                min($longitudes),
                min($latitudes),
                max($longitudes),
                max($latitudes),
            ];

            return [
                'rings' => $rings,
                'bounds' => $bounds,
                'area' => ($bounds[2] - $bounds[0]) * ($bounds[3] - $bounds[1]),
            ];
        }, $coordinates);
        usort($polygons, fn (array $left, array $right): int => $right['area'] <=> $left['area']);

        $positions = array_merge(...array_map(
            fn (array $polygon) => array_merge(...$polygon['rings']),
            $polygons,
        ));
        $longitudes = array_column($positions, 0);
        $latitudes = array_column($positions, 1);

        self::$geometry = [
            'polygons' => $polygons,
            'bounds' => [
                min($longitudes),
                min($latitudes),
                max($longitudes),
                max($latitudes),
            ],
        ];

        return self::$geometry;
    }
}
