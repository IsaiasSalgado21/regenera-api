<?php

namespace App\Services;

use App\Models\Asistencia;
use App\Models\Cuestionario;
use App\Models\EventoGamificacion;
use App\Models\IntentoCuestionario;
use App\Models\Inscripcion;
use App\Models\User;
use App\Models\Voluntariado;
use Illuminate\Support\Facades\DB;
use DateTimeImmutable;
use DateTimeInterface;
use Throwable;

class GamificacionService
{
    public function __construct(
        private readonly CuestionarioService $cuestionarios,
        private readonly VoluntariadoWorkflow $workflow,
        private readonly VoluntariadoHorario $horario,
    ) {}

    public function registrarAprobacion(User $usuario, Cuestionario $cuestionario, IntentoCuestionario $intento): void
    {
        if ($intento->estado !== 'aprobado') {
            return;
        }

        $userId = (string) $usuario->getAuthIdentifier();
        $activityId = $cuestionario->slug === 'restauracion-entorno'
            ? (string) $intento->actividadId
            : '';
        $scope = $activityId === '' ? '' : ':'.$activityId;
        $this->registrarEvento(
            $userId,
            'puntos',
            (int) $cuestionario->puntosAprobacion,
            'Cuestionario aprobado: '.$cuestionario->titulo,
            'cuestionario',
            (string) $cuestionario->slug,
            'quiz:'.$userId.':'.$cuestionario->slug.$scope,
        );

        $medal = $cuestionario->medalla ?? [];
        if (! empty($medal['slug'])) {
            $this->registrarEvento(
                $userId,
                'medalla',
                0,
                'Medalla obtenida: '.($medal['nombre'] ?? $medal['slug']),
                'medalla',
                (string) $medal['slug'],
                'medalla:'.$userId.':'.$medal['slug'],
            );
        }

        if ($cuestionario->slug === 'organizacion-responsable') {
            $this->registrarEvento(
                $userId,
                'certificacion',
                0,
                'Certificación de organizador aprobada',
                'cuestionario',
                (string) $intento->getKey(),
                'certificacion-organizador:'.$userId,
            );
        }
    }

    public function requiereCuestionarioSeguridad(User $usuario): bool
    {
        $userId = (string) $usuario->getAuthIdentifier();
        $hasHistory = Inscripcion::query()
            ->where('usuarioId', $userId)
            ->where('estado', '!=', 'reservando')
            ->exists();
        $approved = IntentoCuestionario::query()
            ->where('usuarioId', $userId)
            ->where('cuestionarioSlug', 'seguridad-limpieza')
            ->where('estado', 'aprobado')
            ->exists();

        return self::debeAprobarSeguridad($usuario->rol, $hasHistory, $approved);
    }

    public static function debeAprobarSeguridad(string $role, bool $hasHistory, bool $approved): bool
    {
        return $role === 'usuario' && ! $hasHistory && ! $approved;
    }

    public function requiereCertificacionPrimeraPublicacion(User $usuario): bool
    {
        if ($usuario->rol !== 'organizador' || ! $usuario->organizadorVerificado) {
            return false;
        }

        $organizerId = (string) $usuario->getAuthIdentifier();
        $objectIdClass = 'MongoDB\\BSON\\ObjectId';
        $organizerStoredId = preg_match('/^[a-f0-9]{24}$/i', $organizerId)
            ? new $objectIdClass($organizerId)
            : $organizerId;
        $hasPublished = Voluntariado::query()
            ->where(function ($query) use ($organizerId, $organizerStoredId): void {
                $query->where('organizadorId', $organizerId)
                    ->orWhere('organizadorId', $organizerStoredId);
            })
            ->whereIn('estado', ['publicado', 'finalizado'])
            ->exists();
        $approved = IntentoCuestionario::query()
            ->where('usuarioId', $organizerId)
            ->where('cuestionarioSlug', 'organizacion-responsable')
            ->where('estado', 'aprobado')
            ->exists();

        return self::debeCertificarPrimeraPublicacion($usuario->rol, (bool) $usuario->organizadorVerificado, $hasPublished, $approved);
    }

