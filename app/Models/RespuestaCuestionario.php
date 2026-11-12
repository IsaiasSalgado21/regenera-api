<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class RespuestaCuestionario extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'respuestas_cuestionarios';

    protected $collection = 'respuestas_cuestionarios';

    protected $fillable = [
        'intentoId',
        'usuarioId',
        'cuestionarioSlug',
        'preguntaId',
        'opcion',
        'correcta',
        'respuestaCorrecta',
        'explicacion',
        'respondidaEn',
        'claveUnica',
    ];

    protected function casts(): array
    {
        return [
            'opcion' => 'integer',
            'correcta' => 'boolean',
            'respondidaEn' => 'datetime',
        ];
    }
}