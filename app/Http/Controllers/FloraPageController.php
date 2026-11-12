<?php

namespace App\Http\Controllers;

use App\Models\FloraFauna;
use App\Models\Lugar;
use App\Services\BajaCaliforniaSurBoundary;
use App\Services\FloraFaunaService;
use Illuminate\Http\Request;
use MongoDB\BSON\ObjectId;

class FloraPageController extends Controller
{
    public function __construct(
        private readonly FloraFaunaService $floraFaunaService,
        private readonly BajaCaliforniaSurBoundary $boundary,
    ) {}

    /**
     * Listado público de flora y fauna con filtros del lado del servidor.
     */
    public function index(Request $request)
    {
        $query = FloraFauna::query()->where('estado', 'publicado');

        // Filtro por tipo (flora / fauna)
        if ($request->filled('tipo') && in_array($request->tipo, ['flora', 'fauna'])) {
            $query->where('tipo', $request->tipo);
        }

        // Búsqueda por nombre común, científico o hábitat
        if ($request->filled('q')) {
            $termino = $request->q;
            $query->where(function ($sub) use ($termino) {
                $sub->where('nombre', 'like', '%' . $termino . '%')
                    ->orWhere('nombreCientifico', 'like', '%' . $termino . '%')
                    ->orWhere('habitat', 'like', '%' . $termino . '%')
                    ->orWhere('descripcion', 'like', '%' . $termino . '%');
            });
        }

        $especies = $query->orderBy('nombre', 'asc')->get()->map(function ($e) {
            return [
                'id' => (string) $e->_id,
                'nombre' => $e->nombre,
                'cientifico' => $e->nombreCientifico,
                'tipo' => $e->tipo,
                'descripcion' => $e->descripcion,
                'habitat' => $e->habitat,
                'conservacion' => $e->estadoConservacion,
                'imagen' => $this->floraFaunaService->imagenes($e)[0] ?? null,
                'ubicacion' => $this->floraFaunaService->ubicacionVisible($e),
                'fuente' => $e->fuenteInformacion,
            ];
        });

        return view('flora', [
            'especies' => $especies,
            'tipo' => $request->tipo,
            'q' => $request->q,
        ]);
    }

    /**
     * Detalle de una especie.
     */
    public function show(string $id)
    {
        $especie = FloraFauna::find($id);

        if (!$especie || $especie->estado !== 'publicado') {
            abort(404);
        }

        $zonaAproximada = $this->floraFaunaService->present($especie)['zonaAproximada'];
        $especie->setAttribute('ubicacion', $this->floraFaunaService->ubicacionVisible($especie));
        $especie->setAttribute('imagenes', $this->floraFaunaService->imagenes($especie));
        $ubicacion = $especie->ubicacion;

        $relatedIds = collect($especie->lugaresRelacionados ?? [])
            ->map(function ($id) {
                $id = (string) $id;

                return preg_match('/^[a-f0-9]{24}$/i', $id) ? new ObjectId($id) : $id;
            })
            ->values()
            ->all();

        $lugaresRelacionados = empty($relatedIds)
            ? collect()
            : Lugar::query()
                ->where('estado', 'activo')
                ->whereIn('_id', $relatedIds)
                ->get(['nombre', 'latitud', 'longitud', 'municipio']);

        $mapLocations = [];
        if (
            is_array($ubicacion)
            && isset($ubicacion['latitud'], $ubicacion['longitud'])
            && $this->boundary->contains($ubicacion['latitud'], $ubicacion['longitud'])
        ) {
            $mapLocations[] = [
                'nombre' => $ubicacion['nombre'] ?? $especie->nombre,
                'tipo' => 'Distribución registrada',
                'latitud' => (float) $ubicacion['latitud'],
                'longitud' => (float) $ubicacion['longitud'],
                'parametro' => 'especie',
                'id' => (string) $especie->_id,
                'aproximada' => $zonaAproximada,
            ];
        }

        foreach ($lugaresRelacionados as $lugar) {
            if ($this->boundary->contains($lugar->latitud, $lugar->longitud)) {
                $mapLocations[] = [
                    'nombre' => $lugar->nombre,
                    'tipo' => 'Lugar relacionado',
                    'latitud' => (float) $lugar->latitud,
                    'longitud' => (float) $lugar->longitud,
                    'parametro' => 'lugar',
                    'id' => (string) $lugar->_id,
                    'aproximada' => false,
                ];
            }
        }

        $locationName = is_array($ubicacion) && is_string($ubicacion['nombre'] ?? null)
            ? mb_strtolower($ubicacion['nombre'])
            : '';
        $mapMode = count($mapLocations) > 0
            ? 'locations'
            : (str_contains($locationName, 'baja california sur') ? 'regional' : 'none');

        return view('flora.show', [
            'especie' => $especie,
            'lugaresRelacionados' => $lugaresRelacionados,
            'mapLocations' => $mapLocations,
            'mapMode' => $mapMode,
        ]);
    }
}
