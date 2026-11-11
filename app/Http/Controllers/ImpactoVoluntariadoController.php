<?php

namespace App\Http\Controllers;

use App\Models\ImpactoVoluntariado;
use App\Models\Voluntariado;
use App\Services\AuditoriaService;
use App\Services\ImpactoVoluntariadoService;
use App\Services\VoluntariadoHorario;
use Illuminate\Http\Request;

class ImpactoVoluntariadoController extends Controller
{
    public function __construct(
        private readonly ImpactoVoluntariadoService $impactos,
        private readonly AuditoriaService $auditoria,
        private readonly VoluntariadoHorario $horario,
    ) {}

    public function resumen()
    {
        $records = ImpactoVoluntariado::query()->get();
        $presented = $this->impactos->present($records);

        return response()->json([
            'data' => $presented['totales'],
            'message' => 'Impacto acumulado consultado correctamente.',
        ]);
    }

    public function index(string $voluntariadoId)
    {
        $voluntariado = Voluntariado::findOrFail($voluntariadoId);
        $records = ImpactoVoluntariado::query()
            ->where('voluntariadoId', (string) $voluntariado->_id)
            ->get();
        $presented = $this->impactos->present($records);

        return response()->json([
            'data' => [
                'voluntariadoId' => (string) $voluntariado->_id,
                'catalogo' => collect($this->impactos->catalog())
                    ->map(fn (array $metric, string $type) => ['tipo' => $type, ...$metric])
                    ->values(),
                ...$presented,
            ],
        ]);
    }

    public function store(Request $request, string $voluntariadoId)
    {
        $voluntariado = Voluntariado::findOrFail($voluntariadoId);
        $usuario = $request->user();
        abort_unless(
            $usuario->rol === 'admin'
                || (string) $voluntariado->organizadorId === (string) $usuario->getAuthIdentifier(),
            403,
            'No tienes permiso para registrar el impacto de este voluntariado.',
        );
        abort_unless(
            in_array($voluntariado->estado, ['publicado', 'finalizado'], true),
            409,
            'Solo se puede reportar impacto de una actividad publicada o finalizada.',
        );
        abort_unless(
            $this->horario->fin($voluntariado)?->isPast() ?? false,
            409,
            'El impacto se registra cuando la actividad haya concluido.',
        );

        $catalog = $this->impactos->catalog();
        $validated = $request->validate($this->impactos->validationRules());
        $metric = $catalog[$validated['tipo']];

        $impact = ImpactoVoluntariado::create([
            'voluntariadoId' => (string) $voluntariado->_id,
            'tipo' => $validated['tipo'],
            'cantidad' => $validated['cantidad'],
            'unidad' => $metric['unidad'],
            'nota' => $validated['nota'] ?? null,
            'registradoPor' => (string) $usuario->getAuthIdentifier(),
        ]);

        $this->auditoria->registrar(
            $usuario,
            'registrar_impacto',
            'voluntariado',
            (string) $voluntariado->_id,
            $request,
            [
                'impactoId' => (string) $impact->_id,
                'tipo' => $impact->tipo,
                'cantidad' => (float) $impact->cantidad,
                'unidad' => $impact->unidad,
            ],
        );

        return response()->json([
            'data' => [
                'id' => (string) $impact->_id,
                'tipo' => $impact->tipo,
                'etiqueta' => $metric['etiqueta'],
                'cantidad' => (float) $impact->cantidad,
                'unidad' => $impact->unidad,
                'nota' => $impact->nota,
                'registradoEn' => $impact->created_at?->toISOString(),
            ],
            'message' => 'Impacto registrado correctamente.',
        ], 201);
    }
}
