<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Asistencia;
use App\Models\Inscripcion;
use App\Models\Voluntariado;
use App\Services\VoluntariadoWorkflow;
use App\Services\VoluntariadoHorario;
use Tests\TestCase;

class VoluntariadoWorkflowTest extends TestCase
{
    public function test_creation_state_uses_role_and_verified_organizer_flag(): void
    {
        $workflow = new VoluntariadoWorkflow;

        $this->assertSame('pendiente', $workflow->estadoInicial(new User(['rol' => 'usuario'])));
        $this->assertSame('pendiente', $workflow->estadoInicial(new User([
            'rol' => 'organizador',
            'organizadorVerificado' => false,
        ])));
        $this->assertSame('publicado', $workflow->estadoInicial(new User([
            'rol' => 'organizador',
            'organizadorVerificado' => true,
        ])));
        $this->assertSame('publicado', $workflow->estadoInicial(new User(['rol' => 'admin'])));
        $this->assertSame('pendiente', $workflow->estadoInicial(new User([
            'rol' => 'usuario',
            'organizadorVerificado' => true,
        ])));
    }

    public function test_verified_organizer_keeps_published_event_and_admin_can_force_review(): void
    {
        $workflow = new VoluntariadoWorkflow;
        $organizadorVerificado = new User([
            'rol' => 'organizador',
            'organizadorVerificado' => true,
        ]);
        $voluntariado = new Voluntariado(['estado' => 'publicado']);

        $this->assertSame('publicado', $workflow->estadoTrasEdicion($organizadorVerificado, $voluntariado));
        $this->assertSame('pendiente', $workflow->estadoTrasEdicion(
            $organizadorVerificado,
            new Voluntariado(['estado' => 'pendiente']),
            'publicado',
        ));
        $this->assertSame('pendiente', $workflow->estadoTrasEdicion(
            new User(['rol' => 'admin']),
            $voluntariado,
            'pendiente',
        ));
        $this->assertSame('pendiente', $workflow->estadoTrasEdicion(
            $organizadorVerificado,
            new Voluntariado(['estado' => 'rechazado']),
            'publicado',
        ));
    }

    public function test_absence_is_derived_only_after_a_non_cancelled_event_ends(): void
    {
        $workflow = new VoluntariadoWorkflow;
        $horario = new VoluntariadoHorario;
        $inscripcion = new Inscripcion(['estado' => 'inscrita']);
        $eventoPasado = new Voluntariado([
            'estado' => 'publicado',
            'fechaInicio' => '2020-06-01',
            'fechaFin' => '2020-06-01',
            'horaFin' => '12:00',
        ]);

        $this->assertTrue($workflow->esFalta($eventoPasado, $inscripcion, null, $horario));
        $this->assertFalse($workflow->esFalta(
            $eventoPasado,
            $inscripcion,
            new Asistencia(['confirmada' => true]),
            $horario,
        ));
        $this->assertFalse($workflow->esFalta(
            new Voluntariado([...$eventoPasado->getAttributes(), 'estado' => 'cancelado']),
            $inscripcion,
            null,
            $horario,
        ));
        $this->assertFalse($workflow->esFalta(
            $eventoPasado,
            new Inscripcion(['estado' => 'cancelada']),
            null,
            $horario,
        ));
    }
}
