<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Inscripcion;
use App\Models\Notificacion;
use App\Models\Voluntariado;
use App\Services\NotificationService;
use App\Services\VoluntariadoCupoService;
use App\Services\VoluntariadoHorario;
use App\Services\VoluntariadoWorkflow;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use MongoDB\BSON\ObjectId;
use Throwable;

class InscripcionController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificaciones,
        private readonly VoluntariadoHorario $horario,
        private readonly VoluntariadoCupoService $cupos,
        private readonly VoluntariadoWorkflow $workflow,
    ) {}

    public function index(Request $request)
    {
        $query = Inscripcion::query()->where('estado', '!=', 'reservando');
        if ($request->user()->rol !== 'admin') {
            $query->where('usuarioId', (string) $request->user()->getAuthIdentifier());
        }

        $inscripciones = $query->orderBy(
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

        if (! $usuario || ! in_array($usuario->rol, ['usuario', 'organizador'], true)) {
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

        if (! $voluntariado) {
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

        $startsAt = $this->horario->inicio($voluntariado);
        if ($startsAt?->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'La actividad ya inició; ya no es posible inscribirse a esta participación',
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

        if ($inscripcionExistente && $inscripcionExistente->estado !== 'reservando') {
            return response()->json([
                'success' => false,
                'message' => 'El usuario ya tiene una inscripción activa para este voluntariado.',
            ], 409);
        }

        $usuarioId = (string) $usuario->getAuthIdentifier();
        $voluntariadoId = (string) $voluntariado->_id;
        $inscripcion = $inscripcionExistente;
        if (! $inscripcion) {
            $inscripcionId = bin2hex(random_bytes(16));
            $inscripcion = new Inscripcion([
                'usuarioId' => $usuarioId,
                'voluntariadoId' => $voluntariadoId,
                'claveRegistroActiva' => $usuarioId.'|'.$voluntariadoId,
                'fechaInscripcion' => now(),
                'estado' => 'reservando',
                'observaciones' => $validated['observaciones'] ?? null,
            ]);
            $inscripcion->setAttribute('_id', $inscripcionId);
            try {
                $inscripcion->save();
            } catch (Throwable $exception) {
                $inscripcionExistente = Inscripcion::where('usuarioId', $usuarioId)
                    ->where('voluntariadoId', $voluntariadoId)
                    ->where('estado', '!=', 'cancelada')
                    ->first();
                if ($inscripcionExistente) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El usuario ya tiene una inscripción activa para este voluntariado.',
                    ], 409);
                }

                throw $exception;
            }
        }

        $inscripcionId = (string) $inscripcion->getKey();
        if (! $this->cupos->reservar($voluntariado, $inscripcionId)) {
            Inscripcion::where('_id', $inscripcion->getKey())
                ->where('estado', 'reservando')
                ->delete();

            return response()->json([
                'success' => false,
                'message' => 'Este voluntariado ya no tiene cupo disponible.',
            ], 409);
        }

        try {
            $finalizada = Inscripcion::where('_id', $inscripcion->getKey())
                ->where('estado', 'reservando')
                ->update(['estado' => 'inscrita']);
        } catch (Throwable $exception) {
            report($exception);
            try {
                $persistida = Inscripcion::find($inscripcion->getKey());
            } catch (Throwable $lookupError) {
                report($lookupError);
                throw $exception;
            }

            if ($persistida?->estado === 'inscrita') {
                $inscripcion = $persistida;
                $finalizada = 1;
            } else {
                try {
                    $this->cupos->liberar($voluntariado, $inscripcionId);
                } catch (Throwable $compensationError) {
                    report($compensationError);
                    throw $exception;
                }

                if ($persistida) {
                    $persistida->delete();
                }

                throw $exception;
            }
        }

        if ($finalizada !== 1) {
            $inscripcion = Inscripcion::find($inscripcion->getKey());
            if ($inscripcion?->estado !== 'inscrita') {
                $this->cupos->liberar($voluntariado, $inscripcionId);
                if ($inscripcion) {
                    $inscripcion->delete();
                }

                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo completar la inscripción; el cupo quedó liberado.',
                ], 409);
            }

            return response()->json([
                'success' => true,
                'message' => 'Inscripción registrada correctamente.',
                'inscripcion' => $inscripcion,
            ], 201);
        }

        $inscripcion = $inscripcion->fresh();
        $this->notificaciones->inscripcionConfirmada($inscripcion, $voluntariado);
        $this->notificaciones->nuevaInscripcionParaOrganizador($inscripcion, $voluntariado);

        return response()->json([
            'success' => true,
            'message' => 'Inscripción registrada correctamente.',
            'inscripcion' => $inscripcion,
        ], 201);
    }

    public function mia(Request $request, string $voluntariadoId)
    {
        $inscripcion = Inscripcion::query()
            ->where('usuarioId', (string) $request->user()->getAuthIdentifier())
            ->where('voluntariadoId', $voluntariadoId)
            ->whereIn('estado', ['inscrita', 'activa', 'completada'])
            ->first();
        $usuarioId = (string) $request->user()->getAuthIdentifier();
        $voluntariado = $inscripcion ? Voluntariado::find($voluntariadoId) : null;
        $asistencia = $inscripcion
            ? Asistencia::where('usuarioId', $usuarioId)
                ->where('voluntariadoId', $voluntariadoId)
                ->where('confirmada', true)
                ->first()
            : null;
        $solicitud = null;
        if ($inscripcion && $voluntariado) {
            $organizadorId = (string) $voluntariado->organizadorId;
            $storedOwnerId = preg_match('/^[a-f0-9]{24}$/i', $organizadorId)
                ? new ObjectId($organizadorId)
                : $organizadorId;
            $solicitud = Notificacion::query()
                ->where('usuarioId', $storedOwnerId)
                ->where('tipo', 'solicitud_asistencia')
                ->where('entidad.id', $voluntariadoId)
                ->where('datos.inscripcionId', (string) $inscripcion->getKey())
                ->orderBy('fechaCreacion', 'desc')
                ->first();
        }

        return response()->json([
            'success' => true,
            'inscrito' => $inscripcion !== null,
            'inscripcionId' => $inscripcion ? (string) $inscripcion->_id : null,
            'asistio' => (bool) $asistencia?->confirmada,
            'falto' => $inscripcion && $voluntariado
                ? $this->workflow->esFalta($voluntariado, $inscripcion, $asistencia, $this->horario)
                : false,
            'solicitudAsistenciaEstado' => data_get($solicitud?->datos, 'estadoSolicitud'),
        ]);
    }

    public function show(Request $request, string $id)
    {
        $inscripcion = Inscripcion::find($id);

        if (! $inscripcion) {
            return response()->json([
                'success' => false,
                'message' => 'Inscripción no encontrada.',
            ], 404);
        }
        abort_unless(
            $request->user()->rol === 'admin'
                || (string) $inscripcion->usuarioId === (string) $request->user()->getAuthIdentifier(),
            403,
        );

        return response()->json([
            'success' => true,
            'inscripcion' => $inscripcion,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $inscripcion = Inscripcion::find($id);

        if (! $inscripcion) {
            return response()->json([
                'success' => false,
                'message' => 'Inscripción no encontrada.',
            ], 404);
        }
        $isAdmin = $request->user()->rol === 'admin';
        abort_unless(
            $isAdmin || (string) $inscripcion->usuarioId === (string) $request->user()->getAuthIdentifier(),
            403,
        );

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

        if ($request->user()->rol !== 'admin') {
            abort_unless(
                ($validated['estado'] ?? null) === 'cancelada',
                403,
                'Solo puedes cancelar tu propia participación.',
            );

            return $this->cancelar($request, $id);
        }

        $estadoAnterior = $inscripcion->estado;
        if (array_key_exists('estado', $validated)) {
            $validated['claveRegistroActiva'] = $validated['estado'] === 'cancelada'
                ? null
                : (string) $inscripcion->usuarioId.'|'.(string) $inscripcion->voluntariadoId;
        }
        $estadoNuevo = $validated['estado'] ?? $estadoAnterior;
        $antesActivo = in_array($estadoAnterior, ['inscrita', 'activa', 'completada'], true);
        $despuesActivo = in_array($estadoNuevo, ['inscrita', 'activa', 'completada'], true);
        $voluntariado = Voluntariado::find((string) $inscripcion->voluntariadoId);
        $inscripcionId = (string) $inscripcion->getKey();

        if ($voluntariado && ! $antesActivo && $despuesActivo
            && ! $this->cupos->reservar($voluntariado, $inscripcionId, false)) {
            abort(409, 'No hay cupo disponible para reactivar esta inscripción.');
        }

        if ($antesActivo !== $despuesActivo) {
            $actualizadas = Inscripcion::where('_id', $inscripcion->getKey())
                ->where('estado', $estadoAnterior)
                ->update($validated);

            if ($actualizadas !== 1) {
                $actual = Inscripcion::find($inscripcion->getKey());
                if ($voluntariado && ! in_array($actual?->estado, ['inscrita', 'activa', 'completada'], true)) {
                    $this->cupos->liberar($voluntariado, $inscripcionId);
                }

                abort(409, 'La inscripción cambió mientras se intentaba actualizar.');
            }
        } else {
            $inscripcion->update($validated);
        }

        if ($voluntariado && ! $despuesActivo && array_key_exists('estado', $validated)) {
            $this->cupos->liberar($voluntariado, $inscripcionId);
        }
        if (($validated['estado'] ?? null) === 'cancelada' && $estadoAnterior !== 'cancelada') {
            $this->notificarCancelacion($inscripcion);
        }

        return response()->json([
            'success' => true,
            'message' => 'Inscripción actualizada correctamente.',
            'inscripcion' => $inscripcion->fresh(),
        ]);
    }

    public function cancelar(Request $request, string $id)
    {
        $inscripcion = Inscripcion::find($id);
        abort_unless(
            $inscripcion
                && (string) $inscripcion->usuarioId === (string) $request->user()->getAuthIdentifier(),
            404,
            'No se encontró una inscripción activa para tu perfil.',
        );
        $voluntariado = Voluntariado::find((string) $inscripcion->voluntariadoId);
        $inscripcionId = (string) $inscripcion->getKey();
        if ($inscripcion->estado === 'cancelada') {
            if ($voluntariado) {
                $this->cupos->liberar($voluntariado, $inscripcionId);
            }

            return response()->json(['success' => true, 'message' => 'Tu participación ya estaba cancelada.']);
        }
        abort_unless(in_array($inscripcion->estado, ['inscrita', 'activa', 'completada'], true), 409, 'Esta inscripción ya no está activa.');

        $startsAt = $voluntariado ? $this->horario->inicio($voluntariado) : null;
        if ($startsAt?->isPast()) {
            abort(409, 'Ya no se puede cancelar la participación porque la actividad comenzó.');
        }

        $cancelacionAplicada = Inscripcion::where('_id', $inscripcion->getKey())
            ->whereIn('estado', ['inscrita', 'activa', 'completada'])
            ->update([
                'estado' => 'cancelada',
                'claveRegistroActiva' => null,
            ]);

        if ($cancelacionAplicada !== 1) {
            $actual = Inscripcion::find($inscripcion->getKey());
            if ($actual?->estado === 'cancelada') {
                if ($voluntariado) {
                    $this->cupos->liberar($voluntariado, $inscripcionId);
                }

                return response()->json(['success' => true, 'message' => 'Tu participación ya estaba cancelada.']);
            }

            abort(409, 'Esta inscripción ya no está activa.');
        }

        if ($voluntariado) {
            $this->cupos->liberar($voluntariado, $inscripcionId);
        }

        if ($voluntariado) {
            $this->notificaciones->inscripcionCancelada($inscripcion->fresh(), $voluntariado);
            $this->notificaciones->inscripcionCanceladaParaOrganizador($inscripcion->fresh(), $voluntariado);
        }

        return response()->json([
            'success' => true,
            'message' => 'Tu participación fue cancelada.',
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        abort_unless($request->user()->rol === 'admin', 403, 'Solo un administrador puede eliminar una inscripción.');
        $inscripcion = Inscripcion::find($id);

        if (! $inscripcion) {
            return response()->json([
                'success' => false,
                'message' => 'Inscripción no encontrada.',
            ], 404);
        }

        $voluntariado = Voluntariado::find((string) $inscripcion->voluntariadoId);
        $inscripcionId = (string) $inscripcion->getKey();
        $estadoParaEliminar = $inscripcion->estado;
        if (in_array($estadoParaEliminar, ['inscrita', 'activa', 'completada'], true)) {
            $actualizadas = Inscripcion::where('_id', $inscripcion->getKey())
                ->whereIn('estado', ['inscrita', 'activa', 'completada'])
                ->update(['estado' => 'cancelada', 'claveRegistroActiva' => null]);
            if ($actualizadas !== 1) {
                abort(409, 'La inscripción cambió mientras se intentaba eliminar.');
            }
            $estadoParaEliminar = 'cancelada';
        }

        if ($voluntariado) {
            $this->cupos->liberar($voluntariado, $inscripcionId);
        }

        $eliminada = Inscripcion::where('_id', $inscripcion->getKey())
            ->where('estado', $estadoParaEliminar)
            ->delete();
        abort_unless($eliminada === 1, 409, 'La inscripción cambió mientras se intentaba eliminar.');

        return response()->json([
            'success' => true,
            'message' => 'Inscripción eliminada correctamente.',
        ]);
    }

    private function notificarCancelacion(Inscripcion $inscripcion): void
    {
        $voluntariado = Voluntariado::find((string) $inscripcion->voluntariadoId);
        if ($voluntariado) {
            $this->notificaciones->inscripcionCancelada($inscripcion->fresh(), $voluntariado);
            $this->notificaciones->inscripcionCanceladaParaOrganizador($inscripcion->fresh(), $voluntariado);
        }
    }
}
