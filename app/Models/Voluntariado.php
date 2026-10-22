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
        'fechaInicio',
        'fechaFin',
        'horaInicio',
        'horaFin',
        'cupo',
        'requisitos',
        'materiales',
        'imagen',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fechaInicio' => 'datetime',
            'fechaFin' => 'datetime',
            'cupo' => 'integer',
            'requisitos' => 'array',
            'materiales' => 'array',
        ];
    }
}