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
        'codigoCorto',
    ];

    protected $hidden = [
        'tokenAsistencia',
        'reservasCupo',
        'inscritosActivos',
    ];

    protected static function booted(): void
    {
        static::creating(function (Voluntariado $voluntariado): void {
            $voluntariado->inscritosActivos ??= 0;
            if (! is_string($voluntariado->tokenAsistencia) || $voluntariado->tokenAsistencia === '') {
                $voluntariado->tokenAsistencia = Str::random(64);
            }
            if (! is_string($voluntariado->codigoCorto) || $voluntariado->codigoCorto === '') {
                $voluntariado->codigoCorto = self::generarCodigoCorto();
            }
        });
    }

    public static function generarCodigoCorto(): string
    {
        $alfabeto = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $codigo = '';
        for ($i = 0; $i < 8; $i++) {
            $codigo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }

        return $codigo;
    }

    protected function casts(): array
    {
        return [
            'fechaInicio' => 'datetime',
            'fechaFin' => 'datetime',
            'cupo' => 'integer',
            'inscritosActivos' => 'integer',
            'latitud' => 'float',
            'longitud' => 'float',
            'requisitos' => 'array',
            'materiales' => 'array',
        ];
    }
}
