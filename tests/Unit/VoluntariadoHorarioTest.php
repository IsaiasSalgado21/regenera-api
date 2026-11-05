<?php

namespace Tests\Unit;

use App\Models\Voluntariado;
use App\Services\VoluntariadoHorario;
use PHPUnit\Framework\TestCase;

class VoluntariadoHorarioTest extends TestCase
{
    public function test_schedule_times_are_interpreted_in_baja_california_sur_local_time(): void
    {
        $voluntariado = new Voluntariado([
            'fechaInicio' => '2026-11-04',
            'horaInicio' => '19:33',
            'fechaFin' => '2026-11-04',
            'horaFin' => '21:33',
        ]);
        $horario = new VoluntariadoHorario;

        self::assertSame(
            '2026-11-04T19:33:00-07:00',
            $horario->inicio($voluntariado)->toIso8601String(),
        );
        self::assertSame(
            '2026-11-04T21:33:00-07:00',
            $horario->fin($voluntariado)->toIso8601String(),
        );
    }
}
