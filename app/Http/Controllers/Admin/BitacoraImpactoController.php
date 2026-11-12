<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use App\Models\ImpactoVoluntariado;
use App\Models\Inscripcion;
use App\Models\Lugar;
use App\Models\Voluntariado;
use App\Services\BitacoraImpactoService;
use App\Services\VoluntariadoHorario;
use App\Services\VoluntariadoWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class BitacoraImpactoController extends Controller
{
    public function __construct(
        private readonly BitacoraImpactoService $bitacora,
        private readonly VoluntariadoWorkflow $workflow,
        private readonly VoluntariadoHorario $horario,
    ) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'lugarId' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'in:finalizado,publicado'],
        ]);

        $finalizados = Voluntariado::query()
            ->get()
            ->filter(fn (Voluntariado $voluntariado) => $this->workflow->finalizado($voluntariado, $this->horario))
            ->values();
        $eventIds = $finalizados->map(fn (Voluntariado $voluntariado) => (string) $voluntariado->_id)->all();
        $storedEventIds = $this->mongoIds($eventIds);
        $inscripciones = $storedEventIds
            ? Inscripcion::query()->whereIn('voluntariadoId', $storedEventIds)->get()
            : new Collection;
        $asistencias = $storedEventIds
            ? Asistencia::query()->whereIn('voluntariadoId', $storedEventIds)->get()
            : new Collection;
        $impactos = $storedEventIds
            ? ImpactoVoluntariado::query()->whereIn('voluntariadoId', $storedEventIds)->get()
            : new Collection;
        $placeIds = $finalizados->pluck('lugarId')->filter()->map(fn ($id) => (string) $id)->unique()->values()->all();
        $lugares = $placeIds
            ? Lugar::query()->whereIn('_id', $this->mongoIds($placeIds))->get()
            : new Collection;

        $data = $this->bitacora->aggregate(
            $finalizados->all(),
            $inscripciones->all(),
            $asistencias->all(),
            $impactos->all(),
            $lugares->all(),
            [
                'from' => $filters['desde'] ?? null,
                'to' => $filters['hasta'] ?? null,
                'category' => $filters['categoria'] ?? null,
                'placeId' => $filters['lugarId'] ?? null,
                'state' => $filters['estado'] ?? null,
            ],
        );

        return response()->json([
            'data' => $data,
            'message' => 'Bitácora de impacto consultada correctamente.',
        ]);
    }

    private function mongoIds(array $ids): array
    {
        $objectIdClass = 'MongoDB\\BSON\\ObjectId';

        return collect($ids)
            ->flatMap(fn (string $id) => preg_match('/^[a-f0-9]{24}$/i', $id)
                ? [$id, new $objectIdClass($id)]
                : [$id])
            ->values()
            ->all();
    }
}