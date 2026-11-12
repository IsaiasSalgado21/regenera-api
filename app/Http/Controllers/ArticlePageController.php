<?php
namespace App\Http\Controllers;

use App\Models\Articulo;
use App\Models\User;
use App\Services\GridFsImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use MongoDB\BSON\ObjectId;

class ArticlePageController extends Controller
{
    public function __construct(private readonly GridFsImageService $gridFsImages) {}

    /** Listado público de artículos publicados, con filtro opcional por categoría. */
    public function index(Request $request)
    {
        $articulos = Articulo::query()
            ->where('estado', 'publicado')
            ->orderBy('fechaPublicacion', 'desc')
            ->get();
        $autorIds = $articulos->pluck('autorId')
            ->filter()
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->values();
        $storedAutorIds = $autorIds->flatMap(fn (string $id) => preg_match('/^[a-f0-9]{24}$/i', $id)
            ? [$id, new ObjectId($id)]
            : [$id])->all();
        $autores = User::query()
            ->whereIn('_id', $storedAutorIds)
            ->get(['_id', 'nombre'])
            ->keyBy(fn (User $user) => (string) $user->_id);
        $articulos = $articulos->map(function (Articulo $a) use ($autores) {
            $imagenes = is_array($a->imagenes) && $a->imagenes
                ? $a->imagenes
                : ($a->imagen ? [$a->imagen] : []);

            return [
                'id' => (string) $a->_id,
                'titulo' => $a->titulo,
                'resumen' => Str::limit(strip_tags($a->contenido), 150),
                'contenidoBusqueda' => strip_tags($a->contenido),
                'imagen' => $this->gridFsImages->url($a->imagen),
                'imagenes' => $this->gridFsImages->urls($imagenes),
                'categoria' => $a->categoria,
                'fecha' => $a->fechaPublicacion?->format('d/m/Y'),
                'fechaIso' => $a->fechaPublicacion?->toIso8601String(),
                'autorNombre' => $autores->get((string) $a->autorId)?->nombre ?: 'Redacción Regenera BCS',
            ];
        });

        return view('articulos', [
            'articulos' => $articulos,
            'categorias' => $articulos->pluck('categoria')->filter()->unique()->sort()->values(),
        ]);
    }

    /** Detalle de un artículo. */
    public function show(string $id)
    {
        $articulo = Articulo::find($id);

        if (!$articulo || $articulo->estado !== 'publicado') {
            abort(404);
        }

        $articuloImages = $this->imageUrls($articulo);
        $articulo->setAttribute('imagenes', $articuloImages);
        $articulo->imagen = $articuloImages[0] ?? null;
        $autor = preg_match('/^[a-f0-9]{24}$/i', (string) $articulo->autorId)
            ? User::find((string) $articulo->autorId)
            : null;
        $parrafos = collect(preg_split('/\R\s*\R/u', trim((string) $articulo->contenido)) ?: [])
            ->map(fn (string $parrafo) => trim($parrafo))
            ->filter()
            ->values();
        $palabras = str_word_count(strip_tags((string) $articulo->contenido));

        $otros = Articulo::where('estado', 'publicado')
            ->where('_id', '!=', $articulo->_id)
            ->orderBy('fechaPublicacion', 'desc')
            ->limit(3)
            ->get();
        $otros->each(function (Articulo $otro): void {
            $otro->imagen = $this->imageUrls($otro)[0] ?? null;
        });

        return view('articulos.show', [
            'articulo' => $articulo,
            'otros' => $otros,
            'autorNombre' => $autor?->nombre ?: 'Redacción Regenera BCS',
            'parrafos' => $parrafos,
            'minutosLectura' => max(1, (int) ceil($palabras / 200)),
        ]);
    }

    private function imageUrls(Articulo $articulo): array
    {
        $images = is_array($articulo->imagenes) && $articulo->imagenes
            ? $articulo->imagenes
            : ($articulo->imagen ? [$articulo->imagen] : []);

        return $this->gridFsImages->urls($images);
    }

    public function create()
    {
        return view('articulos.crear');
    }
}
