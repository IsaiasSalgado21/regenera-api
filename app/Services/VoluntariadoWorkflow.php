<?php

namespace App\Services;

use App\Models\User;
use App\Models\Asistencia;
use App\Models\Inscripcion;
use App\Models\Voluntariado;
use DateTimeInterface;

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
        if (! in_array($inscripcion->estado, ['inscrita', 'activa', 'completada'], true)) {
            return false;
        }

        if ($asistencia?->confirmada) {
            return false;
        }

        return $this->finalizado($voluntariado, $horario);
    }

    public function hayCambiosImportantes(Voluntariado $voluntariado, array $attributes): bool
    {
        $fields = [
            'titulo',
            'descripcion',
            'lugarId',
            'fechaInicio',
            'fechaFin',
            'horaInicio',
            'horaFin',
            'cupo',
            'estado',
        ];

        foreach ($fields as $field) {
            if (! array_key_exists($field, $attributes)) {
                continue;
            }

            $current = $voluntariado->getAttribute($field);
            $next = $attributes[$field];
            if (str_starts_with($field, 'fecha')) {
                $current = $current instanceof DateTimeInterface
                    ? $current->format('Y-m-d')
                    : substr((string) $current, 0, 10);
                $next = $next instanceof DateTimeInterface
                    ? $next->format('Y-m-d')
                    : substr((string) $next, 0, 10);
            }

            if ((string) $current !== (string) $next) {
                return true;
            }
        }

        return false;
    }
}
