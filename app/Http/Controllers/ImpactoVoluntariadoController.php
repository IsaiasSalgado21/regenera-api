<?php

namespace App\Http\Controllers;

use App\Models\ImpactoVoluntariado;
use App\Models\TipoImpactoVoluntariado;
use App\Models\Voluntariado;
use App\Services\AuditoriaService;
use App\Services\GridFsImageService;
use App\Services\ImpactoVoluntariadoService;
use App\Services\VoluntariadoHorario;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ImpactoVoluntariadoController extends Controller
{
    public function __construct(
        private readonly ImpactoVoluntariadoService $impactos,
        private readonly AuditoriaService $auditoria,
        private readonly VoluntariadoHorario $horario,
        private readonly GridFsImageService $gridFsImages,
    ) {}

    public function resumen()
    {
        $records = ImpactoVoluntariado::query()->get();
        $presented = $this->impactos->present($records, includeEvidence: false);

        return response()->json([
            'data' => $presented['totales'],
            'message' => 'Impacto acumulado consultado correctamente.',
        ]);
    }

    public function index(Request $request, string $voluntariadoId)
    {
        $voluntariado = Voluntariado::findOrFail($voluntariadoId);
        $usuario = $request->user();
        $isAdmin = $usuario?->rol === 'admin';
        $isOwner = $usuario
            && (string) $voluntariado->organizadorId === (string) $usuario->getAuthIdentifier();
        $tiposPersonalizados = $isAdmin || $isOwner
            ? TipoImpactoVoluntariado::query()
                ->where('organizadorId', (string) $voluntariado->organizadorId)
                ->orderBy('etiqueta')
                ->get(['_id', 'etiqueta', 'unidad'])
                ->map(fn ($tipo) => [
                    'id' => (string) $tipo->_id,
                    'etiqueta' => $tipo->etiqueta,
                    'unidad' => $tipo->unidad,
                ])
                ->values()
            : collect();

        if ($request->boolean('soloTipos')) {
            return response()->json([
                'data' => [
                    'voluntariadoId' => (string) $voluntariado->_id,
                    'tiposPersonalizados' => $tiposPersonalizados,
                ],
            ]);
        }

        $records = ImpactoVoluntariado::query()
            ->where('voluntariadoId', (string) $voluntariado->_id)
            ->get();
        $impactCategory = $voluntariado->categoria ?: $voluntariado->titulo;
        $presented = $this->impactos->present($records, $impactCategory);
        $catalog = $this->impactos->catalogForCategory($impactCategory);

        return response()->json([
            'data' => [
                'voluntariadoId' => (string) $voluntariado->_id,
                'catalogo' => collect($catalog)
                    ->map(fn (array $metric, string $type) => ['tipo' => $type, ...$metric])
                    ->values(),
                'tiposPersonalizados' => $tiposPersonalizados,
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

        $impactCategory = $voluntariado->categoria ?: $voluntariado->titulo;
        $catalog = $this->impactos->catalogForCategory($impactCategory);
        $rules = $this->impactos->validationRules($impactCategory);
        $rules['imagenes'] = ['sometimes', 'nullable', 'array', 'max:6'];
        $rules['imagenes.*'] = ['file', 'image', 'max:5120'];
        $rules['guardarTipoPersonalizado'] = ['sometimes', 'boolean'];
        $validated = $request->validate($rules);
        $metric = $catalog[$validated['tipo']];
        $etiqueta = $validated['tipo'] === 'otro'
            ? trim($validated['etiquetaPersonalizada'])
            : $metric['etiqueta'];
        $unidad = $validated['tipo'] === 'otro'
            ? trim($validated['unidadPersonalizada'])
            : $metric['unidad'];

        if ($validated['tipo'] === 'otro' && ($validated['guardarTipoPersonalizado'] ?? false)) {
            $organizadorId = (string) $voluntariado->organizadorId;
            $clave = mb_strtolower(Str::slug($etiqueta).'|'.Str::slug($unidad));
            TipoImpactoVoluntariado::updateOrCreate(
                ['organizadorId' => $organizadorId, 'clave' => $clave],
                ['etiqueta' => $etiqueta, 'unidad' => $unidad],
            );
        }
        $files = $request->file('imagenes', []);
        $files = is_array($files) ? array_values(array_filter($files)) : ($files ? [$files] : []);
        [$imagePaths] = $this->gridFsImages->applyCollectionChanges(
            [],
            $files,
            [],
            6,
            'impactos-voluntariados',
            'reporte de impacto',
        );

        try {
            $impact = ImpactoVoluntariado::create([
                'voluntariadoId' => (string) $voluntariado->_id,
                'tipo' => $validated['tipo'],
                'etiquetaPersonalizada' => $validated['tipo'] === 'otro' ? $etiqueta : null,
                'unidadPersonalizada' => $validated['tipo'] === 'otro' ? $unidad : null,
                'cantidad' => $validated['cantidad'],
                'unidad' => $unidad,
                'nota' => $validated['nota'] ?? null,
                'especies' => array_values(array_filter(array_map('trim', $validated['especies'] ?? []))),
                'imagenes' => $imagePaths,
                'registradoPor' => (string) $usuario->getAuthIdentifier(),
            ]);
        } catch (\Throwable $exception) {
            $this->gridFsImages->deleteStored($imagePaths);
            throw $exception;
        }

        $this->auditoria->registrar(
            $usuario,
            'registrar_impacto',
            'voluntariado',
            (string) $voluntariado->_id,
            $request,
            [
                'impactoId' => (string) $impact->_id,
                'tipo' => $impact->tipo,
                'etiqueta' => $etiqueta,
                'cantidad' => (float) $impact->cantidad,
                'unidad' => $unidad,
                'especies' => $impact->especies ?? [],
                'evidencias' => count($imagePaths),
            ],
        );

        return response()->json([
            'data' => [
                'id' => (string) $impact->_id,
                'tipo' => $impact->tipo,
                'etiqueta' => $etiqueta,
                'cantidad' => (float) $impact->cantidad,
                'unidad' => $unidad,
                'nota' => $impact->nota,
                'especies' => $impact->especies ?? [],
                'imagenes' => $this->gridFsImages->urls($impact->imagenes ?? []),
                'registradoEn' => $impact->created_at?->toISOString(),
            ],
            'message' => 'Impacto registrado correctamente.',
        ], 201);
    }
}
