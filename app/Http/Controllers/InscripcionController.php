<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
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
            'voluntariadoId' => 'required|string|max:100',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $usuario = $request->user();

        if (!$usuario || !in_array($usuario->rol, ['usuario', 'organizador'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Solo usuarios y organizadores pueden inscribirse.',
            ], 403);
        }

        if ($usuario->estado !== 'activo') {
            return response()->json([
                'success' => false,
                'message' => 'La cuenta no está activa.',
            ], 403);
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

        $cupo = (int) ($voluntariado->cupo ?? 0);
        $inscripcionesActivas = Inscripcion::where(
            'voluntariadoId',
            (string) $voluntariado->_id
        )->whereIn('estado', ['inscrita', 'activa'])->count();

        if ($cupo < 1 || $inscripcionesActivas >= $cupo) {
            return response()->json([
                'success' => false,
                'message' => 'Este voluntariado ya no tiene cupo disponible.',
            ], 409);
        }

        $inscripcionExistente = Inscripcion::where(
            'usuarioId',
            (string) $usuario->_id
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
            'usuarioId' => (string) $usuario->_id,
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
