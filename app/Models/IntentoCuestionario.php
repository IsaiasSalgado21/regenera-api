<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class IntentoCuestionario extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'intentos_cuestionarios';

    protected $collection = 'intentos_cuestionarios';

    protected $fillable = [
        'usuarioId',
        'cuestionarioSlug',
        'cuestionarioVersion',
        'actividadId',
        'estado',
        'opcionesOrden',
        'respuestas',
        'preguntasCorrectas',
        'totalPreguntas',
        'puntuacion',
        'fechaInicio',
        'fechaUltimoIntento',
        'fechaAprobacion',
    ];

    protected function casts(): array
    {
        return [
            'cuestionarioVersion' => 'integer',
            'preguntasCorrectas' => 'integer',
            'totalPreguntas' => 'integer',
            'puntuacion' => 'float',
            'opcionesOrden' => 'array',
            'respuestas' => 'array',
            'fechaInicio' => 'datetime',
            'fechaUltimoIntento' => 'datetime',
            'fechaAprobacion' => 'datetime',
        ];
    }
}