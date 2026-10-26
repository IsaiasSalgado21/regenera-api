<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Lugar extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'lugares';

    protected $collection = 'lugares';

    protected $fillable = [
        'nombre',
        'descripcion',
        'municipio',
        'direccion',
        'latitud',
        'longitud',
        'tipo',
        'estado',
        // ObjectId de usuarios para aplicar edición por propietario.
        'creadoPor',
    ];

    protected function casts(): array
    {
        return [
            'latitud' => 'float',
            'longitud' => 'float',
        ];
    }
}
