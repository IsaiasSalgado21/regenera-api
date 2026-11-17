<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Medalla extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'medallas';

    protected $fillable = ['slug', 'nombre', 'descripcion', 'icono', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }
}
