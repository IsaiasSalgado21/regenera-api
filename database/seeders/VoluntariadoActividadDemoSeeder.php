<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use App\Models\Inscripcion;
use App\Models\Lugar;
use App\Models\Notificacion;
use App\Models\User;
use App\Models\Voluntariado;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use MongoDB\BSON\ObjectId;

class VoluntariadoActividadDemoSeeder extends Seeder
{
    private const TIMEZONE = 'America/Mazatlan';

    private const PASSWORD = 'Demo12345!';

    public function run(): void
    {
        $organizador = User::firstOrCreate(
            ['correo' => 'organizador@regenerabcs.com'],
            [
                'nombre' => 'Organizador de Prueba',
                'password' => Hash::make('Organizador12345'),
                'rol' => 'organizador',
                'organizadorVerificado' => true,
                'fechaRegistro' => now(),
                'estado' => 'activo',
            ],
        );

        $lugar = Lugar::firstOrCreate(
            ['nombre' => 'Playa Balandra'],
            [
                'descripcion' => 'Lugar para verificar actividades de voluntariado.',
                'municipio' => 'La Paz',
                'direccion' => 'Playa Balandra, La Paz, Baja California Sur',
                'latitud' => 24.3217,
                'longitud' => -110.3248,
                'tipo' => 'playa',
                'estado' => 'activo',
                'creadoPor' => new ObjectId((string) $organizador->_id),
            ],
        );

        $participantes = $this->participantes();
        $ahora = now(self::TIMEZONE);
        $inicioProximo = $ahora->copy()->addMinutes(5);
        $finProximo = $inicioProximo->copy()->addHours(2);
        $inicioHistorico = $ahora->copy()->subDays(7)->setTime(8, 0);
        $finHistorico = $inicioHistorico->copy()->addHours(3);

        $proximo = $this->voluntariado(
            'DEMO: asistencia empieza en cinco minutos',
            'Actividad de prueba para revisar la lista del propietario y registrar asistencia durante el evento.',
            $organizador,
            $lugar,
            $inicioProximo,
            $finProximo,
            12,
            'publicado',
        );

        $historico = $this->voluntariado(
            'DEMO: historial de asistencias, faltas y cancelaciones',
            'Actividad de prueba finalizada con resultados variados para revisar las métricas y solicitudes de asistencia.',
            $organizador,
            $lugar,
            $inicioHistorico,
            $finHistorico,
            20,
            'finalizado',
        );

        $this->seedRegistrations(
            $proximo,
            $participantes,
            array_fill(0, count($participantes), 'inscrita'),
            $ahora->copy()->subDays(2),
        );

        $estadosHistoricos = array_fill(0, count($participantes), 'inscrita');
        $estadosHistoricos[6] = 'cancelada';
        $estadosHistoricos[7] = 'cancelada';
        $inscripcionesHistoricas = $this->seedRegistrations(
            $historico,
            $participantes,
            $estadosHistoricos,
            $inicioHistorico->copy()->subDays(10),
            [0, 1, 2],
        );

        $this->seedPendingReview(
            $organizador,
            $participantes[3],
            $historico,
            $inscripcionesHistoricas[3],
        );

        $this->command?->info('Escenarios de actividad demo creados. La actividad futura empieza aproximadamente en cinco minutos.');
        $this->command?->info('Cuenta propietaria: organizador@regenerabcs.com / Organizador12345');
        $this->command?->info('Cuentas participantes: demo.voluntario01@regenerabcs.com a demo.voluntario08@regenerabcs.com / '.self::PASSWORD);
    }

    private function participantes(): array
    {
        $participantes = [];

        for ($numero = 1; $numero <= 8; $numero++) {
            $correo = sprintf('demo.voluntario%02d@regenerabcs.com', $numero);
            $participantes[] = User::updateOrCreate(
                ['correo' => $correo],
                [
                    'nombre' => 'Voluntario Demo '.$numero,
                    'password' => Hash::make(self::PASSWORD),
                    'foto' => null,
                    'rol' => 'usuario',
                    'organizadorVerificado' => false,
                    'fechaRegistro' => now(),
                    'estado' => 'activo',
                ],
            );
        }

        return $participantes;
    }