    public static function debeCertificarPrimeraPublicacion(string $role, bool $verified, bool $hasPublished, bool $approved): bool
    {
        return $role === 'organizador' && $verified && ! $hasPublished && ! $approved;
    }

    public function registrarParticipacion(User $usuario, Voluntariado $voluntariado): bool
    {
        $userId = (string) $usuario->getAuthIdentifier();
        $volunteerId = (string) $voluntariado->_id;
        $enrolled = Inscripcion::query()
            ->where('usuarioId', $userId)
            ->where('voluntariadoId', $volunteerId)
            ->whereIn('estado', ['inscrita', 'activa', 'completada'])
            ->exists();
        $attended = Asistencia::query()
            ->where('usuarioId', $userId)
            ->where('voluntariadoId', $volunteerId)
            ->where('confirmada', true)
            ->exists();
        if (! $enrolled || ! $attended) {
            return false;
        }

        return $this->registrarEvento(
            $userId,
            'puntos',
            (int) config('gamificacion.puntos.asistencia_confirmada', 50),
            'Asistencia confirmada: '.$voluntariado->titulo,
            'voluntariado',
            $volunteerId,
            'asistencia:'.$userId.':'.$volunteerId,
        );
    }

    public function sincronizarAsistencias(User $usuario): int
    {
        $inscripciones = Inscripcion::query()
            ->where('usuarioId', (string) $usuario->getAuthIdentifier())
            ->whereIn('estado', ['inscrita', 'activa', 'completada'])
            ->get(['voluntariadoId'])
            ->pluck('voluntariadoId')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->all();
        if (! $inscripciones) {
            return 0;
        }

        $attendances = Asistencia::query()
            ->where('usuarioId', (string) $usuario->getAuthIdentifier())
            ->where('confirmada', true)
            ->whereIn('voluntariadoId', $inscripciones)
            ->get();
        $created = 0;

        foreach ($attendances as $attendance) {
            $voluntariadoId = (string) $attendance->voluntariadoId;
            $voluntariado = Voluntariado::find($voluntariadoId);
            if (! $voluntariado) {
                continue;
            }
            $created += (int) $this->registrarParticipacion($usuario, $voluntariado);
        }

        return $created;
    }

