<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Auditoria extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'auditoria';
    protected $collection = 'auditoria';

    protected $fillable = [
        'usuarioId',
        'accion',
        'entidad',
        'entidadId',
        'fecha',
        'ip',
        'detalles',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'detalles' => 'array',
        ];
    }
}