    private function voluntariado(
        string $titulo,
        string $descripcion,
        User $organizador,
        Lugar $lugar,
        Carbon $inicio,
        Carbon $fin,
        int $cupo,
        string $estado,
    ): Voluntariado {
        return Voluntariado::updateOrCreate(
            ['titulo' => $titulo],
            [
                'descripcion' => $descripcion,
                'categoria' => 'Conservación',
                'lugarId' => (string) $lugar->_id,
                'organizadorId' => (string) $organizador->_id,
                'latitud' => $lugar->latitud,
                'longitud' => $lugar->longitud,
                'fechaInicio' => Carbon::parse($inicio->format('Y-m-d'), 'UTC'),
                'fechaFin' => Carbon::parse($fin->format('Y-m-d'), 'UTC'),
                'horaInicio' => $inicio->format('H:i'),
                'horaFin' => $fin->format('H:i'),
                'cupo' => $cupo,
                'requisitos' => ['Cuenta demo para validar el flujo de asistencia.'],
                'materiales' => ['Agua', 'Guantes'],
                'imagen' => null,
                'estado' => $estado,
                'codigoAsistenciaActivo' => true,
            ],
        );
    }

    private function seedRegistrations(
        Voluntariado $voluntariado,
        array $participantes,
        array $estados,
        Carbon $fechaBase,
        array $indicesConAsistencia = [],
    ): array {
        $inscripciones = [];
        $participantesActivos = [];
        $voluntariadoId = (string) $voluntariado->_id;

        foreach ($participantes as $indice => $participante) {
            $usuarioId = (string) $participante->_id;
            $estado = $estados[$indice];
            $claveActiva = $estado === 'cancelada' ? null : $usuarioId.'|'.$voluntariadoId;
            $inscripcion = Inscripcion::updateOrCreate(
                [
                    'usuarioId' => $usuarioId,
                    'voluntariadoId' => $voluntariadoId,
                ],
                [
                    'claveRegistroActiva' => $claveActiva,
                    'fechaInscripcion' => $fechaBase->copy()->subDays($indice),
                    'estado' => $estado,
                    'observaciones' => 'Inscripción creada por el seeder de actividad demo.',
                ],
            );
            $inscripciones[$indice] = $inscripcion;

            if ($estado !== 'cancelada') {
                $participantesActivos[] = $inscripcion;
            }

            if (in_array($indice, $indicesConAsistencia, true)) {
                $horaAsistencia = $voluntariado->horaInicio;
                $fechaAsistencia = $voluntariado->fechaInicio;
                $asistenciaHora = Carbon::parse($fechaAsistencia, self::TIMEZONE)
                    ->setTimeFromTimeString($horaAsistencia)
                    ->addMinutes($indice * 10);

                Asistencia::updateOrCreate(
                    [
                        'usuarioId' => $usuarioId,
                        'voluntariadoId' => $voluntariadoId,
                    ],
                    [
                        'fecha' => $asistenciaHora,
                        'hora' => $asistenciaHora->format('H:i:s'),
                        'metodo' => $indice < 2 ? 'QR' : 'manual',
                        'confirmada' => true,
                    ],
                );
            } else {
                Asistencia::query()
                    ->where('usuarioId', $usuarioId)
                    ->where('voluntariadoId', $voluntariadoId)
                    ->delete();
            }
        }

        $voluntariado->forceFill([
            'inscritosActivos' => count($participantesActivos),
            'reservasCupo' => array_map(
                fn (Inscripcion $inscripcion) => (string) $inscripcion->getKey(),
                $participantesActivos,
            ),
        ])->save();

        return $inscripciones;
    }

    private function seedPendingReview(
        User $organizador,
        User $participante,
        Voluntariado $voluntariado,
        Inscripcion $inscripcion,
    ): void {
        $organizadorId = (string) $organizador->_id;
        $storedOrganizadorId = preg_match('/^[a-f0-9]{24}$/i', $organizadorId)
            ? new ObjectId($organizadorId)
            : $organizadorId;
        $participanteId = (string) $participante->_id;
        $voluntariadoId = (string) $voluntariado->_id;
        $inscripcionId = (string) $inscripcion->getKey();

        Notificacion::updateOrCreate(
            [
                'usuarioId' => $storedOrganizadorId,
                'tipo' => 'solicitud_asistencia',
                'entidad.id' => $voluntariadoId,
                'datos.inscripcionId' => $inscripcionId,
            ],
            [
                'titulo' => 'Solicitud para corregir asistencia',
                'mensaje' => $participante->nombre.' solicita que revises su asistencia a «'.$voluntariado->titulo.'».',
                'entidad' => [
                    'entidadtipo' => 'voluntariado',
                    'id' => $voluntariadoId,
                ],
                'prioridad' => 'normal',
                'leida' => false,
                'fechaCreacion' => now(),
                'fechaLectura' => null,
                'fechaExpiracion' => null,
                'datos' => [
                    'estadoSolicitud' => 'pendiente',
                    'inscripcionId' => $inscripcionId,
                    'solicitanteId' => $participanteId,
                    'url' => '/voluntariados/'.$voluntariadoId,
                ],
            ],
        );
    }
}