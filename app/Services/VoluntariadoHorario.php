<?php

namespace App\Services;

use App\Models\Voluntariado;
use Carbon\Carbon;

class VoluntariadoHorario
{
    private const TIMEZONE = 'America/Mazatlan';

    public function inicio(Voluntariado $voluntariado): ?Carbon
    {
        if (! $voluntariado->fechaInicio) {
            return null;
        }

        return $this->enHoraLocal(
            $voluntariado->fechaInicio,
            $voluntariado->horaInicio ?? '00:00:00',
        );
    }

    public function fin(Voluntariado $voluntariado): ?Carbon
    {
        $fechaFin = $voluntariado->fechaFin ?? $voluntariado->fechaInicio;
        if (! $fechaFin) {
            return null;
        }

        if ($voluntariado->horaFin) {
            return $this->enHoraLocal($fechaFin, $voluntariado->horaFin);
        }

        return $this->enHoraLocal($fechaFin, '00:00:00')->endOfDay();
    }

    private function enHoraLocal(Carbon $fecha, string $hora): Carbon
    {
        return Carbon::parse(
            $fecha->format('Y-m-d').' '.$hora,
            self::TIMEZONE,
        );
    }
}
