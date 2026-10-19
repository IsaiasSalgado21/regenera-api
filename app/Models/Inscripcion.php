<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Inscripcion extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'inscripciones';
    protected $collection = 'inscripciones';

    protected $fillable = [
        'usuarioId',
        'voluntariadoId',
        'fechaInscripcion',
        'estado',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fechaInscripcion' => 'datetime',
        ];
    }
}

