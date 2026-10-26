<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Notificacion extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'notificaciones';

    protected $collection = 'notificaciones';

    protected $fillable = [
        'usuarioId',
        'tipo',
        'titulo',
        'mensaje',
        'entidad',
        'prioridad',
        'leida',
        'fechaCreacion',
        'fechaLectura',
        'fechaExpiracion',
        'datos',
    ];

    protected function casts(): array
    {
        return [
            'leida' => 'boolean',
            'fechaCreacion' => 'datetime',
            'fechaLectura' => 'datetime',
            'fechaExpiracion' => 'datetime',
        ];
    }
}
