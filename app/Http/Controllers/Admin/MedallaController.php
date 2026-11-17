<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cuestionario;
use App\Models\EventoGamificacion;
use App\Models\Medalla;
use App\Services\CatalogoMedallas;
use Illuminate\Http\Request;

class MedallaController extends Controller
{
    public function __construct(private readonly CatalogoMedallas $catalogo) {}

    public function index()
    {
        return response()->json(['data' => $this->catalogo->todas()->sortBy('nombre')->values()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateMedalla($request, true);
        abort_if($this->catalogo->todas()->has($data['slug']), 409, 'Ya existe una medalla con ese identificador.');
        $medalla = Medalla::create([...$data, 'activo' => true]);

        return response()->json(['data' => $medalla, 'message' => 'Medalla creada correctamente.'], 201);
    }

    public function update(Request $request, string $slug)
    {
        abort_unless($this->catalogo->todas()->has($slug), 404);
        $data = $this->validateMedalla($request);
        $medalla = Medalla::updateOrCreate(['slug' => $slug], [...$data, 'slug' => $slug]);

        return response()->json(['data' => $medalla, 'message' => 'Medalla actualizada correctamente.']);
    }

    public function destroy(string $slug)
    {
        $medalla = $this->catalogo->todas()->get($slug);
        abort_unless($medalla, 404);
        $vinculada = Cuestionario::query()->get(['medalla'])
            ->contains(fn (Cuestionario $quiz) => ($quiz->medalla['slug'] ?? null) === $slug);
        $obtenida = EventoGamificacion::query()->where('tipo', 'medalla')->where('referenciaId', $slug)->exists();
        if ($vinculada || $obtenida) {
            Medalla::updateOrCreate(['slug' => $slug], [
                'slug' => $slug,
                'nombre' => $medalla['nombre'],
                'descripcion' => $medalla['descripcion'],
                'icono' => $medalla['icono'],
                'activo' => false,
            ]);

            return response()->json(['message' => 'Medalla desactivada; sus relaciones y logros se conservaron.']);
        }

        Medalla::query()->where('slug', $slug)->delete();

        return response()->json(['message' => 'Medalla eliminada correctamente.']);
    }

    private function validateMedalla(Request $request, bool $creating = false): array
    {
        $rules = [
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['required', 'string', 'max:300'],
            'icono' => ['required', 'string', 'max:40', 'alpha_dash'],
        ];
        if ($creating) {
            $rules['slug'] = ['required', 'string', 'max:80', 'alpha_dash'];
        } else {
            $rules['activo'] = ['required', 'boolean'];
        }

        return $request->validate($rules);
    }
}
