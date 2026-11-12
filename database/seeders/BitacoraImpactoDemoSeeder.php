<?php

namespace Database\Seeders;

use App\Models\Asistencia;
use App\Models\ImpactoVoluntariado;
use App\Models\Inscripcion;
use App\Models\Lugar;
use App\Models\User;
use App\Models\Voluntariado;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BitacoraImpactoDemoSeeder extends Seeder
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

        $users = collect(range(1, 20))->map(fn (int $number) => User::updateOrCreate(
            ['correo' => sprintf('bitacora.voluntario%02d@regenerabcs.com', $number)],
            [
                'nombre' => sprintf('Participante Bitácora %02d', $number),
                'password' => Hash::make(self::PASSWORD),
                'foto' => null,
                'rol' => 'usuario',
                'organizadorVerificado' => false,
                'fechaRegistro' => now(),
                'estado' => 'activo',
            ],
        ));

        $places = collect([
            ['nombre' => 'Playa Balandra', 'municipio' => 'La Paz', 'direccion' => 'Playa Balandra, La Paz, Baja California Sur', 'tipo' => 'playa', 'latitud' => 24.3217, 'longitud' => -110.3248],
            ['nombre' => 'Malecón de La Paz', 'municipio' => 'La Paz', 'direccion' => 'Malecón, La Paz, Baja California Sur', 'tipo' => 'zona_urbana', 'latitud' => 24.1615, 'longitud' => -110.3158],
            ['nombre' => 'Parque Revolución', 'municipio' => 'La Paz', 'direccion' => 'Zona centro, La Paz, Baja California Sur', 'tipo' => 'parque', 'latitud' => 24.1435, 'longitud' => -110.3120],
        ])->mapWithKeys(fn (array $attributes) => [
            $attributes['nombre'] => Lugar::firstOrCreate(
                ['nombre' => $attributes['nombre']],
                [
                    ...$attributes,
                    'descripcion' => 'Ubicación para las actividades de muestra de la bitácora de impacto.',
                    'estado' => 'activo',
                    'creadoPor' => (string) $organizador->_id,
                ],
            ),
        ]);

        $activities = [
            ['title' => 'Limpieza costera DEMO 01', 'category' => 'limpieza-de-playas', 'place' => 'Playa Balandra', 'hours' => 3, 'enrolled' => 13, 'attended' => 9, 'metrics' => [['residuos_recolectados', 86.4, 'kg'], ['residuos_reciclados', 21.2, 'kg'], ['costa_limpiada', 1.8, 'km']]],
            ['title' => 'Reforestación DEMO 02', 'category' => 'reforestacion', 'place' => 'Parque Revolución', 'hours' => 4, 'enrolled' => 15, 'attended' => 12, 'metrics' => [['plantas_sembradas', 74, 'plantas'], ['superficie_reforestada', 620, 'm²']]],
            ['title' => 'Restauración de hábitat DEMO 03', 'category' => 'restauracion-ambiental', 'place' => 'Playa Balandra', 'hours' => 3, 'enrolled' => 12, 'attended' => 8, 'metrics' => [['superficie_restaurada', 430, 'm²'], ['especies_restauradas', 3, 'especies']]],
            ['title' => 'Limpieza urbana DEMO 04', 'category' => 'limpieza-urbana', 'place' => 'Malecón de La Paz', 'hours' => 2, 'enrolled' => 11, 'attended' => 7, 'metrics' => [['areas_intervenidas', 4, 'áreas'], ['espacios_recuperados', 2, 'espacios']]],
            ['title' => 'Limpieza costera DEMO 05', 'category' => 'limpieza-de-playas', 'place' => 'Playa Balandra', 'hours' => 3, 'enrolled' => 16, 'attended' => 11, 'metrics' => [['residuos_recolectados', 103.7, 'kg'], ['residuos_reciclados', 34.6, 'kg'], ['costa_limpiada', 2.4, 'km']]],
            ['title' => 'Conservación comunitaria DEMO 06', 'category' => 'conservacion', 'place' => 'Parque Revolución', 'hours' => 2, 'enrolled' => 10, 'attended' => 6, 'metrics' => [['especies_protegidas', 5, 'especies'], ['superficie_restaurada', 280, 'm²']]],
            ['title' => 'Reforestación DEMO 07', 'category' => 'reforestacion', 'place' => 'Parque Revolución', 'hours' => 4, 'enrolled' => 18, 'attended' => 14, 'metrics' => [['plantas_sembradas', 96, 'plantas'], ['superficie_reforestada', 810, 'm²']]],
            ['title' => 'Limpieza urbana DEMO 08', 'category' => 'limpieza-urbana', 'place' => 'Malecón de La Paz', 'hours' => 2, 'enrolled' => 12, 'attended' => 8, 'metrics' => [['areas_intervenidas', 3, 'áreas'], ['espacios_recuperados', 1, 'espacios']]],
            ['title' => 'Limpieza costera DEMO 09', 'category' => 'limpieza-de-playas', 'place' => 'Playa Balandra', 'hours' => 3, 'enrolled' => 14, 'attended' => 10, 'metrics' => [['residuos_recolectados', 67.9, 'kg'], ['residuos_reciclados', 18.3, 'kg'], ['costa_limpiada', 1.3, 'km']]],
            ['title' => 'Restauración de hábitat DEMO 10', 'category' => 'restauracion-ambiental', 'place' => 'Parque Revolución', 'hours' => 3, 'enrolled' => 13, 'attended' => 9, 'metrics' => [['superficie_restaurada', 510, 'm²'], ['especies_restauradas', 4, 'especies']]],
        ];

        foreach ($activities as $index => $activity) {
            $date = now(self::TIMEZONE)
                ->subMonths(9 - $index)
                ->startOfMonth()
                ->addDays(3 + ($index % 5));
            $voluntariado = Voluntariado::updateOrCreate(
                ['titulo' => $activity['title']],
                [
                    'descripcion' => 'Actividad de muestra identificada por DEMO, creada para poblar la bitácora con registros persistidos.',
                    'categoria' => $activity['category'],
                    'lugarId' => (string) $places->get($activity['place'])->_id,
                    'organizadorId' => (string) $organizador->_id,
                    'latitud' => $places->get($activity['place'])->latitud,
                    'longitud' => $places->get($activity['place'])->longitud,
                    'fechaInicio' => Carbon::parse($date->format('Y-m-d'), 'UTC'),
                    'fechaFin' => Carbon::parse($date->format('Y-m-d'), 'UTC'),
                    'horaInicio' => '08:00',
                    'horaFin' => Carbon::parse('08:00')->addHours($activity['hours'])->format('H:i'),
                    'cupo' => max(20, $activity['enrolled']),
                    'requisitos' => ['Participación en una actividad ambiental comunitaria.'],
                    'materiales' => ['Agua', 'Guantes reutilizables'],
                    'imagen' => null,
                    'estado' => 'finalizado',
                    'codigoAsistenciaActivo' => false,
                ],
            );

            $eventId = (string) $voluntariado->_id;
            $registrationIds = [];
            for ($offset = 0; $offset < $activity['enrolled']; $offset++) {
                $participant = $users[($index * 3 + $offset) % $users->count()];
                $userId = (string) $participant->_id;
                $inscripcion = Inscripcion::updateOrCreate(
                    ['usuarioId' => $userId, 'voluntariadoId' => $eventId],
                    [
                        'claveRegistroActiva' => $userId.'|'.$eventId,
                        'fechaInscripcion' => $date->copy()->subDays(14 + ($offset % 8)),
                        'estado' => 'inscrita',
                        'observaciones' => 'Inscripción de muestra DEMO para bitácora de impacto.',
                    ],
                );
                $registrationIds[] = (string) $inscripcion->getKey();

                if ($offset < $activity['attended']) {
                    $attendanceAt = Carbon::parse($date->format('Y-m-d').' 08:00', self::TIMEZONE)
                        ->addMinutes(10 * ($offset % 12));
                    Asistencia::updateOrCreate(
                        ['usuarioId' => $userId, 'voluntariadoId' => $eventId],
                        [
                            'fecha' => $attendanceAt,
                            'hora' => $attendanceAt->format('H:i:s'),
                            'metodo' => $offset % 2 === 0 ? 'QR' : 'manual',
                            'confirmada' => true,
                        ],
                    );
                }
            }

            $voluntariado->forceFill([
                'inscritosActivos' => count($registrationIds),
                'reservasCupo' => $registrationIds,
            ])->save();

            foreach ($activity['metrics'] as [$type, $amount, $unit]) {
                ImpactoVoluntariado::updateOrCreate(
                    ['voluntariadoId' => $eventId, 'tipo' => $type],
                    [
                        'etiquetaPersonalizada' => null,
                        'unidadPersonalizada' => null,
                        'cantidad' => $amount,
                        'unidad' => $unit,
                        'nota' => 'Resultado de muestra DEMO registrado para visualizar la bitácora.',
                        'especies' => [],
                        'imagenes' => [],
                        'registradoPor' => (string) $organizador->_id,
                    ],
                );
            }
        }

        $this->command?->info('Bitácora DEMO: 20 usuarios y 10 actividades finalizadas con inscripciones, asistencias e impactos persistidos.');
        $this->command?->info('Acceso a cuentas DEMO: bitacora.voluntario01@regenerabcs.com a bitacora.voluntario20@regenerabcs.com / '.self::PASSWORD);
    }
}