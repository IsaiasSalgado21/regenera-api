<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\Voluntariado;
use App\Services\NotificationService;
use App\Services\VoluntariadoHorario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InscripcionController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificaciones,
        private readonly VoluntariadoHorario $horario,
    ) {}

    public function index(Request $request)
    {
        $query = Inscripcion::query();
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
                'message' => 'Las inscripciones cerraron porque la actividad ya comenzó.',
            ], 409);
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
        $this->notificaciones->inscripcionConfirmada($inscripcion, $voluntariado);

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
            ->whereIn('estado', ['inscrita', 'activa'])
            ->first();

        return response()->json([
            'success' => true,
            'inscrito' => $inscripcion !== null,
            'inscripcionId' => $inscripcion ? (string) $inscripcion->_id : null,
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
        $inscripcion->update($validated);
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
        abort_unless(in_array($inscripcion->estado, ['inscrita', 'activa'], true), 409, 'Esta inscripción ya no está activa.');

        $voluntariado = Voluntariado::find((string) $inscripcion->voluntariadoId);
        $startsAt = $voluntariado ? $this->horario->inicio($voluntariado) : null;
        if ($startsAt?->isPast()) {
            abort(409, 'Ya no se puede cancelar la participación porque la actividad comenzó.');
        }

        $inscripcion->update(['estado' => 'cancelada']);
        if ($voluntariado) {
            $this->notificaciones->inscripcionCancelada($inscripcion->fresh(), $voluntariado);
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

        $inscripcion->delete();

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
        }
    }
}
