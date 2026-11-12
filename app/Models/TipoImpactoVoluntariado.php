<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class TipoImpactoVoluntariado extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'tipos_impacto_voluntariados';

    protected $fillable = [
        'organizadorId',
        'clave',
        'etiqueta',
        'unidad',
    ];
}
