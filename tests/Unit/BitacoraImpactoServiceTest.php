<?php

namespace Tests\Unit;

use App\Models\Asistencia;
use App\Models\ImpactoVoluntariado;
use App\Models\Inscripcion;
use App\Models\Lugar;
use App\Models\Voluntariado;
use App\Services\BitacoraImpactoService;
use App\Services\ImpactoVoluntariadoService;
use App\Services\VoluntariadoHorario;
use App\Services\VoluntariadoWorkflow;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;

class BitacoraImpactoServiceTest extends TestCase
{
    public function test_it_deduplicates_attendees_and_aggregates_only_eligible_finalized_impact(): void
    {
        $first = $this->voluntariado('event-1', 'finalizado', Carbon::now()->subMonths(2), 'limpieza-de-playas', 'place-1');
        $second = $this->voluntariado('event-2', 'publicado', Carbon::now()->subMonth(), 'reforestacion', 'place-2');
        $future = $this->voluntariado('event-3', 'publicado', Carbon::now()->addMonth(), 'limpieza-de-playas', 'place-1');
        $cancelled = $this->voluntariado('event-4', 'cancelado', Carbon::now()->subMonth(), 'reforestacion', 'place-2');

        $inscripciones = new Collection([
            $this->inscripcion('event-1', 'user-1'),
            $this->inscripcion('event-1', 'user-2'),
            $this->inscripcion('event-1', 'user-6', 'cancelada'),
            $this->inscripcion('event-2', 'user-1'),
            $this->inscripcion('event-2', 'user-3'),
            $this->inscripcion('event-3', 'user-4'),
            $this->inscripcion('event-4', 'user-5'),
        ]);
        $asistencias = new Collection([
            $this->asistencia('event-1', 'user-1'),
            $this->asistencia('event-1', 'user-1'),
            $this->asistencia('event-1', 'user-2', false),
            $this->asistencia('event-1', 'user-6'),
            $this->asistencia('event-2', 'user-1'),
            $this->asistencia('event-2', 'user-3'),
            $this->asistencia('event-3', 'user-4'),
            $this->asistencia('event-4', 'user-5'),
        ]);
        $impactos = new Collection([
            $this->impacto('event-1', 'residuos_recolectados', 24, 'kg'),
            $this->impacto('event-1', 'residuos_recolectados', 5, 'g'),
            $this->impacto('event-1', 'residuos_reciclados', 8, 'kg'),
            $this->impacto('event-1', 'plantas_sembradas', 900, 'plantas'),
            $this->impacto('event-1', 'costa_limpiada', 1.5, 'km'),
            $this->impacto('event-2', 'plantas_sembradas', 40, 'plantas'),
            $this->impacto('event-2', 'superficie_reforestada', 100, 'm²'),
            $this->impacto('event-3', 'residuos_recolectados', 900, 'kg'),
            $this->impacto('event-4', 'plantas_sembradas', 900, 'plantas'),
        ]);
        $lugares = new Collection([
            $this->lugar('place-1', 'Playa Balandra'),
            $this->lugar('place-2', 'Parque Revolución'),
        ]);

        $result = $this->service()->aggregate(
            new Collection([$first, $second, $future, $cancelled]),
            $inscripciones,
            $asistencias,
            $impactos,
            $lugares,
        );

        self::assertSame([
            'voluntariados' => 2,
            'participantes' => 2,
            'horas' => 6.0,
            'kilogramosResiduos' => 24.0,
            'kilometrosCosta' => 1.5,
            'plantasReforestadas' => 40.0,
            'superficieRecuperada' => 100.0,
            'ubicaciones' => 2,
        ], $result['kpis']);
        self::assertCount(2, $result['historial']);
        self::assertCount(2, $result['evolucion']);
    }

    public function test_filters_apply_to_finalized_activity_date_category_location_and_state(): void
    {
        $older = $this->voluntariado('event-1', 'finalizado', Carbon::now()->subMonths(2), 'limpieza-de-playas', 'place-1');
        $newer = $this->voluntariado('event-2', 'publicado', Carbon::now()->subMonth(), 'reforestacion', 'place-2');

        $result = $this->service()->aggregate(
            new Collection([$older, $newer]),
            new Collection,
            new Collection,
            new Collection,
            new Collection,
            [
                'from' => Carbon::now()->subMonth()->toDateString(),
                'category' => 'reforestacion',
                'placeId' => 'place-2',
                'state' => 'publicado',
            ],
        );

        self::assertSame(1, $result['kpis']['voluntariados']);
        self::assertSame('event-2', $result['historial']->first()['id']);
    }

    private function service(): BitacoraImpactoService
    {
        return new BitacoraImpactoService(
            new VoluntariadoWorkflow,
            new VoluntariadoHorario,
            new ImpactoVoluntariadoService,
        );
    }

    private function voluntariado(string $id, string $state, Carbon $start, string $category, string $placeId): Voluntariado
    {
        $voluntariado = new Voluntariado([
            'titulo' => $id,
            'estado' => $state,
            'categoria' => $category,
            'lugarId' => $placeId,
            'fechaInicio' => $start->toDateString(),
            'fechaFin' => $start->toDateString(),
            'horaInicio' => '08:00',
            'horaFin' => '10:00',
        ]);
        $voluntariado->setAttribute('_id', $id);

        return $voluntariado;
    }

    private function inscripcion(string $eventId, string $userId, string $state = 'inscrita'): Inscripcion
    {
        return new Inscripcion([
            'voluntariadoId' => $eventId,
            'usuarioId' => $userId,
            'estado' => $state,
        ]);
    }

    private function asistencia(string $eventId, string $userId, bool $confirmed = true): Asistencia
    {
        return new Asistencia([
            'voluntariadoId' => $eventId,
            'usuarioId' => $userId,
            'confirmada' => $confirmed,
        ]);
    }

    private function impacto(string $eventId, string $type, float $amount, string $unit): ImpactoVoluntariado
    {
        return new ImpactoVoluntariado([
            'voluntariadoId' => $eventId,
            'tipo' => $type,
            'cantidad' => $amount,
            'unidad' => $unit,
        ]);
    }

    private function lugar(string $id, string $name): Lugar
    {
        $lugar = new Lugar(['nombre' => $name, 'municipio' => 'La Paz']);
        $lugar->setAttribute('_id', $id);

        return $lugar;
    }
}