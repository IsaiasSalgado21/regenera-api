<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Inscripcion;
use App\Models\Notificacion;
use App\Models\User;
use App\Models\Voluntariado;
use App\Services\NotificationService;
use App\Services\GamificacionService;
use App\Services\VoluntariadoHorario;
use App\Services\VoluntariadoWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use MongoDB\BSON\ObjectId;
use MongoDB\Driver\Exception\BulkWriteException;

class AsistenciaController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificaciones,
        private readonly VoluntariadoHorario $horario,
        private readonly VoluntariadoWorkflow $workflow,
        private readonly GamificacionService $gamificacion,
    ) {}

    public function index(Request $request)
    {
        $query = Asistencia::query();
        if ($request->user()->rol !== 'admin') {
            $query->where('usuarioId', (string) $request->user()->getAuthIdentifier());
        } elseif ($request->filled('usuarioId')) {
            $query->where('usuarioId', $request->string('usuarioId')->toString());
        }

        if ($request->filled('voluntariadoId')) {
            $query->where('voluntariadoId', $request->string('voluntariadoId')->toString());
        }

        return response()->json([
            'success' => true,
            'asistencias' => $query->orderBy('fecha', 'desc')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'metodo' => ['required', Rule::in(['QR', 'manual'])],
            'tokenAsistencia' => [
                'required_if:metodo,QR',
                'prohibited_if:metodo,manual',
                'string',
                'max:128',
            ],
            'usuarioId' => [
                'required_if:metodo,manual',
                'prohibited_if:metodo,QR',
                'string',
                'max:100',
            ],
            'voluntariadoId' => [
                'required_if:metodo,manual',
                'prohibited_if:metodo,QR',
                'string',
                'max:100',
            ],
        ]);

        $usuario = $validated['metodo'] === 'QR'
            ? $request->user()
            : User::find($validated['usuarioId']);
        if (! $usuario) {
            return response()->json([
                'success' => false,
                'message' => 'El usuario indicado no existe.',
            ], 404);
        }

        if ($usuario->estado !== 'activo') {
            return response()->json([
                'success' => false,
                'message' => 'La cuenta no está activa.',
            ], 403);
        }

        if ($validated['metodo'] === 'QR') {
            if (! in_array($usuario->rol, ['usuario', 'organizador'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo usuarios y organizadores pueden registrar asistencia con QR.',
                ], 403);
            }

            $codigo = strtoupper(trim($validated['tokenAsistencia']));
            $voluntariado = strlen($codigo) === 8
                ? Voluntariado::where('codigoCorto', $codigo)->first()
                : Voluntariado::where('tokenAsistencia', $validated['tokenAsistencia'])->first();
            if (! $voluntariado) {
                return response()->json([
                    'success' => false,
                    'message' => 'El código no es válido.',
                ], 404);
            }
            if (! ($voluntariado->codigoAsistenciaActivo ?? true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El código QR de asistencia está desactivado.',
                ], 409);
            }
        } else {
            $voluntariado = Voluntariado::find($validated['voluntariadoId']);
            if (! $voluntariado) {
                return response()->json([
                    'success' => false,
                    'message' => 'El voluntariado indicado no existe.',
                ], 404);
            }

            $requestUser = $request->user();
            $isOwner = (string) $voluntariado->organizadorId === (string) $requestUser->getAuthIdentifier();
            $canManageAttendance = $requestUser->rol === 'admin'
                || ($requestUser->rol === 'organizador' && $isOwner)
                || $isOwner;
            if (! $canManageAttendance) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo el administrador, organizador o propietario puede registrar asistencia manual.',
                ], 403);
            }
        }

        $esManual = $validated['metodo'] === 'manual';
        $estadosPermitidos = $esManual ? ['publicado', 'finalizado'] : ['publicado'];
        if (! in_array($voluntariado->estado ?? null, $estadosPermitidos, true)) {
            return response()->json([
                'success' => false,
                'message' => $esManual
                    ? 'La asistencia manual solo puede registrarse en una actividad publicada o finalizada.'
                    : 'La asistencia solo puede registrarse en un voluntariado publicado.',
            ], 409);
        }

        $inicio = $this->horario->inicio($voluntariado);
        $fin = $this->horario->fin($voluntariado);
        $ahora = $this->horario->ahora();
        if (! $inicio || ! $fin || $ahora->lt($inicio) || (! $esManual && $ahora->gt($fin))) {
            return response()->json([
                'success' => false,
                'message' => $esManual
                    ? 'La asistencia manual solo puede registrarse a partir del inicio del voluntariado.'
                    : 'La asistencia solo puede registrarse durante el horario del voluntariado.',
            ], 409);
        }

        $usuarioId = (string) $usuario->getAuthIdentifier();
        $voluntariadoId = (string) $voluntariado->_id;
        $asistenciaQuery = Asistencia::where('usuarioId', $usuarioId)
            ->where('voluntariadoId', $voluntariadoId);

        if ($asistenciaQuery->exists()) {
            return $this->asistenciaDuplicada();
        }

        $inscripcion = Inscripcion::where('usuarioId', $usuarioId)
            ->where('voluntariadoId', $voluntariadoId)
            ->whereIn('estado', ['inscrita', 'activa', 'completada'])
            ->first();

        if (! $inscripcion) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes una inscripción activa para este voluntariado.',
            ], 422);
        }

        $fecha = $this->horario->ahora();
        try {
            $asistencia = Asistencia::create([
                'usuarioId' => $usuarioId,
                'voluntariadoId' => $voluntariadoId,
                'fecha' => $fecha,
                'hora' => $fecha->format('H:i:s'),
                'metodo' => $validated['metodo'],
                'confirmada' => true,
            ]);
        } catch (BulkWriteException $exception) {
            if (! $asistenciaQuery->exists()) {
                throw $exception;
            }

            return $this->asistenciaDuplicada();
        }

        $this->gamificacion->registrarParticipacion($usuario, $voluntariado);
        $this->notificaciones->asistenciaRegistrada($usuarioId, $voluntariado);
        $this->notificaciones->asistenciaRegistradaParaPropietario($asistencia, $voluntariado);

        return response()->json([
            'success' => true,
            'message' => 'Asistencia registrada correctamente.',
            'asistencia' => $asistencia,
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $asistencia = Asistencia::find($id);
        if (! $asistencia) {
            return response()->json([
                'success' => false,
                'message' => 'Registro de asistencia no encontrado.',
            ], 404);
        }

        abort_unless(
            $request->user()->rol === 'admin'
                || (string) $asistencia->usuarioId === (string) $request->user()->getAuthIdentifier(),
            403,
        );

        return response()->json([
            'success' => true,
            'asistencia' => $asistencia,
        ]);
    }

    public function solicitarCorreccion(Request $request)
    {
        $validated = $request->validate([
            'voluntariadoId' => ['required', 'string', 'max:100'],
        ]);
        $participante = $request->user();
        abort_unless(in_array($participante->rol, ['usuario', 'organizador'], true), 403);

        $voluntariado = Voluntariado::find($validated['voluntariadoId']);
        abort_unless($voluntariado && in_array($voluntariado->estado, ['publicado', 'finalizado'], true), 404);
        abort_if(
            (string) $voluntariado->organizadorId === (string) $participante->getAuthIdentifier(),
            422,
            'El propietario no puede solicitar una revisión de su propia actividad.',
        );

        $fin = $this->horario->fin($voluntariado);
        abort_unless($fin && $fin->isPast(), 409, 'La solicitud estará disponible cuando termine la actividad.');

        $inscripcion = Inscripcion::where('usuarioId', (string) $participante->getAuthIdentifier())
            ->where('voluntariadoId', (string) $voluntariado->_id)
            ->first();
        abort_unless($inscripcion !== null, 422, 'No tienes una inscripción vigente para esta actividad.');

        $asistencia = Asistencia::where('usuarioId', (string) $participante->getAuthIdentifier())
            ->where('voluntariadoId', (string) $voluntariado->_id)
            ->where('confirmada', true)
            ->exists();
        abort_if($asistencia, 409, 'Tu asistencia ya está registrada.');

        abort_unless(
            $this->workflow->esFalta($voluntariado, $inscripcion, null, $this->horario),
            409,
            'Aún no se puede solicitar la revisión de una falta.',
        );

        $solicitud = $this->notificaciones->solicitudCorreccionAsistencia($participante, $inscripcion, $voluntariado);

        return response()->json([
            'success' => true,
            'message' => 'Enviamos tu solicitud al propietario del voluntariado.',
            'solicitud' => ['id' => (string) $solicitud->_id, 'estado' => data_get($solicitud->datos, 'estadoSolicitud')],
        ], 201);
    }

    public function responderSolicitud(Request $request, string $id)
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['aceptar', 'rechazar'])],
            'motivo' => ['nullable', 'string', 'max:1000'],
        ]);
        $solicitud = Notificacion::findOrFail($id);
        abort_unless($solicitud->tipo === 'solicitud_asistencia', 404);
        $voluntariadoId = (string) data_get($solicitud->entidad, 'id');
        $voluntariado = Voluntariado::findOrFail($voluntariadoId);
        $responder = $request->user();
        abort_unless(
            $responder->rol === 'admin'
                || (string) $voluntariado->organizadorId === (string) $responder->getAuthIdentifier(),
            403,
        );

        $estadoSolicitud = data_get($solicitud->datos, 'estadoSolicitud');
        $solicitanteId = (string) data_get($solicitud->datos, 'solicitanteId', '');
        $inscripcionId = (string) data_get($solicitud->datos, 'inscripcionId', '');
        $aceptar = $validated['decision'] === 'aceptar';

        if ($estadoSolicitud === 'aceptada' && $aceptar) {
            return response()->json(['success' => true, 'message' => 'La asistencia ya fue confirmada.']);
        }
        if ($estadoSolicitud === 'rechazada' && ! $aceptar) {
            return response()->json(['success' => true, 'message' => 'La solicitud ya fue rechazada.']);
        }

        $collection = DB::connection('mongodb')->getMongoDB()->selectCollection('notificaciones');
        $notificationId = $solicitud->getKey();
        if (is_string($notificationId) && preg_match('/^[a-f0-9]{24}$/i', $notificationId)) {
            $notificationId = new ObjectId($notificationId);
        }

        if ($aceptar) {
            if ($estadoSolicitud === 'pendiente') {
                $claim = $collection->updateOne(
                    ['_id' => $notificationId, 'datos.estadoSolicitud' => 'pendiente'],
                    ['$set' => [
                        'datos.estadoSolicitud' => 'aceptando',
                        'datos.decision' => 'aceptar',
                        'datos.respondidaPor' => (string) $responder->getAuthIdentifier(),
                    ]],
                );
                abort_unless($claim->getModifiedCount() === 1, 409, 'La solicitud ya fue respondida.');
            } else {
                abort_unless(
                    $estadoSolicitud === 'aceptando'
                        && data_get($solicitud->datos, 'decision') === 'aceptar',
                    409,
                    'La solicitud ya fue respondida.',
                );
            }

            $participante = User::find($solicitanteId);
            $inscripcion = Inscripcion::where('_id', $inscripcionId)
                ->where('usuarioId', $solicitanteId)
                ->where('voluntariadoId', $voluntariadoId)
                ->whereIn('estado', ['inscrita', 'activa', 'completada'])
                ->first();
            abort_unless($participante && $inscripcion, 409, 'La inscripción ya no está vigente.');

            $asistenciaQuery = Asistencia::where('usuarioId', $solicitanteId)
                ->where('voluntariadoId', $voluntariadoId);
            $asistencia = $asistenciaQuery->first();
            if (! $asistencia) {
                $fechaEvento = $this->horario->inicio($voluntariado) ?? $this->horario->ahora();
                try {
                    $asistencia = Asistencia::create([
                        'usuarioId' => $solicitanteId,
                        'voluntariadoId' => $voluntariadoId,
                        'fecha' => $fechaEvento,
                        'hora' => $voluntariado->horaInicio ?: $fechaEvento->format('H:i:s'),
                        'metodo' => 'manual',
                        'confirmada' => true,
                    ]);
                } catch (BulkWriteException $exception) {
                    $asistencia = $asistenciaQuery->first();
                    if (! $asistencia) {
                        throw $exception;
                    }
                }
            }

            $finalizada = $collection->updateOne(
                ['_id' => $notificationId, 'datos.estadoSolicitud' => 'aceptando', 'datos.decision' => 'aceptar'],
                ['$set' => [
                    'datos.estadoSolicitud' => 'aceptada',
                    'datos.fechaRespuesta' => now(),
                ]],
            );
            abort_unless($finalizada->getMatchedCount() === 1, 409, 'No se pudo completar la respuesta. Vuelve a intentarlo.');

            $this->gamificacion->registrarParticipacion($participante, $voluntariado);
            $this->notificaciones->respuestaSolicitudAsistencia(
                $participante,
                $voluntariado,
                true,
            );

            return response()->json(['success' => true, 'message' => 'Asistencia confirmada.']);
        }

        $resultado = $collection->updateOne(
            ['_id' => $notificationId, 'datos.estadoSolicitud' => 'pendiente'],
            ['$set' => [
                'datos.estadoSolicitud' => 'rechazada',
                'datos.decision' => 'rechazar',
                'datos.motivoRespuesta' => $validated['motivo'] ?? null,
                'datos.respondidaPor' => (string) $responder->getAuthIdentifier(),
                'datos.fechaRespuesta' => now(),
                'leida' => true,
                'fechaLectura' => now(),
            ]],
        );
        abort_unless($resultado->getModifiedCount() === 1, 409, 'La solicitud ya fue respondida.');

        $participante = User::find($solicitanteId);
        if ($participante) {
            $this->notificaciones->respuestaSolicitudAsistencia(
                $participante,
                $voluntariado,
                false,
                $validated['motivo'] ?? null,
            );
        }

        return response()->json(['success' => true, 'message' => 'Solicitud rechazada.']);
    }

    public function destroy(Request $request, string $id)
    {
        abort_unless($request->user()->rol === 'admin', 403);
        $asistencia = Asistencia::find($id);

        if (! $asistencia) {
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

    private function asistenciaDuplicada()
    {
        return response()->json([
            'success' => false,
            'message' => 'Ya tienes asistencia registrada para este voluntariado.',
        ], 409);
    }
}
