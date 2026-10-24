<?php

namespace App\Http\Controllers;

use App\Models\FloraFauna;
use Illuminate\Http\Request;

class FloraPageController extends Controller
{
    /**
     * Listado público de flora y fauna con filtros del lado del servidor.
     */
    public function index(Request $request)
    {
        $query = FloraFauna::query();

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
                'imagen' => is_array($e->imagenes) && count($e->imagenes) ? $e->imagenes[0] : null,
                'ubicacion' => $e->ubicacion,
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

        if (!$especie) {
            abort(404);
        }

        return view('flora.show', [
            'especie' => $especie,
        ]);
    }
}