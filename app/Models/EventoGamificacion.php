<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class EventoGamificacion extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'eventos_gamificacion';

    protected $collection = 'eventos_gamificacion';

    protected $fillable = [
        'usuarioId',
        'tipo',
        'cantidad',
        'motivo',
        'referenciaTipo',
        'referenciaId',
        'claveUnica',
        'creadoEn',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'creadoEn' => 'datetime',
        ];
    }
}