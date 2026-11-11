<?php

namespace App\Services;

use App\Models\User;
use App\Models\Asistencia;
use App\Models\Inscripcion;
use App\Models\Voluntariado;

class VoluntariadoWorkflow
{
    public function estadoInicial(User $user): string
    {
        return $user->rol === 'admin'
            || ($user->rol === 'organizador' && (bool) $user->organizadorVerificado)
            ? 'publicado'
            : 'pendiente';
    }

    public function estadoTrasEdicion(User $user, Voluntariado $voluntariado, ?string $estadoSolicitado = null): string
    {
        if ($user->rol === 'admin') {
            return $estadoSolicitado ?? $voluntariado->estado ?? 'pendiente';
        }

        if ($voluntariado->estado === 'rechazado') {
            return 'pendiente';
        }

        return $voluntariado->estado ?? 'pendiente';
    }

    public function finalizado(Voluntariado $voluntariado, VoluntariadoHorario $horario): bool
    {
        if ($voluntariado->estado === 'finalizado') {
            return true;
        }

        if ($voluntariado->estado !== 'publicado') {
            return false;
        }

        return $horario->fin($voluntariado)?->isPast() ?? false;
    }

    public function esFalta(
        Voluntariado $voluntariado,
        Inscripcion $inscripcion,
        ?Asistencia $asistencia,
        VoluntariadoHorario $horario,
    ): bool {
        if (! in_array($inscripcion->estado, ['inscrita', 'activa'], true)) {
            return false;
        }

        if ($asistencia?->confirmada) {
            return false;
        }

        return $this->finalizado($voluntariado, $horario);
    }
}
