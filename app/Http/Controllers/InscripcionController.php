<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\User;
use App\Models\Voluntariado;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InscripcionController extends Controller
{
    public function index()
    {
        $inscripciones = Inscripcion::orderBy(
            'fechaInscripcion',
            'desc'
        )->get();

        return response()->json([
            'success' => true,
            'inscripciones' => $inscripciones,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'usuarioId' => 'required|string|max:100',
            'voluntariadoId' => 'required|string|max:100',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $usuario = User::find($validated['usuarioId']);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario indicado no existe.',
            ], 404);
        }

        $voluntariado = Voluntariado::find(
            $validated['voluntariadoId']
        );

        if (!$voluntariado) {
            return response()->json([
                'success' => false,
                'message' => 'El voluntariado indicado no existe.',
            ], 404);
        }

        if (($voluntariado->estado ?? null) !== 'publicado') {
            return response()->json([
                'success' => false,
                'message' => 'Solo puedes inscribirte en voluntariados publicados.',
            ], 422);
        }

        $inscripcionExistente = Inscripcion::where(
            'usuarioId',
            $validated['usuarioId']
        )->where(
            'voluntariadoId',
            $validated['voluntariadoId']
        )->where(
            'estado',
            '!=',
            'cancelada'
        )->first();

        if ($inscripcionExistente) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario ya tiene una inscripción activa para este voluntariado.',
            ], 409);
        }

        $inscripcion = Inscripcion::create([
            'usuarioId' => $validated['usuarioId'],
            'voluntariadoId' => $validated['voluntariadoId'],
            'fechaInscripcion' => now(),
            'estado' => 'inscrita',
            'observaciones' => $validated['observaciones'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Inscripción registrada correctamente.',
            'inscripcion' => $inscripcion,
        ], 201);
    }

    public function show(string $id)
    {
        $inscripcion = Inscripcion::find($id);

        if (!$inscripcion) {
            return response()->json([
                'success' => false,
                'message' => 'Inscripción no encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'inscripcion' => $inscripcion,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $inscripcion = Inscripcion::find($id);

        if (!$inscripcion) {
            return response()->json([
                'success' => false,
                'message' => 'Inscripción no encontrada.',
            ], 404);
        }

        $validated = $request->validate([
            'estado' => [
                'sometimes',
                'required',
                Rule::in([
                    'inscrita',
                    'cancelada',
                    'completada',
                ]),
            ],
            'observaciones' => 'sometimes|nullable|string|max:1000',
        ]);

        $inscripcion->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inscripción actualizada correctamente.',
            'inscripcion' => $inscripcion->fresh(),
        ]);
    }

    public function destroy(string $id)
    {
        $inscripcion = Inscripcion::find($id);

        if (!$inscripcion) {
            return response()->json([
                'success' => false,
                'message' => 'Inscripción no encontrada.',
            ], 404);
        }

        $inscripcion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inscripción eliminada correctamente.',
        ]);
    }
}
