<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class FloraFauna extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'floraFauna';
    protected $collection = 'floraFauna';

    protected $fillable = [
        'nombre',
        'nombreCientifico',
        'tipo',
        'descripcion',
        'habitat',
        'estadoConservacion',
        'imagenes',
        'ubicacion',
        'lugaresRelacionados',
        'fuenteInformacion',
    ];

    protected function casts(): array
    {
        return [
            'imagenes' => 'array',
            'ubicacion' => 'array',
            'lugaresRelacionados' => 'array',
        ];
    }
}