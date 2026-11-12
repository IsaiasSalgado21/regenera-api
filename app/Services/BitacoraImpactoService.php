<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\ImpactoVoluntariado;
use App\Models\Inscripcion;
use App\Models\Lugar;
use App\Models\Voluntariado;
use Illuminate\Support\Str;

class BitacoraImpactoService
{
    public function __construct(
        private readonly VoluntariadoWorkflow $workflow,
        private readonly VoluntariadoHorario $horario,
        private readonly ImpactoVoluntariadoService $catalogoImpacto,
    ) {}

    public function aggregate(
        iterable $voluntariados,
        iterable $inscripciones,
        iterable $asistencias,
        iterable $impactos,
        iterable $lugares,
        array $filters = [],
    ): array {
        $voluntariados = collect($voluntariados);
        $inscripciones = collect($inscripciones);
        $asistencias = collect($asistencias);
        $impactos = collect($impactos);
        $lugares = collect($lugares);
        $finalizados = $voluntariados
            ->filter(fn (Voluntariado $voluntariado) => $this->workflow->finalizado($voluntariado, $this->horario));
        $lugaresPorId = $lugares->keyBy(fn (Lugar $lugar) => (string) $lugar->_id);
        $categorias = $finalizados
            ->map(fn (Voluntariado $voluntariado) => Str::slug((string) $voluntariado->categoria))
            ->filter()
            ->unique()
            ->sort()
            ->map(fn (string $category) => [
                'id' => $category,
                'nombre' => $this->categoryLabel($category),
            ])
            ->values();
        $opcionesLugar = $finalizados
            ->pluck('lugarId')
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->map(fn (string $id) => [
                'id' => $id,
                'nombre' => $lugaresPorId->get($id)?->nombre ?? 'Ubicación no disponible',
                'municipio' => $lugaresPorId->get($id)?->municipio,
            ])
            ->sortBy('nombre')
            ->values();
        $estados = $finalizados->pluck('estado')->filter()->unique()->sort()->values();

        $selected = $finalizados->filter(function (Voluntariado $voluntariado) use ($filters): bool {
            $categoryFilter = Str::slug((string) ($filters['category'] ?? ''));
            if ($categoryFilter !== ''
                && Str::slug((string) $voluntariado->categoria) !== $categoryFilter) {
                return false;
            }
            if (($filters['placeId'] ?? '') !== ''
                && (string) $voluntariado->lugarId !== (string) $filters['placeId']) {
                return false;
            }
            if (($filters['state'] ?? '') !== ''
                && (string) $voluntariado->estado !== (string) $filters['state']) {
                return false;
            }

            $start = $this->horario->inicio($voluntariado);
            $startDate = $start?->toDateString();
            if (isset($filters['from']) && $filters['from'] !== '') {
                if (! $startDate || $startDate < $filters['from']) {
                    return false;
                }
            }
            if (isset($filters['to']) && $filters['to'] !== '') {
                if (! $startDate || $startDate > $filters['to']) {
                    return false;
                }
            }

            return true;
        })->values();

        $eventIds = $selected->map(fn (Voluntariado $voluntariado) => (string) $voluntariado->_id)->all();
        $eventById = $selected->keyBy(fn (Voluntariado $voluntariado) => (string) $voluntariado->_id);
        $activeRegistrations = [];
        foreach ($inscripciones as $inscripcion) {
            if (! in_array((string) $inscripcion->voluntariadoId, $eventIds, true)
                || in_array($inscripcion->estado, ['cancelada', 'reservando'], true)) {
                continue;
            }
            $participantId = (string) $inscripcion->usuarioId;
            if ($participantId !== '') {
                $activeRegistrations[$this->pairKey((string) $inscripcion->voluntariadoId, $participantId)] = true;
            }
        }

        $attendeesByEvent = [];
        $uniqueParticipants = [];
        foreach ($asistencias as $asistencia) {
            if (! $asistencia->confirmada) {
                continue;
            }
            $eventId = (string) $asistencia->voluntariadoId;
            $participantId = (string) $asistencia->usuarioId;
            $pairKey = $this->pairKey($eventId, $participantId);
            if (! isset($activeRegistrations[$pairKey]) || ! isset($eventById[$eventId])) {
                continue;
            }
            $attendeesByEvent[$eventId][$participantId] = true;
            $uniqueParticipants[$participantId] = true;
        }

        $hoursByEvent = [];
        $totalHours = 0.0;
        foreach ($selected as $voluntariado) {
            $eventId = (string) $voluntariado->_id;
            $start = $this->horario->inicio($voluntariado);
            $end = $this->horario->fin($voluntariado);
            $duration = $start && $end && $end->gt($start)
                ? $start->diffInSeconds($end) / 3600
                : 0.0;
            $hoursByEvent[$eventId] = $duration * count($attendeesByEvent[$eventId] ?? []);
            $totalHours += $hoursByEvent[$eventId];
        }

        $metricTotals = [
            'kilogramosResiduos' => 0.0,
            'kilometrosCosta' => 0.0,
            'plantasReforestadas' => 0.0,
            'superficieRecuperada' => 0.0,
        ];
        foreach ($impactos as $impacto) {
            $event = $eventById->get((string) $impacto->voluntariadoId);
            if (! $event
                || ! is_numeric($impacto->cantidad)
                || (float) $impacto->cantidad <= 0) {
                continue;
            }
            $availableMetrics = $this->catalogoImpacto->catalogForCategory($event->categoria ?: $event->titulo);
            if (! array_key_exists((string) $impacto->tipo, $availableMetrics)) {
                continue;
            }

            $unit = mb_strtolower(trim((string) $impacto->unidad));
            $amount = (float) $impacto->cantidad;
            switch ($impacto->tipo) {
                case 'residuos_recolectados':
                    if ($unit === 'kg') {
                        $metricTotals['kilogramosResiduos'] += $amount;
                    }
                    break;
                case 'costa_limpiada':
                    if ($unit === 'km') {
                        $metricTotals['kilometrosCosta'] += $amount;
                    }
                    break;
                case 'plantas_sembradas':
                    if ($unit === 'plantas') {
                        $metricTotals['plantasReforestadas'] += $amount;
                    }
                    break;
                case 'superficie_reforestada':
                case 'superficie_restaurada':
                    if (in_array($unit, ['m²', 'm2'], true)) {
                        $metricTotals['superficieRecuperada'] += $amount;
                    }
                    break;
            }
        }

        $evolution = [];
        $categoryCounts = [];
        $history = $selected->map(function (Voluntariado $voluntariado) use (
            &$evolution,
            &$categoryCounts,
            $attendeesByEvent,
            $hoursByEvent,
            $lugaresPorId,
        ): array {
            $eventId = (string) $voluntariado->_id;
            $date = $this->horario->inicio($voluntariado);
            $month = $date?->format('Y-m') ?? 'Sin fecha';
            $attendeeCount = count($attendeesByEvent[$eventId] ?? []);
            $evolution[$month] ??= ['voluntariados' => 0, 'participaciones' => 0, 'horas' => 0.0];
            $evolution[$month]['voluntariados']++;
            $evolution[$month]['participaciones'] += $attendeeCount;
            $evolution[$month]['horas'] += $hoursByEvent[$eventId] ?? 0;
            $category = Str::slug((string) ($voluntariado->categoria ?: 'sin-categoria'));
            $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;

            return [
                'id' => $eventId,
                'titulo' => (string) $voluntariado->titulo,
                'categoria' => $this->categoryLabel($category),
                'estado' => (string) $voluntariado->estado,
                'fechaInicio' => $date?->toDateString(),
                'lugar' => $lugaresPorId->get((string) $voluntariado->lugarId)?->nombre ?? 'Ubicación no disponible',
                'participantes' => $attendeeCount,
                'horas' => round($hoursByEvent[$eventId] ?? 0, 2),
            ];
        })->sortByDesc('fechaInicio')->values();

        ksort($evolution);
        arsort($categoryCounts);

        return [
            'kpis' => [
                'voluntariados' => $selected->count(),
                'participantes' => count($uniqueParticipants),
                'horas' => round($totalHours, 2),
                ...array_map(fn (float $amount) => round($amount, 2), $metricTotals),
                'ubicaciones' => $selected->pluck('lugarId')->filter()->map(fn ($id) => (string) $id)->unique()->count(),
            ],
            'evolucion' => collect($evolution)
                ->map(fn (array $row, string $month) => ['periodo' => $month, ...$row])
                ->values(),
            'categorias' => collect($categoryCounts)
                ->map(fn (int $count, string $category) => [
                    'categoria' => $this->categoryLabel($category),
                    'voluntariados' => $count,
                ])
                ->values(),
            'historial' => $history,
            'opciones' => [
                'categorias' => $categorias,
                'lugares' => $opcionesLugar,
                'estados' => $estados,
            ],
        ];
    }

    private function categoryLabel(string $category): string
    {
        return match ($category) {
            'conservacion' => 'Conservación',
            'limpieza-de-playas' => 'Limpieza de playas',
            'limpieza-urbana' => 'Limpieza urbana',
            'reforestacion' => 'Reforestación',
            'restauracion-ambiental' => 'Restauración ambiental',
            'sin-categoria' => 'Sin categoría',
            default => Str::headline($category),
        };
    }

    private function pairKey(string $eventId, string $participantId): string
    {
        return $eventId.'|'.$participantId;
    }
}