<?php

namespace Database\Seeders;

use App\Models\Comentario;
use App\Models\User;
use App\Models\Voluntariado;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ComentarioSeeder extends Seeder
{
    public function run(): void
    {
        $voluntario = User::query()->where('rol', 'usuario')->first()
            ?? $this->crearUsuarioDemo(
                'demo.comentarios.voluntario@regenerabcs.com',
                'Voluntario Demo Comentarios',
                'usuario',
            );
        $organizador = User::query()->where('rol', 'organizador')->first()
            ?? $this->crearUsuarioDemo(
                'demo.comentarios.organizador@regenerabcs.com',
                'Organizador Demo Comentarios',
                'organizador',
            );
        $voluntariado = Voluntariado::query()->first()
            ?? $this->crearVoluntariadoDemo($organizador);

        $ejemplos = [
            [
                'contenido' => 'Comentario público de ejemplo para la actividad.',
                'es_privado' => false,
                'es_publicado' => true,
            ],
            [
                'contenido' => 'Pregunta privada de ejemplo para el organizador.',
                'es_privado' => true,
                'es_publicado' => true,
            ],
            [
                'contenido' => 'Comentario oculto de ejemplo para probar moderación.',
                'es_privado' => false,
                'es_publicado' => false,
            ],
        ];

        foreach ($ejemplos as $ejemplo) {
            Comentario::query()->updateOrCreate(
                [
                    'voluntariado_id' => $voluntariado->getKey(),
                    'user_id' => $voluntario->getKey(),
                    'contenido' => $ejemplo['contenido'],
                ],
                [
                    'es_privado' => $ejemplo['es_privado'],
                    'es_publicado' => $ejemplo['es_publicado'],
                ],
            );
        }

        $this->command?->info('Comentarios de ejemplo creados o actualizados correctamente.');
    }

    private function crearUsuarioDemo(string $correo, string $nombre, string $rol): User
    {
        return User::firstOrCreate(
            ['correo' => $correo],
            [
                'nombre' => $nombre,
                'password' => Hash::make(Str::random(40)),
                'rol' => $rol,
                'organizadorVerificado' => $rol === 'organizador',
                'fechaRegistro' => now(),
                'estado' => 'activo',
            ],
        );
    }

    private function crearVoluntariadoDemo(User $organizador): Voluntariado
    {
        return Voluntariado::create([
            'titulo' => 'DEMO: comentarios de voluntariado',
            'descripcion' => 'Actividad de prueba para validar comentarios públicos, privados y ocultos.',
            'categoria' => 'Conservación',
            'organizadorId' => (string) $organizador->getKey(),
            'fechaInicio' => now()->addDays(7),
            'fechaFin' => now()->addDays(7),
            'horaInicio' => '08:00',
            'horaFin' => '10:00',
            'cupo' => 10,
            'requisitos' => [],
            'materiales' => [],
            'estado' => 'publicado',
        ]);
    }
}
