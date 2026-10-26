<?php

namespace App\Http\Controllers;

use App\Models\Voluntariado;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VoluntariadoController extends Controller
{
    public function index()
    {
        $voluntariados = Voluntariado::orderBy('fechaInicio')->get();

        return response()->json([
            'success' => true,
            'voluntariados' => $voluntariados,
        ]);
    }

    public function store(Request $request)
    {
        // MVP: organizadorVerificado=false no debe bloquear a un organizador al crear un voluntariado.
        $validated = $request->validate([
            'titulo' => 'required|string|max:150',
            'descripcion' => 'required|string|max:3000',
            'lugarId' => 'required|string|max:100',
            'organizadorId' => 'required|string|max:100',
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
            'horaInicio' => [
                'required',
                'date_format:H:i',
            ],
            'horaFin' => [
                'required',
                'date_format:H:i',
                'after:horaInicio',
            ],
            'cupo' => 'required|integer|min:1|max:10000',
            'requisitos' => 'nullable|array',
            'requisitos.*' => 'string|max:255',
            'materiales' => 'nullable|array',
            'materiales.*' => 'string|max:255',
            'imagen' => 'nullable|string|max:2048',
            'estado' => [
                'sometimes',
                Rule::in([
                    'pendiente',
                    'publicado',
                    'cancelado',
                    'finalizado',
                ]),
            ],
        ]);

        $validated['estado'] = $validated['estado'] ?? 'pendiente';
        $validated['requisitos'] = $validated['requisitos'] ?? [];
        $validated['materiales'] = $validated['materiales'] ?? [];

        $voluntariado = Voluntariado::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Voluntariado registrado correctamente.',
            'voluntariado' => $voluntariado,
        ], 201);
    }

    public function show(string $id)
    {
        $voluntariado = Voluntariado::find($id);

        if (!$voluntariado) {
            return response()->json([
                'success' => false,
                'message' => 'Voluntariado no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'voluntariado' => $voluntariado,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $voluntariado = Voluntariado::find($id);

        if (!$voluntariado) {
            return response()->json([
                'success' => false,
                'message' => 'Voluntariado no encontrado.',
            ], 404);
        }

        $validated = $request->validate([
            'titulo' => 'sometimes|required|string|max:150',
            'descripcion' => 'sometimes|required|string|max:3000',
            'lugarId' => 'sometimes|required|string|max:100',
            'organizadorId' => 'sometimes|required|string|max:100',
            'fechaInicio' => 'sometimes|required|date',
            'fechaFin' => 'sometimes|required|date|after_or_equal:fechaInicio',
            'horaInicio' => 'sometimes|required|date_format:H:i',
            'horaFin' => 'sometimes|required|date_format:H:i',
            'cupo' => 'sometimes|required|integer|min:1|max:10000',
            'requisitos' => 'sometimes|nullable|array',
            'requisitos.*' => 'string|max:255',
            'materiales' => 'sometimes|nullable|array',
            'materiales.*' => 'string|max:255',
            'imagen' => 'sometimes|nullable|string|max:2048',
            'estado' => [
                'sometimes',
                'required',
                Rule::in([
                    'pendiente',
                    'publicado',
                    'cancelado',
                    'finalizado',
                ]),
            ],
        ]);

        $voluntariado->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Voluntariado actualizado correctamente.',
            'voluntariado' => $voluntariado->fresh(),
        ]);
    }

    public function destroy(string $id)
    {
        $voluntariado = Voluntariado::find($id);

        if (!$voluntariado) {
            return response()->json([
                'success' => false,
                'message' => 'Voluntariado no encontrado.',
            ], 404);
        }

        $voluntariado->delete();

        return response()->json([
            'success' => true,
            'message' => 'Voluntariado eliminado correctamente.',
        ]);
    }
}
