<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Inscripcion;
use App\Models\User;
use App\Models\Voluntariado;
use Illuminate\Http\Request;

class AsistenciaController extends Controller
{
    public function index(Request $request)
    {
        $query = Asistencia::query();

        if ($request->has('voluntariadoId')) {
            $query->where('voluntariadoId', $request->voluntariadoId);
        }

        if ($request->has('usuarioId')) {
            $query->where('usuarioId', $request->usuarioId);
        }

        $asistencias = $query->orderBy('fecha', 'desc')->get();

        return response()->json([
            'success' => true,
            'asistencias' => $asistencias,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'usuarioId' => 'required|string|max:100',
            'voluntariadoId' => 'required|string|max:100',
            'metodo' => 'required|string|in:QR,manual',
        ]);

        $usuario = User::find($validated['usuarioId']);
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario indicado no existe.',
            ], 404);
        }

        $voluntariado = Voluntariado::find($validated['voluntariadoId']);
        if (!$voluntariado) {
            return response()->json([
                'success' => false,
                'message' => 'El voluntariado indicado no existe.',
            ], 404);
        }

        // Regla de Negocio: Validar que el usuario esté inscrito previamente
        $inscripcion = Inscripcion::where('usuarioId', $validated['usuarioId'])
            ->where('voluntariadoId', $validated['voluntariadoId'])
            ->where('estado', '!=', 'cancelada')
            ->first();

        if (!$inscripcion) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario no cuenta con una inscripción activa para este voluntariado.',
            ], 422);
        }

        // Evitar duplicar asistencia
        $asistenciaPrevia = Asistencia::where('usuarioId', $validated['usuarioId'])
            ->where('voluntariadoId', $validated['voluntariadoId'])
            ->first();

        if ($asistenciaPrevia) {
            return response()->json([
                'success' => false,
                'message' => 'La asistencia de este usuario ya fue registrada previamente.',
            ], 409);
        }

        $asistencia = Asistencia::create([
            'usuarioId' => $validated['usuarioId'],
            'voluntariadoId' => $validated['voluntariadoId'],
            'fecha' => now(),
            'hora' => now()->format('H:i:s'),
            'metodo' => $validated['metodo'],
            'confirmada' => true,
        ]);

        // Marcar la inscripción como completada
        $inscripcion->update(['estado' => 'completada']);

        return response()->json([
            'success' => true,
            'message' => 'Asistencia registrada correctamente.',
            'asistencia' => $asistencia,
        ], 201);
    }

    public function show(string $id)
    {
        $asistencia = Asistencia::find($id);

        if (!$asistencia) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de asistencia no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'asistencia' => $asistencia,
        ]);
    }

    public function destroy(string $id)
    {
        $asistencia = Asistencia::find($id);

        if (!$asistencia) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de asistencia no encontrado.',
            ], 404);
        }

        $asistencia->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asistencia eliminada correctamente.',
        ]);
    }
}