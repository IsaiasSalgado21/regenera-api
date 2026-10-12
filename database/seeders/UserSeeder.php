<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Ejecuta el seeder.
     */
    public function run(): void
    {
        User::create([
            'nombre' => 'Administrador Regenera BCS',
            'correo' => 'admin@regenerabcs.com',
            'password' => Hash::make('Admin12345'),
            'foto' => null,
            'rol' => 'admin',
            'organizadorVerificado' => true,
            'fechaRegistro' => now(),
            'estado' => 'activo',
        ]);

        User::create([
            'nombre' => 'Organizador de Prueba',
            'correo' => 'organizador@regenerabcs.com',
            'password' => Hash::make('Organizador12345'),
            'foto' => null,
            'rol' => 'organizador',
            'organizadorVerificado' => true,
            'fechaRegistro' => now(),
            'estado' => 'activo',
        ]);

        User::create([
            'nombre' => 'Usuario de Prueba',
            'correo' => 'usuario@regenerabcs.com',
            'password' => Hash::make('Usuario12345'),
            'foto' => null,
            'rol' => 'usuario',
            'organizadorVerificado' => false,
            'fechaRegistro' => now(),
            'estado' => 'activo',
        ]);
    }
}