    public function progreso(User $usuario): array
    {
        $this->sincronizarAsistencias($usuario);
        $userId = (string) $usuario->getAuthIdentifier();
        $this->repararFechasLegadas($userId);
        $events = EventoGamificacion::query()
            ->where('usuarioId', $userId)
            ->orderByDesc('created_at')
            ->get();
        $points = $events->where('tipo', 'puntos');
        $medalEvents = $events->where('tipo', 'medalla')->keyBy('referenciaId');
        $attempts = IntentoCuestionario::query()
            ->where('usuarioId', $userId)
            ->orderByDesc('created_at')
            ->get();
        $catalog = Cuestionario::query()->where('activo', true)->get();
        $quizProgress = collect($this->cuestionarios->catalogo($usuario));
        $medals = $catalog
            ->filter(fn ($quiz) => ! empty($quiz->medalla['slug']))
            ->map(function ($quiz) use ($medalEvents): array {
                $medal = $quiz->medalla;
                $event = $medalEvents->get((string) $medal['slug']);

                return [
                    'slug' => (string) $medal['slug'],
                    'nombre' => (string) $medal['nombre'],
                    'descripcion' => (string) $medal['descripcion'],
                    'icono' => (string) ($medal['icono'] ?? 'award'),
                    'requisito' => 'Aprobar: '.$quiz->titulo,
                    'desbloqueada' => $event !== null,
                    'fechaObtencion' => $event?->creadoEn?->toISOString(),
                ];
            })
            ->values();
        $approvedAttempts = $attempts->filter(fn ($attempt) => $attempt->estado === 'aprobado');
        $pointsTotal = (int) $points->sum('cantidad');
        $organizerAttempt = $approvedAttempts->firstWhere('cuestionarioSlug', 'organizacion-responsable');
        $educationAttempts = $approvedAttempts->where('cuestionarioSlug', 'restauracion-entorno');
        $circularEconomyAttempt = $approvedAttempts->firstWhere('cuestionarioSlug', 'economia-circular');
        $history = $events->map(fn ($event) => [
            'tipo' => $event->tipo,
            'motivo' => $event->motivo,
            'cantidad' => (int) $event->cantidad,
            'fecha' => $event->creadoEn?->toISOString() ?? $event->created_at?->toISOString(),
            'referenciaTipo' => $event->referenciaTipo,
            'referenciaId' => $event->referenciaId,
        ])->all();

        foreach ($approvedAttempts as $attempt) {
            $history[] = [
                'tipo' => 'cuestionario',
                'motivo' => 'Cuestionario aprobado: '.$attempt->cuestionarioSlug,
                'cantidad' => (float) $attempt->puntuacion,
                'fecha' => $attempt->fechaAprobacion?->toISOString(),
                'referenciaTipo' => 'cuestionario',
                'referenciaId' => $attempt->cuestionarioSlug,
            ];
        }
        usort($history, fn (array $first, array $second) => strcmp((string) ($second['fecha'] ?? ''), (string) ($first['fecha'] ?? '')));

        return [
            'puntos' => $pointsTotal,
            'nivel' => 1 + intdiv($pointsTotal, max(1, (int) config('gamificacion.nivel_por_puntos', 100))),
            'medallas' => $medals->all(),
            'cuestionarios' => $quizProgress->all(),
            'cuestionariosAsignados' => $quizProgress->count(),
            'historialCuestionarios' => $attempts->map(fn ($attempt) => [
                'cuestionarioSlug' => (string) $attempt->cuestionarioSlug,
                'actividadId' => $attempt->actividadId ? (string) $attempt->actividadId : null,
                'estado' => (string) $attempt->estado,
                'puntuacion' => $attempt->puntuacion === null ? null : (float) $attempt->puntuacion,
                'preguntasCorrectas' => (int) ($attempt->preguntasCorrectas ?? 0),
                'totalPreguntas' => (int) ($attempt->totalPreguntas ?? 0),
                'fechaUltimoIntento' => $attempt->fechaUltimoIntento?->toISOString(),
                'fechaAprobacion' => $attempt->fechaAprobacion?->toISOString(),
            ])->values()->all(),
            'cuestionariosAprobados' => $approvedAttempts->pluck('cuestionarioSlug')->unique()->count(),
            'seguridadValidada' => $approvedAttempts->contains(fn ($attempt) => $attempt->cuestionarioSlug === 'seguridad-limpieza'),
            'ecosistemasBCSAprendidos' => $educationAttempts->pluck('actividadId')->filter()->unique()->count(),
            'conocimientoEconomiaCircular' => $circularEconomyAttempt?->puntuacion === null
                ? null
                : (float) $circularEconomyAttempt->puntuacion,
            'horasParticipacion' => $this->horasAsistidas($usuario),
            'actividadesRealizadas' => $this->actividadesRealizadas($usuario),
            'certificacionOrganizador' => $usuario->rol === 'organizador' ? [
                'certificado' => $organizerAttempt !== null,
                'fechaAprobacion' => $organizerAttempt?->fechaAprobacion?->toISOString(),
            ] : null,
            'historial' => $history,
            'historialPuntos' => $points->map(fn ($event) => [
                'cantidad' => (int) $event->cantidad,
                'motivo' => (string) $event->motivo,
                'fecha' => $event->creadoEn?->toISOString() ?? $event->created_at?->toISOString(),
                'referenciaTipo' => $event->referenciaTipo,
                'referenciaId' => $event->referenciaId,
            ])->values()->all(),
        ];
    }

    private function actividadesRealizadas(User $usuario): int
    {
        return count($this->actividadesConAsistenciaValida($usuario));
    }

    private function horasAsistidas(User $usuario): float
    {
        $hours = 0.0;

        foreach ($this->actividadesConAsistenciaValida($usuario) as $voluntariado) {
            $start = $this->horario->inicio($voluntariado);
            $end = $this->horario->fin($voluntariado);
            if ($start && $end && $end->gt($start)) {
                $hours += $start->diffInSeconds($end) / 3600;
            }
        }

        return round($hours, 2);
    }

