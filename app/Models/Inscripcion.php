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
        'claveRegistroActiva',
        'fechaInscripcion',
        'estado',
        'observaciones',
    ];

    protected $hidden = [
        'claveRegistroActiva',
    ];

    protected function casts(): array
    {
        return [
            'fechaInscripcion' => 'datetime',
        ];
    }
}

