<?php

namespace Database\Seeders;

use App\Models\Comentario;
use App\Models\Lugar;
use App\Models\User;
use App\Models\Voluntariado;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Uses three demo volunteer accounts and a dedicated demo organizer/activity.
 * Reruns are safe; to reset only these samples, remove the contents listed in
 * DUDAS, POSITIVOS, and RESPUESTAS.
 */
class ComentariosDemoSeeder extends Seeder
{
    public const PASSWORD = 'ComentariosDemo123!';

    public const DEMO_VOLUNTEER_TITLE = 'DEMO: comentarios de voluntariado';

    public const DUDAS = [
        [
            'contenido' => '¿A qué hora comienza la actividad y cuánto tiempo estaremos en la playa?',
            'es_privado' => true,
            'es_publicado' => false,
        ],
        [
            'contenido' => '¿El punto de encuentro es la entrada principal de Playa Balandra?',
            'es_privado' => false,
            'es_publicado' => true,
        ],
        [
            'contenido' => '¿Debo llevar guantes reutilizables o el equipo se proporciona en el lugar?',
            'es_privado' => true,
            'es_publicado' => false,
        ],
        [
            'contenido' => '¿El recorrido es accesible para una persona que usa silla de ruedas?',
            'es_privado' => false,
            'es_publicado' => true,
        ],
        [
            'contenido' => '¿Hay una edad mínima para participar en la jornada?',
            'es_privado' => true,
            'es_publicado' => false,
        ],
    ];

    public const POSITIVOS = [
        'Gracias por organizar esta jornada; me encantó ayudar a cuidar la playa.',
        'Fue una experiencia muy bonita y aprendí bastante sobre los residuos costeros.',
        'El equipo organizador explicó todo con claridad. ¡Felicidades!',
        'Me dio gusto conocer a otras personas comprometidas con el ambiente.',
        'Gracias por el espacio y por compartir el impacto logrado entre todos.',
    ];

    public const RESPUESTAS = [
        [
            'contenido' => 'La actividad inicia a las 8:00 y dura aproximadamente tres horas. ¡Gracias por preguntar!',
            'es_privado' => true,
            'es_publicado' => false,
        ],
        [
            'contenido' => 'Sí, nos reuniremos junto a la entrada principal; el equipo organizador estará identificado.',
            'es_privado' => false,
            'es_publicado' => true,
        ],
    ];

    private const DEMO_PLACE_NAME = 'Playa Balandra - demo comentarios';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('ComentariosDemoSeeder omitido: no se ejecuta en producción.');

            return;
        }

        $voluntarios = $this->crearVoluntariosDemo();
        $organizador = $this->crearOrganizadorDemo();
        $voluntariado = $this->crearVoluntariadoDemo($organizador);

        $dudas = [];
        foreach (self::DUDAS as $index => $duda) {
            $dudas[] = $this->crearComentario(
                $voluntariado,
                $voluntarios[$index % count($voluntarios)],
                $duda,
            );
        }

        foreach (self::POSITIVOS as $index => $contenido) {
            $this->crearComentario($voluntariado, $voluntarios[$index % count($voluntarios)], [
                'contenido' => $contenido,
                'es_privado' => false,
                'es_publicado' => true,
            ]);
        }

        $this->crearComentario($voluntariado, $organizador, self::RESPUESTAS[0], $dudas[0]);
        $this->crearComentario($voluntariado, $organizador, self::RESPUESTAS[1], $dudas[1]);

        $this->command?->info('Se cargaron 5 dudas, 5 comentarios positivos y 2 respuestas demo.');
        $this->command?->info('Voluntariado: '.$voluntariado->titulo.' ('.$voluntariado->getKey().')');
        $this->command?->info('Organizador propietario: '.$organizador->correo);
        $this->command?->info('Voluntarios demo: demo.comentarios.voluntario01@regenerabcs.com a demo.comentarios.voluntario03@regenerabcs.com / '.self::PASSWORD);
        if ($organizador->correo === 'demo.comentarios.organizador@regenerabcs.com') {
            $this->command?->info('Organizador demo: '.$organizador->correo.' / '.self::PASSWORD);
        }
    }

    /**
     * @return list<User>
     */
    private function crearVoluntariosDemo(): array
    {
        $voluntarios = [];

        for ($numero = 1; $numero <= 3; $numero++) {
            $correo = sprintf('demo.comentarios.voluntario%02d@regenerabcs.com', $numero);
            $voluntarios[] = User::updateOrCreate(
                ['correo' => $correo],
                [
                    'nombre' => 'Voluntario Demo Comentarios '.$numero,
                    'password' => Hash::make(self::PASSWORD),
                    'rol' => 'usuario',
                    'organizadorVerificado' => false,
                    'fechaRegistro' => now(),
                    'estado' => 'activo',
                    'perfilCompleto' => true,
                ],
            );
        }

        return $voluntarios;
    }

    private function crearOrganizadorDemo(): User
    {
        return User::updateOrCreate(
            ['correo' => 'demo.comentarios.organizador@regenerabcs.com'],
            [
                'nombre' => 'Organizador Demo Comentarios',
                'password' => Hash::make(self::PASSWORD),
                'rol' => 'organizador',
                'organizadorVerificado' => true,
                'fechaRegistro' => now(),
                'estado' => 'activo',
                'perfilCompleto' => true,
            ],
        );
    }

    private function crearVoluntariadoDemo(User $organizador): Voluntariado
    {
        $lugar = Lugar::firstOrCreate(
            ['nombre' => self::DEMO_PLACE_NAME],
            [
                'descripcion' => 'Punto de encuentro de una actividad de muestra para probar comentarios.',
                'municipio' => 'La Paz',
                'direccion' => 'Playa Balandra, La Paz, Baja California Sur',
                'latitud' => 24.3217,
                'longitud' => -110.3248,
                'tipo' => 'playa',
                'estado' => 'activo',
                'creadoPor' => (string) $organizador->getKey(),
            ],
        );

        return Voluntariado::updateOrCreate(
            ['titulo' => self::DEMO_VOLUNTEER_TITLE],
            [
                'descripcion' => 'Jornada de limpieza costera para retirar residuos y cuidar el hábitat.',
                'categoria' => 'Conservación',
                'lugarId' => (string) $lugar->getKey(),
                'organizadorId' => (string) $organizador->getKey(),
                'latitud' => $lugar->latitud,
                'longitud' => $lugar->longitud,
                'fechaInicio' => now()->addDays(14)->startOfDay(),
                'fechaFin' => now()->addDays(14)->startOfDay(),
                'horaInicio' => '08:00',
                'horaFin' => '11:00',
                'cupo' => 20,
                'requisitos' => [],
                'materiales' => ['Agua', 'Protector solar', 'Guantes reutilizables'],
                'estado' => 'publicado',
                'comentarios_por_defecto' => 'publico',
            ],
        );
    }

    private function crearComentario(
        Voluntariado $voluntariado,
        User $autor,
        array $data,
        ?Comentario $padre = null,
    ): Comentario {
        return Comentario::updateOrCreate(
            [
                'voluntariado_id' => $voluntariado->getKey(),
                'user_id' => $autor->getKey(),
                'contenido' => $data['contenido'],
            ],
            [
                'es_privado' => $data['es_privado'],
                'es_publicado' => $data['es_publicado'],
                'parent_id' => $padre?->getKey(),
            ],
        );
    }
}
