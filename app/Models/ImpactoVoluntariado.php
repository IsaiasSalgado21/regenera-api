<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ImpactoVoluntariado extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'impactos_voluntariados';

    protected $fillable = [
        'voluntariadoId',
        'tipo',
        'etiquetaPersonalizada',
        'unidadPersonalizada',
        'cantidad',
        'unidad',
        'nota',
        'especies',
        'imagenes',
        'registradoPor',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
        ];
    }
}
