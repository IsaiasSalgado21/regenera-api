<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\FloraFauna;
use App\Models\Voluntariado;
use App\Services\GridFsImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __construct(private readonly GridFsImageService $gridFsImages) {}

    /**
     * Página principal de Regenera BCS.
     *
     * Alimenta las secciones del home con datos reales de MongoDB:
     *  - voluntariados (top 3)
     *  - flora y fauna (para el carrusel del hero)
     *  - artículos publicados (top 3)
     */
    public function index(Request $request)
    {
        // Voluntariados: solo los publicados, del más reciente al más antiguo.
        $voluntariados = Voluntariado::query()
            ->where('estado', 'publicado')
            ->orderBy('fechaInicio', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($voluntariado) {
                $lugar = null;
                if ($voluntariado->lugarId) {
                    $lugar = \App\Models\Lugar::find($voluntariado->lugarId);
                }

                return [
                    'id'         => (string) $voluntariado->_id,
                    'titulo'     => $voluntariado->titulo,
                    'descripcion'=> $voluntariado->descripcion,
                    'imagen'     => $this->gridFsImages->url($voluntariado->imagen),
                    'categoria'  => $voluntariado->estado,
                    'lugar'      => $lugar?->nombre,
                    'fecha'      => $voluntariado->fechaInicio?->format('d/m/Y'),
                    'cupo'       => $voluntariado->cupo,
                ];
            });

        // Flora y fauna: alimenta el carrusel del hero.
        $especies = FloraFauna::query()
            ->where('estado', 'publicado')
            ->limit(4)
            ->get()
            ->map(function ($especie) {
                $imagen = $this->gridFsImages->url(collect($especie->imagenes ?? [])->first());

                return [
                    'id'        => (string) $especie->_id,
                    'nombre'    => $especie->nombre,
                    'cientifico'=> $especie->nombreCientifico,
                    'tipo'      => $especie->tipo,
                    'descripcion' => $especie->descripcion,
                    'imagen'    => $imagen,
                ];
            });

        // Artículos publicados: los 3 más recientes.
        $articulos = Articulo::query()
            ->where('estado', 'publicado')
            ->orderBy('fechaPublicacion', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($articulo) {
                return [
                    'id'        => (string) $articulo->_id,
                    'titulo'    => $articulo->titulo,
                    'contenido' => \Illuminate\Support\Str::limit(strip_tags($articulo->contenido), 120),
                    'imagen'    => $this->gridFsImages->url($articulo->imagen),
                    'categoria' => $articulo->categoria,
                    'fecha'     => $articulo->fechaPublicacion?->format('d/m/Y'),
                ];
            });

        return view('index', [
            'voluntariados' => $voluntariados,
            'especies'      => $especies,
            'articulos'     => $articulos,
            'usuario'       => Auth::user(),
        ]);
    }
}
