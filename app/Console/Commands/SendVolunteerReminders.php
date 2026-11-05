<?php

namespace App\Console\Commands;

use App\Models\Inscripcion;
use App\Models\Notificacion;
use App\Models\User;
use App\Models\Voluntariado;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use MongoDB\BSON\ObjectId;

class SendVolunteerReminders extends Command
{
    protected $signature = 'notifications:send-volunteer-reminders';

    protected $description = 'Send scheduled reminders for upcoming volunteer activities';

    public function handle(NotificationService $notifications): int
    {
        $now = now();
        $voluntariados = Voluntariado::query()
            ->where('estado', 'publicado')
            ->where('fechaInicio', '>=', $now->copy()->subDay())
            ->get();

        foreach ($voluntariados as $voluntariado) {
            if (! $voluntariado->fechaInicio) {
                continue;
            }

            $startsAt = $voluntariado->fechaInicio->copy();
            if ($voluntariado->horaInicio) {
                $startsAt->setTimeFromTimeString($voluntariado->horaInicio);
            }
            if ($startsAt->lessThanOrEqualTo($now)) {
                continue;
            }

            $reminderTypes = $this->reminderTypes($startsAt, $now);
            if ($reminderTypes === []) {
                continue;
            }

            $voluntariadoId = (string) $voluntariado->_id;
            $inscripciones = Inscripcion::query()
                ->where('voluntariadoId', $voluntariadoId)
                ->where('estado', 'inscrita')
                ->get();
            $destinatarios = $inscripciones
                ->map(fn (Inscripcion $inscripcion) => User::find((string) $inscripcion->usuarioId))
                ->filter()
                ->push(User::find((string) $voluntariado->organizadorId))
                ->filter(fn (User $usuario) => $usuario->rol !== 'admin')
                ->unique(fn (User $usuario) => (string) $usuario->getAuthIdentifier());

            foreach ($destinatarios as $usuario) {
                $frequency = $usuario->recordatorioFrecuencia ?: 'milestones';
                if ($frequency === 'none') {
                    continue;
                }
                $reminderType = $frequency === 'daily'
                    ? (in_array('daily', $reminderTypes, true) ? 'daily' : null)
                    : (in_array('day', $reminderTypes, true) ? 'day' : (in_array('hour', $reminderTypes, true) ? 'hour' : null));
                if ($reminderType === null) {
                    continue;
                }

                $key = $voluntariadoId.':'.$reminderType.':'.$now->toDateString();
                if ($this->alreadySent((string) $usuario->getAuthIdentifier(), $key)) {
                    continue;
                }

                $notifications->recordatorioVoluntariado(
                    (string) $usuario->getAuthIdentifier(),
                    $voluntariado,
                    $key,
                );
            }
        }

        return self::SUCCESS;
    }

    private function reminderTypes($startsAt, $now): array
    {
        if ($startsAt->lessThanOrEqualTo($now->copy()->addMinute())) {
            return [];
        }

        $types = [];
        $minute = $now->copy()->startOfMinute();
        $nextMinute = $minute->copy()->addMinute();
        foreach (['day' => 24 * 60, 'hour' => 60] as $milestone => $minutes) {
            $reminderAt = $startsAt->copy()->subMinutes($minutes);
            if ($reminderAt->greaterThanOrEqualTo($minute) && $reminderAt->lessThan($nextMinute)) {
                $types[] = $milestone;
            }
        }

        $types[] = 'daily';

        return $types;
    }

    private function alreadySent(string $usuarioId, string $key): bool
    {
        $storedId = preg_match('/^[a-f0-9]{24}$/i', $usuarioId)
            ? new ObjectId($usuarioId)
            : $usuarioId;

        return Notificacion::query()
            ->where('usuarioId', $storedId)
            ->where('tipo', 'recordatorio_voluntariado')
            ->where('datos.recordatorioClave', $key)
            ->exists();
    }
}
