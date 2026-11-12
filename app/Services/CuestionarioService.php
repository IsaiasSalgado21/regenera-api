<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Cuestionario;
use App\Models\IntentoCuestionario;
use App\Models\Inscripcion;
use App\Models\RespuestaCuestionario;
use App\Models\User;
use App\Models\Voluntariado;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\ObjectId;
use Throwable;

class CuestionarioService
{
    public function __construct(
        private readonly EvaluacionCuestionario $evaluador,
        private readonly VoluntariadoWorkflow $workflow,
        private readonly VoluntariadoHorario $horario,
    ) {}

    public function catalogo(User $usuario): array
    {
        return Cuestionario::query()
            ->where('activo', true)
            ->orderByDesc('version')
            ->orderBy('slug')
            ->get()
            ->unique('slug')
            ->sortBy('slug')
            ->values()
            ->filter(fn (Cuestionario $cuestionario) => in_array($usuario->rol, $cuestionario->audiencia ?? [], true)
                && (! ($cuestionario->requiereOrganizadorVerificado ?? false)
                    || ($usuario->rol === 'organizador' && (bool) $usuario->organizadorVerificado)))
            ->map(function (Cuestionario $cuestionario) use ($usuario): array {
                $attempts = $this->userAttempts($usuario, $cuestionario->slug);
                $latest = $attempts->first();
                $eligibleActivities = $cuestionario->slug === 'restauracion-entorno'
                    ? $this->actividadesElegibles($usuario)
                    : [];
                if ($cuestionario->slug === 'restauracion-entorno') {
                    $eligibleActivities = array_map(function (array $activity) use ($attempts): array {
                        $activityAttempts = $attempts->where('actividadId', $activity['id']);
                        $activity['aprobado'] = $activityAttempts->contains(fn ($attempt) => $attempt->estado === 'aprobado');
                        $activity['intentos'] = $activityAttempts->count();
                        $activity['puntuacion'] = $activityAttempts->first()?->puntuacion === null
                            ? null
                            : (float) $activityAttempts->first()->puntuacion;

                        return $activity;
                    }, $eligibleActivities);
                }
                $passed = $attempts->contains(fn ($attempt) => $attempt->estado === 'aprobado');
                $available = $cuestionario->slug === 'restauracion-entorno'
                    ? collect($eligibleActivities)->contains(fn (array $activity) => ! $activity['aprobado'])
                    : ! $passed;
                $available = $available
                    && (! ($cuestionario->requiereOrganizadorVerificado ?? false) || (bool) $usuario->organizadorVerificado);

                return [
                    'slug' => (string) $cuestionario->slug,
                    'version' => (int) $cuestionario->version,
                    'titulo' => (string) $cuestionario->titulo,
                    'descripcion' => (string) $cuestionario->descripcion,
                    'guia' => (string) ($cuestionario->guia ?? ''),
                    'minimoAprobacion' => (float) $cuestionario->minimoAprobacion,
                    'totalPreguntas' => count($cuestionario->preguntas ?? []),
                    'estado' => $passed ? 'aprobado' : ($latest?->estado ?? 'pendiente'),
                    'disponible' => $available,
                    'bloqueo' => $this->motivoBloqueo($cuestionario, $usuario, $eligibleActivities, $passed),
                    'intentos' => $attempts->count(),
                    'ultimoIntento' => $latest?->fechaUltimoIntento?->toISOString(),
                    'fechaAprobacion' => $attempts
                        ->first(fn ($attempt) => $attempt->estado === 'aprobado')
                        ?->fechaAprobacion?->toISOString(),
                    'puntuacion' => $latest?->puntuacion === null ? null : (float) $latest->puntuacion,
                    'medalla' => $cuestionario->medalla,
                    'actividadesElegibles' => $eligibleActivities,
                ];
            })
            ->values()
            ->all();
    }

