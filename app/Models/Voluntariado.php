<?php

namespace App\Models;

use Illuminate\Support\Str;
use MongoDB\Laravel\Eloquent\Model;

class Voluntariado extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'voluntariados';

    protected $fillable = [
        'titulo',
        'descripcion',
        'categoria',
        'lugarId',
        'organizadorId',
        'latitud',
        'longitud',
        'fechaInicio',
        'fechaFin',
        'horaInicio',
        'horaFin',
        'cupo',
        'requisitos',
        'materiales',
        'imagen',
        'imagenes',
        'estado',
        'tokenAsistencia',
        'codigoAsistenciaActivo',
    ];

    protected $hidden = [
        'tokenAsistencia',
    ];

    protected static function booted(): void
    {
        static::creating(function (Voluntariado $voluntariado): void {
            if (! is_string($voluntariado->tokenAsistencia) || $voluntariado->tokenAsistencia === '') {
                $voluntariado->tokenAsistencia = Str::random(64);
            }
            $voluntariado->codigoAsistenciaActivo ??= true;
        });
    }

    protected function casts(): array
    {
        return [
            'fechaInicio' => 'datetime',
            'fechaFin' => 'datetime',
            'cupo' => 'integer',
            'latitud' => 'float',
            'longitud' => 'float',
            'codigoAsistenciaActivo' => 'boolean',
            'requisitos' => 'array',
            'materiales' => 'array',
        ];
    }
}
