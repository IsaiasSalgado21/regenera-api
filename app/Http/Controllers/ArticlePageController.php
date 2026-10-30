<?php

namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Services\GridFsImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ArticlePageController extends Controller
{
    public function __construct(private readonly GridFsImageService $gridFsImages) {}

    /**
     * Listado público de artículos publicados, con filtro opcional por categoría.
     */
    public function index(Request $request)
    {
        $query = Articulo::query()->where('estado', 'publicado');

        if ($request->filled('q')) {
            $termino = $request->q;
            $query->where(function ($sub) use ($termino) {
                $sub->where('titulo', 'like', '%' . $termino . '%')
                    ->orWhere('contenido', 'like', '%' . $termino . '%');
            });
        }

        $articulos = $query->orderBy('fechaPublicacion', 'desc')->get()->map(function ($a) {
            return [
                'id' => (string) $a->_id,
                'titulo' => $a->titulo,
                'resumen' => Str::limit(strip_tags($a->contenido), 150),
                'imagen' => $this->gridFsImages->url($a->imagen),
                'categoria' => $a->categoria,
                'fecha' => $a->fechaPublicacion?->format('d/m/Y'),
            ];
        });

        return view('articulos', [
            'articulos' => $articulos,
            'q' => $request->q,
        ]);
    }

    /**
     * Detalle de un artículo.
     */
    public function show(string $id)
    {
        $articulo = Articulo::find($id);

        if (!$articulo || $articulo->estado !== 'publicado') {
            abort(404);
        }

        $articulo->imagen = $this->gridFsImages->url($articulo->imagen);

        $otros = Articulo::where('estado', 'publicado')
            ->where('_id', '!=', $articulo->_id)
            ->orderBy('fechaPublicacion', 'desc')
            ->limit(3)
            ->get();
        $otros->each(fn (Articulo $otro) => $otro->imagen = $this->gridFsImages->url($otro->imagen));

        return view('articulos.show', [
            'articulo' => $articulo,
            'otros' => $otros,
        ]);
    }
}