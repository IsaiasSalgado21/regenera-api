<?php

namespace App\Http\Controllers;

use App\Models\Lugar;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LugarController extends Controller
{
    public function index()
    {
        $lugares = Lugar::orderBy('nombre')->get();

        return response()->json([
            'success' => true,
            'lugares' => $lugares,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:150',
            'descripcion' => 'nullable|string|max:2000',
            'municipio' => 'required|string|max:100',
            'direccion' => 'nullable|string|max:255',
            'latitud' => 'nullable|numeric|between:-90,90',
            'longitud' => 'nullable|numeric|between:-180,180',
            'tipo' => [
                'required',
                Rule::in([
                    'playa',
                    'parque',
                    'reserva',
                    'zona_urbana',
                    'otro',
                ]),
            ],
            'estado' => [
                'sometimes',
                Rule::in(['activo', 'inactivo']),
            ],
            'creadoPor' => 'nullable|string|max:100',
        ]);

        $validated['estado'] = $validated['estado'] ?? 'activo';

        $lugar = Lugar::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Lugar registrado correctamente.',
            'lugar' => $lugar,
        ], 201);
    }

    public function show(string $id)
    {
        $lugar = Lugar::find($id);

        if (!$lugar) {
            return response()->json([
                'success' => false,
                'message' => 'Lugar no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'lugar' => $lugar,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $lugar = Lugar::find($id);

        if (!$lugar) {
            return response()->json([
                'success' => false,
                'message' => 'Lugar no encontrado.',
            ], 404);
        }

        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:150',
            'descripcion' => 'sometimes|nullable|string|max:2000',
            'municipio' => 'sometimes|required|string|max:100',
            'direccion' => 'sometimes|nullable|string|max:255',
            'latitud' => 'sometimes|nullable|numeric|between:-90,90',
            'longitud' => 'sometimes|nullable|numeric|between:-180,180',
            'tipo' => [
                'sometimes',
                'required',
                Rule::in([
                    'playa',
                    'parque',
                    'reserva',
                    'zona_urbana',
                    'otro',
                ]),
            ],
            'estado' => [
                'sometimes',
                'required',
                Rule::in(['activo', 'inactivo']),
            ],
            'creadoPor' => 'sometimes|nullable|string|max:100',
        ]);

        $lugar->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Lugar actualizado correctamente.',
            'lugar' => $lugar->fresh(),
        ]);
    }

    public function destroy(string $id)
    {
        $lugar = Lugar::find($id);

        if (!$lugar) {
            return response()->json([
                'success' => false,
                'message' => 'Lugar no encontrado.',
            ], 404);
        }

        $lugar->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lugar eliminado correctamente.',
        ]);
    }
}