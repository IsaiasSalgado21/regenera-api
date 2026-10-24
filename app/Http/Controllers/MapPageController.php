<?php

namespace App\Http\Controllers;

use App\Models\FloraFauna;
use App\Models\Lugar;
use App\Models\Voluntariado;
use Illuminate\Support\Collection;

class MapPageController extends Controller
{
    public function index()
    {
        $lugares = Lugar::query()
            ->where('estado', 'activo')
            ->orderBy('nombre')
            ->get()
            ->filter(fn (Lugar $lugar) => $this->hasCoordinates($lugar->latitud, $lugar->longitud))
            ->map(fn (Lugar $lugar) => [
                'id' => (string) $lugar->_id,
                'tipo' => 'lugar',
                'categoria' => 'lugares',
                'nombre' => $lugar->nombre,
                'descripcion' => $lugar->descripcion,
                'municipio' => $lugar->municipio,
                'detalle' => $lugar->tipo,
                'latitud' => (float) $lugar->latitud,
                'longitud' => (float) $lugar->longitud,
                'url' => null,
            ]);

        $lugaresPorId = $lugares->keyBy('id');

        $especies = FloraFauna::query()
            ->orderBy('nombre')
            ->get()
            ->flatMap(fn (FloraFauna $especie) => $this->speciesMarkers($especie, $lugaresPorId));

        $actividades = Voluntariado::query()
            ->where('estado', 'publicado')
            ->where('fechaInicio', '>=', now())
            ->orderBy('fechaInicio')
            ->get()
            ->filter(fn (Voluntariado $actividad) => $lugaresPorId->has((string) $actividad->lugarId))
            ->map(function (Voluntariado $actividad) use ($lugaresPorId) {
                $lugar = $lugaresPorId->get((string) $actividad->lugarId);

                return [
                    'id' => (string) $actividad->_id,
                    'tipo' => 'actividad',
                    'categoria' => 'actividades',
                    'nombre' => $actividad->titulo,
                    'descripcion' => $actividad->descripcion,
                    'municipio' => $lugar['municipio'],
                    'detalle' => $actividad->fechaInicio?->format('d/m/Y'),
                    'latitud' => $lugar['latitud'],
                    'longitud' => $lugar['longitud'],
                    'url' => url('/voluntariados/'.$actividad->_id),
                ];
            });

        return view('mapa', [
            'marcadores' => $lugares->concat($especies)->concat($actividades)->values(),
            'municipios' => $lugares->pluck('municipio')->filter()->unique()->sort()->values(),
        ]);
    }

    private function speciesMarkers(FloraFauna $especie, Collection $lugaresPorId): Collection
    {
        $ubicacion = $especie->ubicacion;
        $latitud = is_array($ubicacion) ? ($ubicacion['latitud'] ?? null) : null;
        $longitud = is_array($ubicacion) ? ($ubicacion['longitud'] ?? null) : null;

        $base = [
            'id' => (string) $especie->_id,
            'tipo' => $especie->tipo,
            'categoria' => $especie->tipo === 'flora' ? 'flora' : 'fauna',
            'nombre' => $especie->nombre,
            'descripcion' => $especie->descripcion,
            'detalle' => $especie->nombreCientifico,
            'url' => url('/flora/'.$especie->_id),
        ];
        $lugarRelacionado = collect($especie->lugaresRelacionados ?? [])
            ->map(fn ($id) => $lugaresPorId->get((string) $id))
            ->first();

        if ($this->hasCoordinates($latitud, $longitud)) {
            return collect([array_merge($base, [
                'municipio' => (is_array($ubicacion) ? ($ubicacion['municipio'] ?? null) : null)
                    ?: ($lugarRelacionado['municipio'] ?? ''),
                'latitud' => (float) $latitud,
                'longitud' => (float) $longitud,
            ])]);
        }

        return collect($especie->lugaresRelacionados ?? [])
            ->map(fn ($id) => $lugaresPorId->get((string) $id))
            ->filter()
            ->map(fn (array $lugar) => array_merge($base, [
                'municipio' => $lugar['municipio'],
                'latitud' => $lugar['latitud'],
                'longitud' => $lugar['longitud'],
            ]))
            ->values();
    }

    private function hasCoordinates(mixed $latitud, mixed $longitud): bool
    {
        return is_numeric($latitud)
            && is_numeric($longitud)
            && (float) $latitud >= -90
            && (float) $latitud <= 90
            && (float) $longitud >= -180
            && (float) $longitud <= 180;
    }
}
