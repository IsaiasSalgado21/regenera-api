<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        $query = Notificacion::query();

        if ($request->has('usuarioId')) {
            $query->where('usuarioId', $request->usuarioId);
        }

        $notificaciones = $query->orderBy('fechaCreacion', 'desc')->get();

        return response()->json([
            'success' => true,
            'notificaciones' => $notificaciones,
        ]);
    }

    public function noLeidas(string $usuarioId)
    {
        $notificaciones = Notificacion::where('usuarioId', $usuarioId)
            ->where('leida', false)
            ->orderBy('fechaCreacion', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'noLeidasCount' => $notificaciones->count(),
            'notificaciones' => $notificaciones,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'usuarioId' => 'required|string|max:100',
            'tipo' => 'required|string|max:100',
            'titulo' => 'required|string|max:200',
            'mensaje' => 'required|string|max:1000',
            'entidad' => 'nullable|array',
            'prioridad' => 'nullable|string|in:baja,normal,alta',
            'datos' => 'nullable|array',
        ]);

        $notificacion = Notificacion::create([
            'usuarioId' => $validated['usuarioId'],
            'tipo' => $validated['tipo'],
            'titulo' => $validated['titulo'],
            'mensaje' => $validated['mensaje'],
            'entidad' => $validated['entidad'] ?? null,
            'prioridad' => $validated['prioridad'] ?? 'normal',
            'leida' => false,
            'fechaCreacion' => now(),
            'fechaLectura' => null,
            'fechaExpiracion' => null,
            'datos' => $validated['datos'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notificación creada correctamente.',
            'notificacion' => $notificacion,
        ], 201);
    }

    public function marcarLeida(string $id)
    {
        $notificacion = Notificacion::find($id);

        if (!$notificacion) {
            return response()->json([
                'success' => false,
                'message' => 'Notificación no encontrada.',
            ], 404);
        }

        $notificacion->update([
            'leida' => true,
            'fechaLectura' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída.',
            'notificacion' => $notificacion->fresh(),
        ]);
    }

    public function marcarTodasLeidas(string $usuarioId)
    {
        Notificacion::where('usuarioId', $usuarioId)
            ->where('leida', false)
            ->update([
                'leida' => true,
                'fechaLectura' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Todas las notificaciones fueron marcadas como leídas.',
        ]);
    }

    public function destroy(string $id)
    {
        $notificacion = Notificacion::find($id);

        if (!$notificacion) {
            return response()->json([
                'success' => false,
                'message' => 'Notificación no encontrada.',
            ], 404);
        }

        $notificacion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notificación eliminada correctamente.',
        ]);
    }
}