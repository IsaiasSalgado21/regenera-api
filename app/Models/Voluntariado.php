<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Voluntariado extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'voluntariados';

    protected $fillable = [
        'titulo',
        'descripcion',
        'categoria',
        'lugarId',
        'organizadorId',
        'latitud',
        'longitud',
        'fechaInicio',
        'fechaFin',
        'horaInicio',
        'horaFin',
        'cupo',
        'requisitos',
        'materiales',
        'imagen',
        'imagenes',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fechaInicio' => 'datetime',
            'fechaFin' => 'datetime',
            'cupo' => 'integer',
            'latitud' => 'float',
            'longitud' => 'float',
            'requisitos' => 'array',
            'materiales' => 'array',
        ];
    }
}