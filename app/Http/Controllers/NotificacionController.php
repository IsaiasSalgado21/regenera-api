<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use App\Models\PushSubscription;
use App\Models\User;
use App\Services\WebPushService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use MongoDB\BSON\ObjectId;

class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        $usuario = $request->user();
        $query = $usuario->rol === 'admin'
            ? Notificacion::query()
            : $this->queryForUser((string) $usuario->getAuthIdentifier());
        $notificaciones = $query
            ->orderBy('fechaCreacion', 'desc')
            ->get();

        return response()->json([
            'data' => $this->presentMany($notificaciones, $usuario),
            'message' => 'Notificaciones consultadas correctamente.',
        ]);
    }

    public function noLeidas(Request $request)
    {
        $usuario = $request->user();
        $query = $usuario->rol === 'admin'
            ? Notificacion::query()
            : $this->queryForUser((string) $usuario->getAuthIdentifier());
        $notificaciones = $query
            ->where('leida', false)
            ->orderBy('fechaCreacion', 'desc')
            ->get();

        return response()->json([
            'data' => $this->presentMany($notificaciones, $usuario),
            'count' => $notificaciones->count(),
            'message' => 'Notificaciones no leídas consultadas correctamente.',
        ]);
    }

    public function marcarLeida(Request $request, string $id)
    {
        $notificacion = Notificacion::findOrFail($id);
        abort_unless($this->perteneceAlUsuario($notificacion, (string) $request->user()->getAuthIdentifier()), 403);
        if (! $notificacion->leida) {
            $notificacion->update(['leida' => true, 'fechaLectura' => now()]);
        }

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

    public function preferencias(Request $request)
    {
        $usuario = $request->user();

        return response()->json([
            'data' => [
                'recordatorioFrecuencia' => $usuario->rol === 'admin'
                    ? null
                    : ($usuario->recordatorioFrecuencia ?: 'milestones'),
                'pushActivo' => PushSubscription::query()
                    ->where('usuarioId', $this->storedUserId((string) $usuario->getAuthIdentifier()))
                    ->exists(),
                'pushDisponible' => app(WebPushService::class)->available(),
            ],
        ]);
    }

    public function actualizarPreferencias(Request $request)
    {
        abort_if($request->user()->rol === 'admin', 403, 'Los recordatorios de voluntariados son para participantes y organizadores.');

        $validated = $request->validate([
            'recordatorioFrecuencia' => ['required', Rule::in(['none', 'milestones', 'daily'])],
        ]);
        $request->user()->update($validated);

        return response()->json([
            'data' => ['recordatorioFrecuencia' => $request->user()->recordatorioFrecuencia],
            'message' => 'Preferencias de notificación guardadas.',
        ]);
    }

    public function clavePublicaPush()
    {
        $webPush = app(WebPushService::class);
        abort_unless($webPush->available(), 503, 'Las notificaciones push no están configuradas en el servidor.');

        return response()->json(['data' => ['publicKey' => $webPush->publicKey()]]);
    }

    public function guardarSuscripcionPush(Request $request)
    {
        $webPush = app(WebPushService::class);
        abort_unless($webPush->available(), 503, 'Las notificaciones push no están configuradas en el servidor.');

        $validated = $request->validate([
            'endpoint' => ['required', 'url', 'max:2048'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', Rule::in(['aes128gcm', 'aesgcm'])],
        ]);
        $usuarioId = $this->storedUserId((string) $request->user()->getAuthIdentifier());

        $subscription = PushSubscription::query()->firstOrNew(['endpoint' => $validated['endpoint']]);
        abort_if(
            $subscription->exists && (string) $subscription->usuarioId !== (string) $request->user()->getAuthIdentifier(),
            403,
            'Esta suscripción push ya está asociada a otro perfil.',
        );
        $subscription->fill([
            'usuarioId' => $usuarioId,
            'publicKey' => $validated['keys']['p256dh'],
            'authToken' => $validated['keys']['auth'],
            'contentEncoding' => $validated['contentEncoding'] ?? 'aes128gcm',
            'fechaActualizacion' => now(),
        ]);
        $subscription->save();

        return response()->json(['message' => 'Notificaciones push activadas en este dispositivo.']);
    }

    public function eliminarSuscripcionPush(Request $request)
    {
        $validated = $request->validate(['endpoint' => ['required', 'url', 'max:2048']]);
        PushSubscription::query()
            ->where('usuarioId', $this->storedUserId((string) $request->user()->getAuthIdentifier()))
            ->where('endpoint', $validated['endpoint'])
            ->delete();

        return response()->json(['message' => 'Notificaciones push desactivadas en este dispositivo.']);
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

    private function presentMany($notifications, $currentUser): array
    {
        $recipientIds = $currentUser->rol === 'admin'
            ? $notifications->pluck('usuarioId')->map(fn ($id) => (string) $id)->unique()->values()
            : collect();
        $objectIds = $recipientIds
            ->filter(fn (string $id) => preg_match('/^[a-f0-9]{24}$/i', $id))
            ->map(fn (string $id) => new ObjectId($id))
            ->all();
        $recipients = $objectIds
            ? User::query()->whereIn('_id', $objectIds)->get(['_id', 'nombre', 'correo', 'rol'])->keyBy(fn (User $user) => (string) $user->getAuthIdentifier())
            : collect();

        return $notifications->map(function (Notificacion $notification) use ($currentUser, $recipients): array {
            $item = $this->present($notification);
            $item['esPropia'] = (string) $notification->usuarioId === (string) $currentUser->getAuthIdentifier();

            if ($currentUser->rol === 'admin') {
                $recipient = $recipients->get((string) $notification->usuarioId);
                $item['destinatario'] = $recipient ? [
                    'id' => (string) $recipient->getAuthIdentifier(),
                    'nombre' => $recipient->nombre,
                    'correo' => $recipient->correo,
                    'rol' => $recipient->rol,
                ] : null;
            }

            return $item;
        })->values()->all();
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
        if (! $url && ($entity['tipo'] ?? null) === 'voluntariado') {
            $url = url('/voluntariados/'.($entity['id'] ?? ''));
        } elseif (! $url && ($entity['tipo'] ?? null) === 'articulo') {
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

    private function storedUserId(string $usuarioId): string|ObjectId
    {
        return preg_match('/^[a-f0-9]{24}$/i', $usuarioId) ? new ObjectId($usuarioId) : $usuarioId;
    }
}