    private function actividadesConAsistenciaValida(User $usuario): array
    {
        $userId = (string) $usuario->getAuthIdentifier();
        $activeIds = Inscripcion::query()
            ->where('usuarioId', $userId)
            ->whereIn('estado', ['inscrita', 'activa', 'completada'])
            ->get(['voluntariadoId'])
            ->pluck('voluntariadoId')
            ->map(fn ($id) => (string) $id)
            ->unique()
            ->all();
        if (! $activeIds) {
            return [];
        }

        $attendanceIds = Asistencia::query()
            ->where('usuarioId', $userId)
            ->where('confirmada', true)
            ->whereIn('voluntariadoId', $activeIds)
            ->get(['voluntariadoId'])
            ->pluck('voluntariadoId')
            ->map(fn ($id) => (string) $id)
            ->unique();

        return $attendanceIds
            ->map(fn (string $id) => Voluntariado::find($id))
            ->filter(fn ($voluntariado) => $voluntariado
                && $this->workflow->finalizado($voluntariado, $this->horario))
            ->values()
            ->all();
    }

    private function registrarEvento(
        string $userId,
        string $type,
        int $amount,
        string $reason,
        string $referenceType,
        string $referenceId,
        string $uniqueKey,
    ): bool {
        try {
            $utcDateTimeClass = 'MongoDB\\BSON\\UTCDateTime';
            $now = new $utcDateTimeClass(now());
            $result = DB::connection('mongodb')->getDatabase()
                ->selectCollection('eventos_gamificacion')
                ->updateOne(
                    ['claveUnica' => $uniqueKey],
                    ['$setOnInsert' => [
                        'usuarioId' => $userId,
                        'tipo' => $type,
                        'cantidad' => $amount,
                        'motivo' => $reason,
                        'referenciaTipo' => $referenceType,
                        'referenciaId' => $referenceId,
                        'claveUnica' => $uniqueKey,
                        'creadoEn' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]],
                    ['upsert' => true],
                );

            return $result->getUpsertedCount() === 1;
        } catch (Throwable $exception) {
            if (EventoGamificacion::query()->where('claveUnica', $uniqueKey)->exists()) {
                return false;
            }

            throw $exception;
        }
    }

    private function repararFechasLegadas(string $userId): void
    {
        $collection = DB::connection('mongodb')->getDatabase()->selectCollection('eventos_gamificacion');
        $utcDateTimeClass = 'MongoDB\\BSON\\UTCDateTime';

        foreach ($collection->find(['usuarioId' => $userId]) as $event) {
            $createdAt = $this->utcDateTime($event['creadoEn'] ?? null)
                ?? $this->utcDateTime($event['created_at'] ?? null);
            $updatedAt = $this->utcDateTime($event['updated_at'] ?? null) ?? $createdAt;
            if ($createdAt && $updatedAt && $this->utcDateTime($event['creadoEn'] ?? null) && $this->utcDateTime($event['created_at'] ?? null)) {
                continue;
            }

            $eventId = $event['_id'] ?? null;
            $timestamp = is_object($eventId) && method_exists($eventId, 'getTimestamp')
                ? (int) $eventId->getTimestamp()
                : now()->timestamp;
            $createdAt ??= new $utcDateTimeClass($timestamp * 1000);
            $updatedAt ??= $createdAt;
            $collection->updateOne(
                ['_id' => $eventId],
                ['$set' => [
                    'creadoEn' => $createdAt,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ]],
            );
        }
    }

    private function utcDateTime(mixed $value): mixed
    {
        $utcDateTimeClass = 'MongoDB\\BSON\\UTCDateTime';
        if (is_object($value) && get_class($value) === $utcDateTimeClass) {
            return $value;
        }
        if ($value instanceof DateTimeInterface) {
            return new $utcDateTimeClass($value);
        }
        if (is_string($value) && trim($value) !== '') {
            try {
                return new $utcDateTimeClass(new DateTimeImmutable($value));
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}