<?php

namespace App\Http\Controllers;

use App\Models\Lugar;
use App\Models\Voluntariado;
use App\Services\BajaCaliforniaSurBoundary;
use App\Services\GridFsImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VoluntariadoPageController extends Controller
{
    public function __construct(
        private readonly GridFsImageService $gridFsImages,
        private readonly BajaCaliforniaSurBoundary $boundary,
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
                'fecha_fin' => $v->fechaFin?->format('d/m/Y'),
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

        return view('voluntariados.show', [
            'voluntariado' => $voluntariado,
            'lugar' => $lugar,
            'ubicacion' => $hasValidLocation ? [
                'latitud' => (float) $latitude,
                'longitud' => (float) $longitude,
            ] : null,
        ]);
    }
}