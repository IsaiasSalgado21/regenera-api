<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Articulo extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'articulos';

    protected $fillable = [
        'autorId',
        'titulo',
        'contenido',
        'imagen',
        'categoria',
        'estado',
        'motivoRechazo',
        'fechaPublicacion',
        'fechaActualizacion',
    ];

    protected function casts(): array
    {
        return [
            'fechaPublicacion' => 'datetime',
            'fechaActualizacion' => 'datetime',
        ];
    }
}
