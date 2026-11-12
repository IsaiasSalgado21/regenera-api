<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Cuestionario extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'cuestionarios';

    protected $collection = 'cuestionarios';

    protected $fillable = [
        'slug',
        'version',
        'titulo',
        'descripcion',
        'guia',
        'audiencia',
        'requiereOrganizadorVerificado',
        'minimoAprobacion',
        'preguntas',
        'medalla',
        'puntosAprobacion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'minimoAprobacion' => 'float',
            'puntosAprobacion' => 'integer',
            'activo' => 'boolean',
            'requiereOrganizadorVerificado' => 'boolean',
            'preguntas' => 'array',
            'audiencia' => 'array',
            'medalla' => 'array',
        ];
    }
}