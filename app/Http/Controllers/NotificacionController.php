<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;
use MongoDB\BSON\ObjectId;

class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        $notificaciones = $this->queryVigentes((string) $request->user()->getAuthIdentifier())
            ->orderBy('fechaCreacion', 'desc')
            ->get();

        return response()->json([
            'data' => $notificaciones->map(fn (Notificacion $notificacion) => $this->present($notificacion))->values(),
            'message' => 'Notificaciones consultadas correctamente.',
        ]);
    }

    public function noLeidas(Request $request)
    {
        $notificaciones = $this->queryVigentes((string) $request->user()->getAuthIdentifier())
            ->where('leida', false)
            ->orderBy('fechaCreacion', 'desc')
            ->get();

        return response()->json([
            'data' => $notificaciones->map(fn (Notificacion $notificacion) => $this->present($notificacion))->values(),
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
            'data' => $this->present($notificacion->fresh()),
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

    private function queryVigentes(string $usuarioId)
    {
        return $this->queryForUser($usuarioId)
            ->where(function ($query): void {
                $query->whereNull('fechaExpiracion')
                    ->orWhere('fechaExpiracion', '>=', now());
            })
            ->where(function ($query): void {
                $query->where('leida', false)
                    ->orWhereNull('fechaLectura')
                    ->orWhere('fechaLectura', '>=', now()->subDays(30));
            });
    }

    private function present(Notificacion $notificacion): array
    {
        $icons = [
            'inscripcion_confirmada' => 'check',
            'inscripcion_cancelada' => 'x',
            'voluntariado_actualizado' => 'calendar',
            'voluntariado_cancelado' => 'x',
            'recordatorio_voluntariado' => 'bell',
            'asistencia_registrada' => 'heart',
            'articulo_aprobado' => 'file-check',
            'articulo_rechazado' => 'file-x',
            'organizador_verificado' => 'shield',
            'sistema' => 'info',
        ];
        $entity = is_array($notificacion->entidad) ? $notificacion->entidad : [];
        $url = data_get($notificacion->datos, 'url');
        if (!$url && ($entity['tipo'] ?? null) === 'voluntariado') {
            $url = url('/voluntariados/'.($entity['id'] ?? ''));
        } elseif (!$url && ($entity['tipo'] ?? null) === 'articulo') {
            $url = url('/articulos/'.($entity['id'] ?? ''));
        }

        return [
            'id' => (string) $notificacion->_id,
            'tipo' => (string) $notificacion->tipo,
            'titulo' => $notificacion->titulo,
            'mensaje' => $notificacion->mensaje,
            'entidad' => $entity,
            'prioridad' => $notificacion->prioridad ?: 'baja',
            'leida' => (bool) $notificacion->leida,
            'fechaCreacion' => $notificacion->fechaCreacion?->toISOString(),
            'fechaLectura' => $notificacion->fechaLectura?->toISOString(),
            'fechaExpiracion' => $notificacion->fechaExpiracion?->toISOString(),
            'datos' => $notificacion->datos ?: [],
            'icono' => $icons[(string) $notificacion->tipo] ?? 'info',
            'url' => $url,
        ];
    }

    private function perteneceAlUsuario(Notificacion $notificacion, string $usuarioId): bool
    {
        return (string) $notificacion->usuarioId === $usuarioId;
    }
}
