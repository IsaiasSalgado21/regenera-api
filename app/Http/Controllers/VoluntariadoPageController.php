<?php

namespace App\Http\Controllers;

use App\Models\Lugar;
use App\Models\Voluntariado;
use App\Services\GridFsImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VoluntariadoPageController extends Controller
{
    public function __construct(private readonly GridFsImageService $gridFsImages) {}

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
                $sub->where('titulo', 'like', '%' . $termino . '%')
                    ->orWhere('descripcion', 'like', '%' . $termino . '%');
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
                'hora_inicio' => $v->horaInicio,
                'hora_fin' => $v->horaFin,
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
     * Detalle de un voluntariado.
     */
    public function show(string $id)
    {
        $voluntariado = Voluntariado::find($id);

        if (!$voluntariado || $voluntariado->estado !== 'publicado') {
            abort(404);
        }

        $lugar = $voluntariado->lugarId ? Lugar::find($voluntariado->lugarId) : null;
        $voluntariado->imagen = $this->gridFsImages->url($voluntariado->imagen);

        return view('voluntariados.show', [
            'voluntariado' => $voluntariado,
            'lugar' => $lugar,
        ]);
    }
}