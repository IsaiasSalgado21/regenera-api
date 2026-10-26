<?php

namespace App\Http\Controllers;

use App\Models\FloraFauna;
use App\Models\Inscripcion;
use App\Models\Lugar;
use App\Models\Voluntariado;
use App\Services\FloraFaunaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MapApiController extends Controller
{
    public function __construct(private readonly FloraFaunaService $floraFaunaService) {}

    public function voluntariados(Request $request)
    {
        $filters = $request->validate([
            'categoria' => 'sometimes|nullable|string|max:100',
            'fecha_desde' => 'sometimes|nullable|date',
            'fecha_hasta' => 'sometimes|nullable|date|after_or_equal:fecha_desde',
            'lugar_id' => 'sometimes|nullable|string|max:100',
            'disponibles' => 'sometimes|nullable|boolean',
        ]);

        $lugares = Lugar::query()
            ->where('estado', 'activo')
            ->get()
            ->filter(fn (Lugar $lugar) => $this->hasCoordinates($lugar->latitud, $lugar->longitud))
            ->keyBy(fn (Lugar $lugar) => (string) $lugar->_id);

        $query = Voluntariado::query()->where('estado', 'publicado');

        if (! empty($filters['categoria'])) {
            $query->where('categoria', $filters['categoria']);
        }

        if (! empty($filters['fecha_desde'])) {
            $query->where('fechaInicio', '>=', Carbon::parse($filters['fecha_desde'])->startOfDay());
        }

        if (! empty($filters['fecha_hasta'])) {
            $query->where('fechaInicio', '<=', Carbon::parse($filters['fecha_hasta'])->endOfDay());
        }

        if (! empty($filters['lugar_id'])) {
            $query->where('lugarId', $filters['lugar_id']);
        }

        $voluntariados = $query->orderBy('fechaInicio')->get()
            ->filter(fn (Voluntariado $voluntariado) => $lugares->has((string) $voluntariado->lugarId))
            ->values();
        $ids = $voluntariados->map(fn (Voluntariado $voluntariado) => (string) $voluntariado->_id);
        $inscripcionesPorVoluntariado = Inscripcion::query()
            ->whereIn('voluntariadoId', $ids->all())
            ->whereIn('estado', ['inscrita', 'activa'])
            ->get(['voluntariadoId'])
            ->groupBy('voluntariadoId')
            ->map(fn ($inscripciones) => $inscripciones->count());

        $data = $voluntariados->map(function (Voluntariado $voluntariado) use ($lugares, $inscripcionesPorVoluntariado) {
            $lugar = $lugares->get((string) $voluntariado->lugarId);
            $cupo = (int) ($voluntariado->cupo ?? 0);
            $inscripcionesActivas = (int) $inscripcionesPorVoluntariado->get((string) $voluntariado->_id, 0);
            $lleno = $cupo < 1 || $inscripcionesActivas >= $cupo;

            return [
                'id' => (string) $voluntariado->_id,
                'tipo' => 'voluntariado',
                'titulo' => $voluntariado->titulo,
                'descripcion' => $voluntariado->descripcion,
                'categoria' => $voluntariado->categoria,
                'estado' => $voluntariado->estado,
                'fecha_inicio' => $voluntariado->fechaInicio?->toISOString(),
                'lugar_id' => (string) $voluntariado->lugarId,
                'lugar_nombre' => $lugar->nombre,
                'cupo' => $cupo,
                'inscripciones_activas' => $inscripcionesActivas,
                'lleno' => $lleno,
                'lat' => (float) $lugar->latitud,
                'lng' => (float) $lugar->longitud,
            ];
        })->filter(function (array $voluntariado) use ($filters) {
            if (! array_key_exists('disponibles', $filters) || $filters['disponibles'] === null) {
                return true;
            }

            return $voluntariado['lleno'] === ! filter_var($filters['disponibles'], FILTER_VALIDATE_BOOLEAN);
        })->values();

        return response()->json([
            'success' => true,
            'count' => $data->count(),
            'data' => $data,
        ]);
    }

    public function lugares(Request $request)
    {
        $filters = $request->validate([
            'tipo' => 'sometimes|nullable|string|max:100',
        ]);

        $query = Lugar::query()->where('estado', 'activo');

        if (! empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        $data = $query->orderBy('nombre')->get()
            ->filter(fn (Lugar $lugar) => $this->hasCoordinates($lugar->latitud, $lugar->longitud))
            ->map(fn (Lugar $lugar) => [
                'id' => (string) $lugar->_id,
                'tipo' => 'lugar',
                'nombre' => $lugar->nombre,
                'descripcion' => $lugar->descripcion,
                'municipio' => $lugar->municipio,
                'tipo_lugar' => $lugar->tipo,
                'lat' => (float) $lugar->latitud,
                'lng' => (float) $lugar->longitud,
            ])->values();

        return response()->json([
            'success' => true,
            'count' => $data->count(),
            'data' => $data,
        ]);
    }

    public function floraFauna(Request $request)
    {
        $filters = $request->validate([
            'tipo' => 'sometimes|nullable|in:flora,fauna',
            'estado_conservacion' => 'sometimes|nullable|string|max:100',
        ]);

        $query = FloraFauna::query()->where('estado', 'publicado');

        if (! empty($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        $data = $query->orderBy('nombre')->get()
            ->filter(function (FloraFauna $registro) use ($filters) {
                return empty($filters['estado_conservacion'])
                    || $this->normalizeConservationStatus($this->conservationStatus($registro))
                        === $filters['estado_conservacion'];
            })
            ->filter(function (FloraFauna $registro) {
                $ubicacion = $this->floraFaunaService->ubicacionVisible($registro);

                return is_array($ubicacion)
                    && $this->hasCoordinates($ubicacion['latitud'] ?? null, $ubicacion['longitud'] ?? null);
            })
            ->map(function (FloraFauna $registro) {
                $registroPresentado = $this->floraFaunaService->present($registro);
                $ubicacion = $registroPresentado['ubicacion'];
                $zonaAproximada = $registroPresentado['zonaAproximada'];

                return [
                    'id' => (string) $registro->_id,
                    'tipo' => $registro->tipo,
                    'nombre' => $registro->nombre,
                    'nombre_cientifico' => $registro->nombreCientifico,
                    'descripcion' => $registro->descripcion,
                    'estado_conservacion' => $registroPresentado['estadoConservacion'],
                    'lugares_relacionados' => collect($registro->lugaresRelacionados ?? [])
                        ->map(fn ($id) => (string) $id)
                        ->values(),
                    'lat' => (float) $ubicacion['latitud'],
                    'lng' => (float) $ubicacion['longitud'],
                    'zona_aproximada' => $zonaAproximada,
                    'radio_metros' => $zonaAproximada ? 5000 : null,
                ];
            })->values();

        return response()->json([
            'success' => true,
            'count' => $data->count(),
            'data' => $data,
        ]);
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
