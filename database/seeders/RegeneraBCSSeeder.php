<?php

namespace Database\Seeders;

use App\Models\Articulo;
use App\Models\Asistencia;
use App\Models\Auditoria;
use App\Models\FloraFauna;
use App\Models\Inscripcion;
use App\Models\Lugar;
use App\Models\Notificacion;
use App\Models\User;
use App\Models\Voluntariado;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use MongoDB\BSON\ObjectId;

class RegeneraBCSSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Usuarios de prueba
        |--------------------------------------------------------------------------
        | Se crean únicamente si el correo todavía no existe.
        | La contraseña de estos usuarios de prueba es: Regenera123!
        */

        $organizador = User::firstOrCreate(
            ['correo' => 'organizador@regenerabcs.com'],
            [
                'nombre' => 'Organizador Regenera BCS',
                'password' => Hash::make('Regenera123!'),
                'foto' => null,
                'rol' => 'organizador',
                'organizadorVerificado' => true,
                'fechaRegistro' => now(),
                'estado' => 'activo',
            ]
        );

        $usuario1 = User::firstOrCreate(
            ['correo' => 'voluntario1@regenerabcs.com'],
            [
                'nombre' => 'María López',
                'password' => Hash::make('Regenera123!'),
                'foto' => null,
                'rol' => 'usuario',
                'organizadorVerificado' => false,
                'fechaRegistro' => now(),
                'estado' => 'activo',
            ]
        );

        $usuario2 = User::firstOrCreate(
            ['correo' => 'voluntario2@regenerabcs.com'],
            [
                'nombre' => 'Carlos García',
                'password' => Hash::make('Regenera123!'),
                'foto' => null,
                'rol' => 'usuario',
                'organizadorVerificado' => false,
                'fechaRegistro' => now(),
                'estado' => 'activo',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 2. Lugares
        |--------------------------------------------------------------------------
        */

        $playaBalandra = Lugar::firstOrCreate(
            ['nombre' => 'Playa Balandra'],
            [
                'descripcion' => 'Área costera para actividades de conservación y limpieza.',
                'municipio' => 'La Paz',
                'direccion' => 'Zona de Playa Balandra, La Paz, Baja California Sur',
                'latitud' => 24.3217,
                'longitud' => -110.3248,
                'tipo' => 'playa',
                'estado' => 'activo',
                'creadoPor' => new ObjectId((string) $organizador->_id),
            ]
        );

        $malecon = Lugar::firstOrCreate(
            ['nombre' => 'Malecón de La Paz'],
            [
                'descripcion' => 'Zona costera urbana para jornadas comunitarias de limpieza.',
                'municipio' => 'La Paz',
                'direccion' => 'Malecón, La Paz, Baja California Sur',
                'latitud' => 24.1615,
                'longitud' => -110.3158,
                'tipo' => 'zona_urbana',
                'estado' => 'activo',
                'creadoPor' => new ObjectId((string) $organizador->_id),
            ]
        );

        $parque = Lugar::firstOrCreate(
            ['nombre' => 'Parque Revolución'],
            [
                'descripcion' => 'Espacio público para actividades de educación ambiental y reforestación.',
                'municipio' => 'La Paz',
                'direccion' => 'Zona centro, La Paz, Baja California Sur',
                'latitud' => 24.1435,
                'longitud' => -110.3120,
                'tipo' => 'parque',
                'estado' => 'activo',
                'creadoPor' => new ObjectId((string) $organizador->_id),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 3. Voluntariados
        |--------------------------------------------------------------------------
        | Las fechas se generan a partir del día en que se ejecuta el seeder.
        */

        $fechaLimpieza = now()->addDays(7)->startOfDay();
        $fechaReforestacion = now()->addDays(14)->startOfDay();
        $fechaEducacion = now()->addDays(21)->startOfDay();

        $limpieza = Voluntariado::firstOrCreate(
            ['titulo' => 'Jornada de limpieza de Playa Balandra'],
            [
                'descripcion' => 'Jornada comunitaria de recolección de residuos y cuidado de la zona costera.',
                'lugarId' => (string) $playaBalandra->_id,
                'organizadorId' => (string) $organizador->_id,
                'fechaInicio' => $fechaLimpieza->copy()->setTime(8, 0),
                'fechaFin' => $fechaLimpieza->copy()->setTime(12, 0),
                'horaInicio' => '08:00',
                'horaFin' => '12:00',
                'cupo' => 30,
                'requisitos' => [
                    'Llevar agua para consumo personal',
                    'Usar ropa cómoda y protección solar',
                    'Seguir las indicaciones del organizador',
                ],
                'materiales' => [
                    'Guantes reutilizables',
                    'Bolsas para residuos',
                    'Pinzas recolectoras',
                ],
                'imagen' => null,
                'estado' => 'publicado',
            ]
        );

        $reforestacion = Voluntariado::firstOrCreate(
            ['titulo' => 'Jornada de reforestación comunitaria'],
            [
                'descripcion' => 'Actividad de plantación y cuidado de vegetación apropiada para la región.',
                'lugarId' => (string) $parque->_id,
                'organizadorId' => (string) $organizador->_id,
                'fechaInicio' => $fechaReforestacion->copy()->setTime(7, 30),
                'fechaFin' => $fechaReforestacion->copy()->setTime(11, 30),
                'horaInicio' => '07:30',
                'horaFin' => '11:30',
                'cupo' => 25,
                'requisitos' => [
                    'Llevar agua',
                    'Usar ropa adecuada para trabajo al aire libre',
                ],
                'materiales' => [
                    'Guantes de jardinería',
                    'Palas',
                    'Plantas adecuadas para la región',
                ],
                'imagen' => null,
                'estado' => 'publicado',
            ]
        );

        $educacion = Voluntariado::firstOrCreate(
            ['titulo' => 'Taller de educación ambiental costera'],
            [
                'descripcion' => 'Actividad educativa sobre residuos, ecosistemas costeros y participación ciudadana.',
                'lugarId' => (string) $malecon->_id,
                'organizadorId' => (string) $organizador->_id,
                'fechaInicio' => $fechaEducacion->copy()->setTime(9, 0),
                'fechaFin' => $fechaEducacion->copy()->setTime(11, 0),
                'horaInicio' => '09:00',
                'horaFin' => '11:00',
                'cupo' => 40,
                'requisitos' => [
                    'Interés en el cuidado del medio ambiente',
                ],
                'materiales' => [
                    'Libreta para notas',
                    'Pluma o lápiz',
                ],
                'imagen' => null,
                'estado' => 'publicado',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 4. Inscripciones
        |--------------------------------------------------------------------------
        | Cada inscripción apunta a un usuario y un voluntariado existentes.
        */

        Inscripcion::firstOrCreate(
            [
                'usuarioId' => (string) $usuario1->_id,
                'voluntariadoId' => (string) $limpieza->_id,
            ],
            [
                'fechaInscripcion' => now(),
                'estado' => 'inscrita',
                'observaciones' => 'Inscripción de prueba para la jornada de limpieza.',
            ]
        );

        Inscripcion::firstOrCreate(
            [
                'usuarioId' => (string) $usuario2->_id,
                'voluntariadoId' => (string) $limpieza->_id,
            ],
            [
                'fechaInscripcion' => now(),
                'estado' => 'inscrita',
                'observaciones' => 'Participación de prueba en la jornada de limpieza.',
            ]
        );

        Inscripcion::firstOrCreate(
            [
                'usuarioId' => (string) $usuario1->_id,
                'voluntariadoId' => (string) $reforestacion->_id,
            ],
            [
                'fechaInscripcion' => now(),
                'estado' => 'inscrita',
                'observaciones' => 'Inscripción de prueba para reforestación.',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 5. Flora y Fauna
        |--------------------------------------------------------------------------
        */

        FloraFauna::firstOrCreate(
            ['nombre' => 'Mangle Rojo'],
            [
                'creadoPor' => new ObjectId((string) $organizador->_id),
                'nombreCientifico' => 'Rhizophora mangle',
                'tipo' => 'flora',
                'descripcion' => 'Árbol halófito crucial para la protección de la línea de costa y esteros de BCS.',
                'habitat' => 'Esteros y zonas intermareales costeras',
                'estadoConservacion' => 'Protección especial',
                'estado' => 'publicado',
                'imagenes' => [],
                'ubicacion' => [
                    'nombre' => 'Ensenada de La Paz',
                    'latitud' => 24.142,
                    'longitud' => -110.315,
                ],
                'lugaresRelacionados' => [new ObjectId((string) $playaBalandra->_id)],
                'fuenteInformacion' => 'CONABIO / Ecosistemas de BCS',
            ]
        );

        FloraFauna::firstOrCreate(
            ['nombre' => 'Tortuga Negra'],
            [
                'creadoPor' => new ObjectId((string) $organizador->_id),
                'nombreCientifico' => 'Chelonia agassizii',
                'tipo' => 'fauna',
                'descripcion' => 'Especie marina emblemática que habita en las bahías y zonas costeras del estado.',
                'habitat' => 'Aguas costeras y bahías protegidas',
                'estadoConservacion' => 'en_peligro',
                'estado' => 'publicado',
                'imagenes' => [],
                'ubicacion' => [
                    'nombre' => 'Bahía de La Paz',
                    'latitud' => 24.200,
                    'longitud' => -110.350,
                ],
                'lugaresRelacionados' => [new ObjectId((string) $playaBalandra->_id)],
                'fuenteInformacion' => 'Programa de Conservación de Tortugas Marinas',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 6. Artículos Ambientales
        |--------------------------------------------------------------------------
        */

        $articulo1 = Articulo::firstOrCreate(
            ['titulo' => 'La importancia de la restauración de manglares en la Bahía de La Paz'],
            [
                'autorId' => (string) $organizador->_id,
                'contenido' => 'Los manglares actúan como barreras naturales contra la erosión y son el hogar de numerosas especies marinas juveniles. Su conservación es vital para el equilibrio ecológico...',
                'imagen' => null,
                'categoria' => 'Conservación',
                'estado' => 'publicado',
                'fechaPublicacion' => now(),
                'fechaActualizacion' => now(),
            ]
        );

        Articulo::firstOrCreate(
            ['titulo' => 'Guía práctica para reducir residuos plásticos en las playas de BCS'],
            [
                'autorId' => (string) $organizador->_id,
                'contenido' => 'Pequeñas acciones diarias, como el uso de termos reutilizables y la participación en limpiezas comunitarias, generan un impacto positivo directo en nuestros ecosistemas costeros...',
                'imagen' => null,
                'categoria' => 'Educación Ambiental',
                'estado' => 'publicado',
                'fechaPublicacion' => now(),
                'fechaActualizacion' => now(),
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 7. Asistencias
        |--------------------------------------------------------------------------
        */

        Asistencia::firstOrCreate(
            [
                'usuarioId' => (string) $usuario1->_id,
                'voluntariadoId' => (string) $limpieza->_id,
            ],
            [
                'fecha' => now(),
                'hora' => '08:15:00',
                'metodo' => 'QR',
                'confirmada' => true,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 8. Notificaciones
        |--------------------------------------------------------------------------
        */

        Notificacion::firstOrCreate(
            [
                'usuarioId' => (string) $usuario1->_id,
                'titulo' => 'Inscripción confirmada',
            ],
            [
                'tipo' => 'inscripcion_confirmada',
                'mensaje' => 'Te has inscrito exitosamente en la Jornada de limpieza de Playa Balandra.',
                'entidad' => [
                    'entidadtipo' => 'voluntariado',
                    'id' => (string) $limpieza->_id,
                ],
                'prioridad' => 'normal',
                'leida' => true,
                'fechaCreacion' => now()->subDays(1),
                'fechaLectura' => now()->subHours(12),
                'fechaExpiracion' => null,
                'datos' => [
                    'voluntariadoTitulo' => $limpieza->titulo,
                ],
            ]
        );

        Notificacion::firstOrCreate(
            [
                'usuarioId' => (string) $usuario1->_id,
                'titulo' => 'Asistencia registrada',
            ],
            [
                'tipo' => 'asistencia_registrada',
                'mensaje' => 'Tu asistencia ha sido confirmada mediante código QR. ¡Gracias por tu participación!',
                'entidad' => [
                    'entidadtipo' => 'voluntariado',
                    'id' => (string) $limpieza->_id,
                ],
                'prioridad' => 'alta',
                'leida' => false,
                'fechaCreacion' => now(),
                'fechaLectura' => null,
                'fechaExpiracion' => null,
                'datos' => [
                    'metodo' => 'QR',
                ],
            ]
        );

        Notificacion::firstOrCreate(
            [
                'usuarioId' => (string) $organizador->_id,
                'titulo' => 'Artículo publicado',
            ],
            [
                'tipo' => 'articulo_aprobado',
                'mensaje' => 'Tu artículo "La importancia de la restauración de manglares" ha sido aprobado y publicado.',
                'entidad' => [
                    'entidadtipo' => 'articulo',
                    'id' => (string) $articulo1->_id,
                ],
                'prioridad' => 'normal',
                'leida' => false,
                'fechaCreacion' => now(),
                'fechaLectura' => null,
                'fechaExpiracion' => null,
                'datos' => [],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | 9. Auditoría
        |--------------------------------------------------------------------------
        */

        Auditoria::firstOrCreate(
            [
                'usuarioId' => (string) $organizador->_id,
                'accion' => 'verificacion_organizador',
            ],
            [
                'entidad' => 'usuarios',
                'entidadId' => (string) $organizador->_id,
                'fecha' => now()->subDays(2),
                'ip' => '127.0.0.1',
                'detalles' => [
                    'motivo' => 'Verificación inicial de cuenta de organizador.',
                    'aprobadoPor' => 'sistema',
                ],
            ]
        );

        Auditoria::firstOrCreate(
            [
                'usuarioId' => (string) $organizador->_id,
                'accion' => 'crear_voluntariado',
            ],
            [
                'entidad' => 'voluntariados',
                'entidadId' => (string) $limpieza->_id,
                'fecha' => now()->subDays(1),
                'ip' => '127.0.0.1',
                'detalles' => [
                    'titulo' => $limpieza->titulo,
                    'lugarId' => (string) $playaBalandra->_id,
                ],
            ]
        );

        $this->command?->info('Seeder de Regenera BCS ejecutado correctamente.');
        $this->command?->info('Se verificaron usuarios, lugares, voluntariados e inscripciones de prueba.');
    }
}
