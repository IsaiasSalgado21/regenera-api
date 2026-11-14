<?php

namespace App\Http\Controllers;

use App\Models\Lugar;
use App\Models\Inscripcion;
use App\Models\ImpactoVoluntariado;
use App\Models\User;
use App\Models\Voluntariado;
use App\Services\GridFsImageService;
use App\Services\ImpactoVoluntariadoService;
use App\Services\VoluntariadoHorario;
use App\Services\VoluntariadoWorkflow;
use App\Services\VoluntariadoUbicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use MongoDB\BSON\ObjectId;

class VoluntariadoPageController extends Controller
{
    public function __construct(
        private readonly GridFsImageService $gridFsImages,
        private readonly VoluntariadoHorario $horario,
        private readonly VoluntariadoWorkflow $workflow,
        private readonly ImpactoVoluntariadoService $impactos,
        private readonly VoluntariadoUbicacion $ubicaciones,
    ) {}

    /**
     * Lista pública de voluntariados, con filtros del lado del servidor.
     */
    public function index(Request $request)
    {
        $query = Voluntariado::query()->where('estado', 'publicado');

        $lugares = Lugar::orderBy('nombre')->get();
        $ahora = $this->horario->ahora();
        $voluntariados = $query->orderBy('fechaInicio', 'asc')->get()
            ->filter(fn (Voluntariado $voluntariado) => ($inicio = $this->horario->inicio($voluntariado))
                && $inicio->greaterThan($ahora))
            ->values();
        $organizadorIds = $voluntariados->pluck('organizadorId')
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();
        $storedOrganizadorIds = $organizadorIds->flatMap(fn (string $id) => preg_match('/^[a-f0-9]{24}$/i', $id)
            ? [$id, new ObjectId($id)]
            : [$id])->all();
        $organizadores = User::query()
            ->whereIn('_id', $storedOrganizadorIds)
            ->get(['_id', 'nombre'])
            ->keyBy(fn (User $user) => (string) $user->_id);
        $voluntariados = $voluntariados->map(function ($v) use ($lugares, $organizadores) {
            $lugar = $lugares->firstWhere('_id', (string) $v->lugarId);

            return [
                'id' => (string) $v->_id,
                'titulo' => $v->titulo,
                'organizadorNombre' => $organizadores->get((string) $v->organizadorId)?->nombre,
                'descripcion' => Str::limit(strip_tags($v->descripcion), 160),
                'imagen' => $this->gridFsImages->url(
                    $this->gridFsImages->paths($v->imagenes, $v->imagen)[0] ?? null,
                ),
                'categoria' => $v->categoria ?? null,
                'lugar_id' => (string) $v->lugarId,
                'lugar' => $lugar?->nombre,
                'ubicacion' => $this->ubicaciones->resolver($v, $lugar),
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
    public function show(Request $request, string $id)
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
        $inicioFechaHora = $this->horario->inicio($voluntariado);
        $finFechaHora = $this->horario->fin($voluntariado);
        $isAdmin = $request->user()?->rol === 'admin';
        $isOwner = $request->user()
            && (string) $voluntariado->organizadorId === (string) $request->user()->getAuthIdentifier();
        $inscritosCount = Inscripcion::query()
            ->where('voluntariadoId', (string) $voluntariado->_id)
            ->whereIn('estado', ['inscrita', 'activa'])
            ->count();
        $impacto = $this->impactos->present(
            ImpactoVoluntariado::query()
                ->where('voluntariadoId', (string) $voluntariado->_id)
                ->get(),
            $voluntariado->categoria ?: $voluntariado->titulo,
        );

        return view('voluntariados.show', [
            'voluntariado' => $voluntariado,
            'organizadorNombre' => User::find((string) $voluntariado->organizadorId)?->nombre,
            'isOwner' => (bool) $isOwner,
            'isAdmin' => $isAdmin,
            'impactoCatalogo' => $this->impactos->catalogForCategory($voluntariado->categoria ?: $voluntariado->titulo),
            'impacto' => $impacto,
            'inscritosCount' => $inscritosCount,
            'lugar' => $lugar,
            'inicioFechaHora' => $inicioFechaHora,
            'finFechaHora' => $finFechaHora,
            'voluntariadoFinalizado' => $this->workflow->finalizado($voluntariado, $this->horario),
            'voluntariadoCancelado' => $voluntariado->estado === 'cancelado',
            'ubicacion' => $this->ubicaciones->resolver($voluntariado, $lugar),
        ]);
    }
}
