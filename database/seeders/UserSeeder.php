<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['correo' => 'admin@regenerabcs.com'],
            [
                'nombre' => 'Administrador Regenera BCS',
                'password' => Hash::make('Admin12345'),
                'foto' => null,
                'rol' => 'admin',
                'organizadorVerificado' => true,
                'fechaRegistro' => now(),
                'estado' => 'activo',
            ]
        );

        User::firstOrCreate(
            ['correo' => 'organizador@regenerabcs.com'],
            [
                'nombre' => 'Organizador de Prueba',
                'password' => Hash::make('Organizador12345'),
                'foto' => null,
                'rol' => 'organizador',
                'organizadorVerificado' => true,
                'fechaRegistro' => now(),
                'estado' => 'activo',
            ]
        );

        User::firstOrCreate(
            ['correo' => 'usuario@regenerabcs.com'],
            [
                'nombre' => 'Usuario de Prueba',
                'password' => Hash::make('Usuario12345'),
                'foto' => null,
                'rol' => 'usuario',
                'organizadorVerificado' => false,
                'fechaRegistro' => now(),
                'estado' => 'activo',
            ]
        );

        $this->command?->info(
            'Usuarios de prueba verificados correctamente.'
        );
    }
}
