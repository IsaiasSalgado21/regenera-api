<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;
use MongoDB\BSON\ObjectId;

class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        $notificaciones = $this->queryForUser((string) $request->user()->getAuthIdentifier())
            ->orderBy('fechaCreacion', 'desc')
            ->get();

        return response()->json([
            'data' => $notificaciones,
            'message' => 'Notificaciones consultadas correctamente.',
        ]);
    }

    public function noLeidas(Request $request)
    {
        $notificaciones = $this->queryForUser((string) $request->user()->getAuthIdentifier())
            ->where('leida', false)
            ->orderBy('fechaCreacion', 'desc')
            ->get();

        return response()->json([
            'data' => $notificaciones,
            'count' => $notificaciones->count(),
            'message' => 'Notificaciones no leídas consultadas correctamente.',
        ]);
    }

    public function marcarLeida(Request $request, string $id)
    {
        $notificacion = Notificacion::findOrFail($id);
        abort_unless($this->perteneceAlUsuario($notificacion, (string) $request->user()->getAuthIdentifier()), 403);
        $notificacion->update(['leida' => true, 'fechaLectura' => now()]);

        return response()->json([
            'data' => $notificacion->fresh(),
            'message' => 'Notificación marcada como leída.',
        ]);
    }

    public function marcarTodasLeidas(Request $request)
    {
        $this->queryForUser((string) $request->user()->getAuthIdentifier())
            ->where('leida', false)
            ->update(['leida' => true, 'fechaLectura' => now()]);

        return response()->json([
            'data' => [],
            'message' => 'Todas las notificaciones fueron marcadas como leídas.',
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $notificacion = Notificacion::findOrFail($id);
        abort_unless($this->perteneceAlUsuario($notificacion, (string) $request->user()->getAuthIdentifier()), 403);
        $notificacion->delete();

        return response()->json([
            'data' => ['id' => $id],
            'message' => 'Notificación eliminada correctamente.',
        ]);
    }

    private function queryForUser(string $usuarioId)
    {
        return Notificacion::query()->where(function ($query) use ($usuarioId): void {
            $query->where('usuarioId', $usuarioId);

            if (preg_match('/^[a-f0-9]{24}$/i', $usuarioId)) {
                $query->orWhere('usuarioId', new ObjectId($usuarioId));
            }
        });
    }

    private function perteneceAlUsuario(Notificacion $notificacion, string $usuarioId): bool
    {
        return (string) $notificacion->usuarioId === $usuarioId;
    }
}
