<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Asistencia extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'asistencias';

    protected $fillable = [
        'usuarioId',
        'voluntariadoId',
        'fecha',
        'hora',
        'metodo',
        'confirmada',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'confirmada' => 'boolean',
        ];
    }
}