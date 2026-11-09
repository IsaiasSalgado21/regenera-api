<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Inscripcion;
use App\Models\User;
use App\Models\Voluntariado;
use App\Services\NotificationService;
use App\Services\VoluntariadoHorario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use MongoDB\Driver\Exception\BulkWriteException;

class AsistenciaController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificaciones,
        private readonly VoluntariadoHorario $horario,
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

            $voluntariado = Voluntariado::where('tokenAsistencia', $validated['tokenAsistencia'])->first();
            if (! $voluntariado) {
                return response()->json([
                    'success' => false,
                    'message' => 'El código QR no es válido.',
                ], 404);
            }

            if (! $voluntariado->codigoAsistenciaActivo) {
                return response()->json([
                    'success' => false,
                    'message' => 'El código QR de este voluntariado está desactivado.',
                ], 410);
            }
        } else {
            if (! in_array($request->user()->rol, ['admin', 'organizador'], true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo un administrador u organizador puede registrar asistencia manual.',
                ], 403);
            }

            $voluntariado = Voluntariado::find($validated['voluntariadoId']);
            if (! $voluntariado) {
                return response()->json([
                    'success' => false,
                    'message' => 'El voluntariado indicado no existe.',
                ], 404);
            }

            if (
                $request->user()->rol === 'organizador'
                && (string) $voluntariado->organizadorId !== (string) $request->user()->getAuthIdentifier()
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para registrar asistencia en este voluntariado.',
                ], 403);
            }
        }

        if (($voluntariado->estado ?? null) !== 'publicado') {
            return response()->json([
                'success' => false,
                'message' => 'La asistencia solo puede registrarse en un voluntariado publicado.',
            ], 409);
        }

        $inicio = $this->horario->inicio($voluntariado);
        $fin = $this->horario->fin($voluntariado);
        $ahora = $this->horario->ahora();
        if (! $inicio || ! $fin || $ahora->lt($inicio) || $ahora->gt($fin)) {
            return response()->json([
                'success' => false,
                'message' => 'La asistencia solo puede registrarse durante el horario del voluntariado.',
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
            ->whereIn('estado', ['inscrita', 'activa'])
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

        $inscripcion->update(['estado' => 'completada']);
        $this->notificaciones->asistenciaRegistrada($usuarioId, $voluntariado);

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
