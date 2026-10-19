<?php

namespace App\Http\Controllers;

use App\Models\FloraFauna;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FloraFaunaController extends Controller
{
    public function index(Request $request)
    {
        $query = FloraFauna::query();

        if ($request->has('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        $floraFauna = $query->orderBy('nombre', 'asc')->get();

        return response()->json([
            'success' => true,
            'data' => $floraFauna,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'nombreCientifico' => 'nullable|string|max:150',
            'tipo' => ['required', Rule::in(['flora', 'fauna'])],
            'descripcion' => 'required|string|max:2000',
            'habitat' => 'nullable|string|max:500',
            'estadoConservacion' => 'nullable|string|max:100',
            'imagenes' => 'nullable|array',
            'ubicacion' => 'nullable|array',
            'lugaresRelacionados' => 'nullable|array',
            'fuenteInformacion' => 'nullable|string|max:255',
        ]);

        $registro = FloraFauna::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Registro de flora o fauna creado correctamente.',
            'data' => $registro,
        ], 201);
    }

    public function show(string $id)
    {
        $registro = FloraFauna::find($id);

        if (!$registro) {
            return response()->json([
                'success' => false,
                'message' => 'Registro no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $registro,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $registro = FloraFauna::find($id);

        if (!$registro) {
            return response()->json([
                'success' => false,
                'message' => 'Registro no encontrado.',
            ], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:150',
            'nombreCientifico' => 'sometimes|nullable|string|max:150',
            'tipo' => ['sometimes', 'required', Rule::in(['flora', 'fauna'])],
            'descripcion' => 'sometimes|required|string|max:2000',
            'habitat' => 'sometimes|nullable|string|max:500',
            'estadoConservacion' => 'sometimes|nullable|string|max:100',
            'imagenes' => 'sometimes|nullable|array',
            'ubicacion' => 'sometimes|nullable|array',
            'lugaresRelacionados' => 'sometimes|nullable|array',
            'fuenteInformacion' => 'sometimes|nullable|string|max:255',
        ]);

        $registro->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Registro actualizado correctamente.',
            'data' => $registro->fresh(),
        ]);
    }

    public function destroy(string $id)
    {
        $registro = FloraFauna::find($id);

        if (!$registro) {
            return response()->json([
                'success' => false,
                'message' => 'Registro no encontrado.',
            ], 404);
        }

        $registro->delete();

        return response()->json([
            'success' => true,
            'message' => 'Registro eliminado correctamente.',
        ]);
    }
}