    public function iniciar(User $usuario, string $slug, ?string $actividadId = null): array
    {
        $cuestionario = Cuestionario::query()
            ->where('slug', $slug)
            ->where('activo', true)
            ->orderByDesc('version')
            ->firstOrFail();
        abort_unless(in_array($usuario->rol, $cuestionario->audiencia ?? [], true), 403, 'Este cuestionario no está disponible para tu rol.');
        abort_unless(
            ! ($cuestionario->requiereOrganizadorVerificado ?? false) || ($usuario->rol === 'organizador' && (bool) $usuario->organizadorVerificado),
            403,
            'Este cuestionario es exclusivo para organizadores verificados.',
        );

        if ($slug === 'restauracion-entorno') {
            abort_unless((bool) $actividadId, 422, 'Selecciona la actividad a la que asististe.');
            $eligibleIds = array_column($this->actividadesElegibles($usuario), 'id');
            abort_unless(in_array($actividadId, $eligibleIds, true), 403, 'El cuestionario se habilita después de una asistencia confirmada en una actividad finalizada.');
        }

        $attempts = $this->userAttempts($usuario, $slug);
        $passed = $attempts->first(fn (IntentoCuestionario $attempt) => $attempt->estado === 'aprobado'
            && ($slug !== 'restauracion-entorno' || (string) $attempt->actividadId === (string) $actividadId));
        abort_if($passed, 409, 'Este cuestionario ya está aprobado.');

        $openAttempt = $attempts->first(fn (IntentoCuestionario $attempt) => $attempt->estado === 'en_progreso'
            && (string) ($attempt->actividadId ?? '') === (string) ($actividadId ?? ''));
        if ($openAttempt) {
            return $this->presentAttempt($cuestionario, $openAttempt);
        }

        $opcionesOrden = [];
        foreach ($cuestionario->preguntas ?? [] as $pregunta) {
            $order = range(0, count($pregunta['opciones']) - 1);
            shuffle($order);
            $opcionesOrden[$pregunta['id']] = $order;
        }

        try {
            $attempt = IntentoCuestionario::create([
                'usuarioId' => (string) $usuario->getAuthIdentifier(),
                'cuestionarioSlug' => $slug,
                'cuestionarioVersion' => (int) $cuestionario->version,
                'actividadId' => $actividadId,
                'estado' => 'en_progreso',
                'opcionesOrden' => $opcionesOrden,
                'respuestas' => [],
                'preguntasCorrectas' => 0,
                'totalPreguntas' => count($cuestionario->preguntas ?? []),
                'fechaInicio' => now(),
                'fechaUltimoIntento' => null,
                'fechaAprobacion' => null,
            ]);
        } catch (Throwable $exception) {
            $attempt = $this->userAttempts($usuario, $slug)
                ->first(fn ($item) => $item->estado === 'en_progreso'
                    && (string) ($item->actividadId ?? '') === (string) ($actividadId ?? ''));
            if (! $attempt) {
                throw $exception;
            }
        }

        return $this->presentAttempt($cuestionario, $attempt);
    }

    public function responder(User $usuario, string $attemptId, string $questionId, int $optionIndex): array
    {
        $attempt = IntentoCuestionario::query()
            ->where('_id', $this->mongoId($attemptId))
            ->where('usuarioId', (string) $usuario->getAuthIdentifier())
            ->firstOrFail();
        $cuestionario = Cuestionario::query()
            ->where('slug', $attempt->cuestionarioSlug)
            ->where('version', $attempt->cuestionarioVersion)
            ->firstOrFail();
        $pregunta = collect($cuestionario->preguntas ?? [])->firstWhere('id', $questionId);
        abort_unless($pregunta, 404, 'La pregunta no pertenece a este cuestionario.');

        $respuestas = $this->respuestasDelIntento($attempt);
        if (isset($respuestas[$questionId])) {
            $attempt = $this->finalizarIntento($cuestionario, $attempt);

            return $this->respuestaFinal($attempt, $questionId, $respuestas[$questionId]);
        }
        abort_unless($attempt->estado === 'en_progreso', 409, 'Este intento ya fue calificado.');

        $optionOrder = (array) (($attempt->opcionesOrden ?? [])[$questionId] ?? range(0, count($pregunta['opciones']) - 1));
        $originalOptionIndex = $optionOrder[$optionIndex] ?? null;
        abort_unless($originalOptionIndex !== null, 422, 'Selecciona una de las opciones disponibles.');
        $feedback = $this->evaluador->evaluarRespuesta($pregunta, (int) $originalOptionIndex);
        $answerKey = (string) $attempt->getKey().':'.$questionId;
        try {
            $answer = RespuestaCuestionario::create([
                'intentoId' => (string) $attempt->getKey(),
                'usuarioId' => (string) $usuario->getAuthIdentifier(),
                'cuestionarioSlug' => (string) $cuestionario->slug,
                'preguntaId' => $questionId,
                'opcion' => (int) $originalOptionIndex,
                'opcionPresentada' => $optionIndex,
                ...$feedback,
                'respondidaEn' => now(),
                'claveUnica' => $answerKey,
            ]);
        } catch (Throwable $exception) {
            $answer = RespuestaCuestionario::query()->where('claveUnica', $answerKey)->first();
            if (! $answer) {
                throw $exception;
            }
        }
        IntentoCuestionario::where('_id', $this->mongoId($attempt->getKey()))
            ->where('estado', 'en_progreso')
            ->update(['fechaUltimoIntento' => now()]);
        $attempt = IntentoCuestionario::find($attempt->getKey());
        abort_unless($attempt !== null, 409, 'No se pudo recuperar el intento.');
        $respuestas = $this->respuestasDelIntento($attempt);

        $attempt = $this->finalizarIntento($cuestionario, $attempt);

        return $this->respuestaFinal($attempt, $questionId, $respuestas[$questionId]);
    }

