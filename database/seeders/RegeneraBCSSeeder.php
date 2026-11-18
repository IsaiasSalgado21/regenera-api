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
use Illuminate\Http\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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

        $fechaCupoLleno = now()->addDays(28)->startOfDay();
        $cupoLleno = Voluntariado::firstOrCreate(
            ['titulo' => 'Prueba de inscripción con cupo lleno'],
            [
                'descripcion' => 'Evento de prueba con el único lugar ocupado para verificar el rechazo de nuevas inscripciones.',
                'lugarId' => (string) $playaBalandra->_id,
                'organizadorId' => (string) $organizador->_id,
                'fechaInicio' => $fechaCupoLleno->copy()->setTime(8, 0),
                'fechaFin' => $fechaCupoLleno->copy()->setTime(10, 0),
                'horaInicio' => '08:00',
                'horaFin' => '10:00',
                'cupo' => 1,
                'requisitos' => ['Inscripción de prueba para validar el límite de cupo.'],
                'materiales' => [],
                'imagen' => null,
                'estado' => 'publicado',
            ]
        );

        DB::purge('mongodb');
        DB::reconnect('mongodb');

        $this->replaceImage($limpieza, 'limpieza-playa.png', 'voluntariados');
        $this->replaceImage($reforestacion, 'reforestacion-manglar.png', 'voluntariados');
        $this->replaceImage($educacion, 'limpieza-playa.png', 'voluntariados');

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
                'claveRegistroActiva' => (string) $usuario1->_id.'|'.(string) $limpieza->_id,
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
                'claveRegistroActiva' => (string) $usuario2->_id.'|'.(string) $limpieza->_id,
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
                'claveRegistroActiva' => (string) $usuario1->_id.'|'.(string) $reforestacion->_id,
                'fechaInscripcion' => now(),
                'estado' => 'inscrita',
                'observaciones' => 'Inscripción de prueba para reforestación.',
            ]
        );

        Inscripcion::firstOrCreate(
            [
                'usuarioId' => (string) $usuario1->_id,
                'voluntariadoId' => (string) $cupoLleno->_id,
            ],
            [
                'claveRegistroActiva' => (string) $usuario1->_id.'|'.(string) $cupoLleno->_id,
                'fechaInscripcion' => now(),
                'estado' => 'inscrita',
                'observaciones' => 'Lugar ocupado para probar el rechazo por cupo lleno.',
            ]
        );

        $this->syncActiveCupo($limpieza);
        $this->syncActiveCupo($reforestacion);
        $this->syncActiveCupo($educacion);
        $this->syncActiveCupo($cupoLleno);

        /*
        |--------------------------------------------------------------------------
        | 5. Flora y Fauna
        |--------------------------------------------------------------------------
        */

        $mangleRojo = FloraFauna::query()->firstOrNew([
            'nombreCientifico' => 'Rhizophora mangle',
        ]);
        if (! $mangleRojo->exists) {
            $mangleRojo->creadoPor = new ObjectId((string) $organizador->_id);
            $mangleRojo->estado = 'publicado';
            $mangleRojo->imagenes = [];
        }
        $mangleRojo->fill([
            'nombre' => 'Mangle rojo',
            'nombreCientifico' => 'Rhizophora mangle',
            'tipo' => 'flora',
            'descripcion' => 'El mangle rojo es un árbol de manglar adaptado a ambientes salinos e intermareales. Sus raíces aéreas ayudan a retener sedimentos y forman una estructura compleja en la franja costera.'."\n\n".
                'En Baja California Sur, los manglares conectan ambientes terrestres y marinos y ofrecen refugio y zonas de crianza para organismos costeros. Su conservación depende de mantener íntegros los humedales y la calidad del agua.',
            'habitat' => 'Manglares, esteros y zonas intermareales salobres o salinas de las costas.',
            'estadoConservacion' => 'Amenazada (A)',
            'ubicacion' => [
                'nombre' => 'Distribución costera tropical y subtropical; presencia documentada en Baja California Sur. No se asigna un sitio puntual.',
            ],
            'lugaresRelacionados' => [],
            'fuenteInformacion' => 'CONABIO, EncicloVida (consulta 2026-11-18): https://enciclovida.mx/especies/166804-rhizophora-mangle; DOF, NOM-059 (14-11-2019): https://dof.gob.mx/nota_detalle.php?codigo=5578808&fecha=14/11/2019',
        ]);
        $mangleRojo->save();

        $tortugasNegras = FloraFauna::query()
            ->whereIn('nombreCientifico', ['Chelonia agassizii', 'Chelonia mydas'])
            ->get();
        if ($tortugasNegras->count() > 1) {
            throw new \RuntimeException('Hay más de un registro para la tortuga negra; resuelve la duplicidad antes de sembrar.');
        }

        $tortugaNegra = $tortugasNegras->first()
            ?? FloraFauna::query()->firstOrNew(['nombre' => 'Tortuga Negra']);
        if (! $tortugaNegra->exists) {
            $tortugaNegra->creadoPor = new ObjectId((string) $organizador->_id);
            $tortugaNegra->estado = 'publicado';
            $tortugaNegra->imagenes = [];
        }
        $tortugaNegra->fill([
            'nombre' => 'Tortuga negra o prieta',
            'nombreCientifico' => 'Chelonia mydas',
            'tipo' => 'fauna',
            'descripcion' => 'La tortuga negra o prieta es la forma del Pacífico oriental de la tortuga verde, una tortuga marina que consume principalmente pastos marinos y algas. El nombre Chelonia agassizii se ha usado para esta forma, pero EncicloVida lo registra como sinónimo de Chelonia mydas; su reconocimiento como especie independiente es controvertido.'."\n\n".
                'En el Pacífico mexicano, la tortuga negra tiene importancia ecológica y cultural. Esta ficha no señala una playa de anidación ni un punto de observación específico en Baja California Sur.',
            'habitat' => 'Aguas marinas tropicales y subtropicales del Pacífico oriental; zonas costeras y áreas con pastos marinos y algas.',
            'estadoConservacion' => 'En peligro de extinción (P)',
            'ubicacion' => [
                'nombre' => 'Distribución marina del Pacífico oriental; no se asigna una playa o localidad puntual de Baja California Sur.',
            ],
            'lugaresRelacionados' => [],
            'fuenteInformacion' => 'EncicloVida (consulta 2026-11-18): https://enciclovida.mx/especies/27176-chelonia-mydas; CONANP PACE (2019): https://www.gob.mx/conanp/documentos/programa-de-accion-para-la-conservacion-de-la-especie-tortuga-verde-negra-chelonia-mydas',
        ]);
        $tortugaNegra->save();

        if (empty($mangleRojo->imagenes)) {
            $this->replaceImages($mangleRojo, 'manglar-rojo.png', 'flora-fauna');
        }
        if (empty($tortugaNegra->imagenes)) {
            $this->replaceImages($tortugaNegra, 'tortuga-negra.png', 'flora-fauna');
        }

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

        $articulo2 = Articulo::firstOrCreate(
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

        $this->replaceImage($articulo1, 'manglar.png', 'articulos');
        $this->replaceImage($articulo2, 'costas-lapaz.jpg', 'articulos');

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

    private function replaceImage(object $model, string $filename, string $directory): void
    {
        $imagePath = database_path('seeders/imagenes/'.$filename);

        if (! is_file($imagePath)) {
            throw new \RuntimeException("No se encontró la imagen del seeder: {$imagePath}");
        }

        $previousPath = $model->imagen;
        $newPath = $this->uploadImage($imagePath, $filename, $directory);

        $model->imagen = $newPath;
        $model->save();

        if (
            is_string($previousPath)
            && $previousPath !== ''
            && ! filter_var($previousPath, FILTER_VALIDATE_URL)
            && $previousPath !== $newPath
        ) {
            Storage::disk('gridfs')->delete($previousPath);
        }
    }

    private function syncActiveCupo(Voluntariado $voluntariado): void
    {
        $inscripciones = Inscripcion::where('voluntariadoId', (string) $voluntariado->getKey())
            ->whereIn('estado', ['inscrita', 'activa', 'completada'])
            ->get();

        $voluntariado->forceFill([
            'inscritosActivos' => $inscripciones->count(),
            'reservasCupo' => $inscripciones
                ->map(fn (Inscripcion $inscripcion) => (string) $inscripcion->getKey())
                ->values()
                ->all(),
        ])->save();
    }

    private function replaceImages(object $model, string $filename, string $directory): void
    {
        $storedImages = $model->imagenes ?? null;
        $previousPaths = is_array($storedImages)
            ? $storedImages
            : (is_iterable($storedImages) ? iterator_to_array($storedImages) : []);
        $previousPaths = array_values(array_filter(
            $previousPaths,
            fn ($path) => is_string($path) && $path !== '',
        ));
        $imagePath = database_path('seeders/imagenes/'.$filename);

        if (! is_file($imagePath)) {
            throw new \RuntimeException("No se encontró la imagen del seeder: {$imagePath}");
        }

        $newPath = $this->uploadImage($imagePath, $filename, $directory);
        $model->imagenes = [$newPath];
        $model->save();

        foreach ($previousPaths as $previousPath) {
            if (! filter_var($previousPath, FILTER_VALIDATE_URL) && $previousPath !== $newPath) {
                Storage::disk('gridfs')->delete($previousPath);
            }
        }
    }

    private function uploadImage(string $imagePath, string $filename, string $directory): string
    {
        $newPath = Storage::disk('gridfs')->putFile($directory, new File($imagePath));

        if (! is_string($newPath)) {
            throw new \RuntimeException("No se pudo subir la imagen del seeder: {$filename}");
        }

        return $newPath;
    }
}
