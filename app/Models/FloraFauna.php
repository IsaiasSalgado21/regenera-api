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
        // ObjectId de usuarios para aplicar edición por propietario.
        'creadoPor',
        'tipo',
        'descripcion',
        'habitat',
        'estadoConservacion',
        'estado',
        'imagenes',
        'ubicacion',
        'lugaresRelacionados',
        'fuenteInformacion',
    ];
}