    public function actividadesElegibles(User $usuario): array
    {
        if ($usuario->rol !== 'usuario') {
            return [];
        }

        $activeIds = Inscripcion::query()
            ->where('usuarioId', (string) $usuario->getAuthIdentifier())
            ->whereIn('estado', ['inscrita', 'activa', 'completada'])
            ->get(['voluntariadoId'])
            ->pluck('voluntariadoId')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->all();
        if (! $activeIds) {
            return [];
        }

        $confirmedIds = Asistencia::query()
            ->where('usuarioId', (string) $usuario->getAuthIdentifier())
            ->where('confirmada', true)
            ->whereIn('voluntariadoId', $activeIds)
            ->get(['voluntariadoId'])
            ->pluck('voluntariadoId')
            ->map(fn ($id) => (string) $id)
            ->unique();

        return $confirmedIds
            ->map(function (string $id): ?array {
                $voluntariado = Voluntariado::find($this->mongoId($id));
                if (! $voluntariado || ! $this->workflow->finalizado($voluntariado, $this->horario)) {
                    return null;
                }

                return ['id' => (string) $voluntariado->_id, 'titulo' => (string) $voluntariado->titulo];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function userAttempts(User $usuario, string $slug)
    {
        return IntentoCuestionario::query()
            ->where('usuarioId', (string) $usuario->getAuthIdentifier())
            ->where('cuestionarioSlug', $slug)
            ->orderByDesc('created_at')
            ->get();
    }

    private function presentAttempt(Cuestionario $cuestionario, IntentoCuestionario $attempt): array
    {
        $answered = $this->respuestasDelIntento($attempt);
        $question = collect($cuestionario->preguntas ?? [])
            ->first(fn (array $item) => ! array_key_exists($item['id'], $answered));

        return [
            'intentoId' => (string) $attempt->getKey(),
            'cuestionario' => [
                'slug' => (string) $cuestionario->slug,
                'titulo' => (string) $cuestionario->titulo,
                'descripcion' => (string) $cuestionario->descripcion,
                'minimoAprobacion' => (float) $cuestionario->minimoAprobacion,
                'totalPreguntas' => count($cuestionario->preguntas ?? []),
            ],
            'preguntas' => $this->preguntasDelIntento($cuestionario, $attempt),
            'respuestasPrevias' => collect($answered)->map(fn (array $answer, string $questionId) => [
                'preguntaId' => $questionId,
                'correcta' => (bool) ($answer['correcta'] ?? false),
                'respuestaCorrecta' => $answer['respuestaCorrecta'] ?? null,
                'explicacion' => $answer['explicacion'] ?? null,
            ])->values(),
            'siguientePreguntaId' => $question['id'] ?? null,
        ];
    }

    private function respuestaGuardada(IntentoCuestionario $attempt, string $questionId, array $answer): array
    {
        return [
            'preguntaId' => $questionId,
            'correcta' => (bool) ($answer['correcta'] ?? false),
            'respuestaCorrecta' => $answer['respuestaCorrecta'] ?? null,
            'explicacion' => $answer['explicacion'] ?? null,
        ];
    }

    private function preguntasDelIntento(Cuestionario $cuestionario, IntentoCuestionario $attempt): array
    {
        $questions = $this->evaluador->preguntasPublicas($cuestionario->preguntas ?? []);
        $orders = (array) ($attempt->opcionesOrden ?? []);

        foreach ($questions as &$question) {
            $order = $orders[$question['id']] ?? range(0, count($question['opciones']) - 1);
            $question['opciones'] = array_values(array_map(
                fn (int $index) => $question['opciones'][$index],
                $order,
            ));
        }
        unset($question);

        return $questions;
    }

    private function respuestaFinal(IntentoCuestionario $attempt, string $questionId, array $answer): array
    {
        return [
            'intentoId' => (string) $attempt->getKey(),
            'cuestionarioSlug' => (string) $attempt->cuestionarioSlug,
            'preguntaId' => $questionId,
            'retroalimentacion' => $this->respuestaGuardada($attempt, $questionId, $answer),
            'finalizado' => $attempt->estado !== 'en_progreso',
            'estado' => $attempt->estado,
            'puntuacion' => $attempt->puntuacion === null ? null : (float) $attempt->puntuacion,
            'preguntasCorrectas' => (int) ($attempt->preguntasCorrectas ?? 0),
            'totalPreguntas' => (int) $attempt->totalPreguntas,
        ];
    }

    private function finalizarIntento(Cuestionario $cuestionario, IntentoCuestionario $attempt): IntentoCuestionario
    {
        $answers = $this->respuestasDelIntento($attempt);
        $total = count($cuestionario->preguntas ?? []);
        if ($attempt->estado !== 'en_progreso' || $total === 0 || count($answers) !== $total) {
            return $attempt;
        }

        $correctas = collect($answers)->filter(fn (array $answer) => (bool) ($answer['correcta'] ?? false))->count();
        $puntaje = $this->evaluador->puntaje($correctas, $total);
        $estado = $this->evaluador->aprobado($puntaje, (float) $cuestionario->minimoAprobacion)
            ? 'aprobado'
            : 'no_aprobado';
        IntentoCuestionario::where('_id', $this->mongoId($attempt->getKey()))
            ->where('estado', 'en_progreso')
            ->update([
                'estado' => $estado,
                'preguntasCorrectas' => $correctas,
                'puntuacion' => $puntaje,
                'fechaUltimoIntento' => now(),
                'fechaAprobacion' => $estado === 'aprobado' ? now() : null,
            ]);

        return IntentoCuestionario::find($attempt->getKey()) ?? $attempt;
    }

    private function motivoBloqueo(Cuestionario $cuestionario, User $usuario, array $eligibleActivities, bool $passed): ?string
    {
        if ($passed) {
            return null;
        }
        if (($cuestionario->requiereOrganizadorVerificado ?? false) && ! $usuario->organizadorVerificado) {
            return 'Requiere una cuenta de organizador verificada.';
        }
        if ($cuestionario->slug === 'restauracion-entorno' && $eligibleActivities === []) {
            return 'Se habilita después de una asistencia confirmada en una actividad finalizada.';
        }

        return null;
    }

    private function mongoId(mixed $id): mixed
    {
        $objectIdClass = 'MongoDB\\BSON\\ObjectId';

        return is_string($id) && preg_match('/^[a-f0-9]{24}$/i', $id)
            ? new $objectIdClass($id)
            : $id;
    }

    private function respuestasDelIntento(IntentoCuestionario $attempt): array
    {
        return RespuestaCuestionario::query()
            ->where('intentoId', (string) $attempt->getKey())
            ->where('usuarioId', (string) $attempt->usuarioId)
            ->get()
            ->mapWithKeys(fn ($answer) => [(string) $answer->preguntaId => [
                'preguntaId' => (string) $answer->preguntaId,
                'opcion' => (int) $answer->opcion,
                'correcta' => (bool) $answer->correcta,
                'respuestaCorrecta' => (string) $answer->respuestaCorrecta,
                'explicacion' => (string) $answer->explicacion,
                'respondidaEn' => $answer->respondidaEn?->toISOString(),
            ]])
            ->all();
    }
}