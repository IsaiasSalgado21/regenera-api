<?php

namespace App\Http\Controllers;

use App\Models\Lugar;
use App\Models\Inscripcion;
use App\Models\ImpactoVoluntariado;
use App\Models\Voluntariado;
use App\Services\BajaCaliforniaSurBoundary;
use App\Services\GridFsImageService;
use App\Services\ImpactoVoluntariadoService;
use App\Services\VoluntariadoHorario;
use App\Services\VoluntariadoWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VoluntariadoPageController extends Controller
{
    public function __construct(
        private readonly GridFsImageService $gridFsImages,
        private readonly BajaCaliforniaSurBoundary $boundary,
        private readonly VoluntariadoHorario $horario,
        private readonly VoluntariadoWorkflow $workflow,
        private readonly ImpactoVoluntariadoService $impactos,
    ) {}

    /**
     * Lista pública de voluntariados, con filtros del lado del servidor.
     */
    public function index(Request $request)
    {
        $query = Voluntariado::query()->where('estado', 'publicado');

        // Filtro por categoría
        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        // Búsqueda por texto libre
        if ($request->filled('q')) {
            $termino = $request->q;
            $query->where(function ($sub) use ($termino) {
                $sub->where('titulo', 'like', '%'.$termino.'%')
                    ->orWhere('descripcion', 'like', '%'.$termino.'%');
            });
        }

        // Filtro por lugar
        $lugares = Lugar::orderBy('nombre')->get();

        if ($request->filled('lugarId')) {
            $query->where('lugarId', $request->lugarId);
        }

        $voluntariados = $query->orderBy('fechaInicio', 'asc')->get()->map(function ($v) use ($lugares) {
            $lugar = $lugares->firstWhere('_id', (string) $v->lugarId);

            return [
                'id' => (string) $v->_id,
                'titulo' => $v->titulo,
                'descripcion' => Str::limit(strip_tags($v->descripcion), 160),
                'imagen' => $this->gridFsImages->url($v->imagen),
                'categoria' => $v->categoria ?? null,
                'lugar' => $lugar?->nombre,
                'fecha' => $v->fechaInicio?->format('d/m/Y'),
                'fecha_iso' => $v->fechaInicio?->format('Y-m-d'),
                'fecha_fin' => $v->fechaFin?->format('d/m/Y'),
                'hora_inicio' => $v->horaInicio,
                'hora_fin' => $v->horaFin,
                'inicio_iso' => $this->horario->inicio($v)?->toIso8601String(),
                'fin_iso' => $this->horario->fin($v)?->toIso8601String(),
                'cupo' => $v->cupo,
                'requisitos' => $v->requisitos,
                'materiales' => $v->materiales,
            ];
        });

        return view('voluntariados', [
            'voluntariados' => $voluntariados,
            'lugares' => $lugares,
            'categoria' => $request->categoria,
            'q' => $request->q,
            'lugarId' => $request->lugarId,
        ]);
    }

    /**
     * Formulario para crear un voluntariado.
     */
    public function create()
    {
        return view('voluntariados.crear');
    }

    /**
     * Detalle de un voluntariado.
     */
    public function show(string $id)
    {
        $voluntariado = Voluntariado::find($id);

        if (! $voluntariado || ! in_array($voluntariado->estado, ['publicado', 'cancelado', 'finalizado'], true)) {
            abort(404);
        }

        $lugar = $voluntariado->lugarId ? Lugar::find($voluntariado->lugarId) : null;
        $images = is_array($voluntariado->imagenes) && $voluntariado->imagenes
            ? $voluntariado->imagenes
            : ($voluntariado->imagen ? [$voluntariado->imagen] : []);
        $voluntariado->setAttribute('imagenes', $this->gridFsImages->urls($images));
        $voluntariado->imagen = $voluntariado->imagenes[0] ?? null;
        $latitude = $lugar?->latitud;
        $longitude = $lugar?->longitud;
        if (! $this->boundary->contains($latitude, $longitude)) {
            $latitude = $voluntariado->latitud;
            $longitude = $voluntariado->longitud;
        }
        $hasValidLocation = $this->boundary->contains($latitude, $longitude);
        $inicioFechaHora = $this->horario->inicio($voluntariado);
        $finFechaHora = $this->horario->fin($voluntariado);
        $inscritosCount = Inscripcion::query()
            ->where('voluntariadoId', (string) $voluntariado->_id)
            ->whereIn('estado', ['inscrita', 'activa'])
            ->count();
        $impacto = $this->impactos->present(
            ImpactoVoluntariado::query()
                ->where('voluntariadoId', (string) $voluntariado->_id)
                ->get(),
        );

        return view('voluntariados.show', [
            'voluntariado' => $voluntariado,
            'impacto' => $impacto,
            'inscritosCount' => $inscritosCount,
            'lugar' => $lugar,
            'inicioFechaHora' => $inicioFechaHora,
            'finFechaHora' => $finFechaHora,
            'voluntariadoFinalizado' => $this->workflow->finalizado($voluntariado, $this->horario),
            'voluntariadoCancelado' => $voluntariado->estado === 'cancelado',
            'ubicacion' => $hasValidLocation ? [
                'latitud' => (float) $latitude,
                'longitud' => (float) $longitude,
            ] : null,
        ]);
    }
}
