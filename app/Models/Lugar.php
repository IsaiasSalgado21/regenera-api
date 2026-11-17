<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Lugar extends Model
{
    public static function requireActive(string $id): self
    {
        $lugar = preg_match('/^[a-f0-9]{24}$/i', $id) ? self::find($id) : null;
        if (! $lugar || $lugar->estado !== 'activo') {
            throw ValidationException::withMessages([
                'lugarId' => 'Selecciona un lugar aprobado y disponible.',
            ]);
        }

        return $lugar;
    }

    protected $connection = 'mongodb';

    protected $table = 'lugares';

    protected $collection = 'lugares';

    protected $fillable = [
        'nombre',
        'descripcion',
        'municipio',
        'direccion',
        'latitud',
        'longitud',
        'tipo',
        'imagen',
        'estado',
        // ObjectId de usuarios para aplicar edición por propietario.
        'creadoPor',
        'propuesto',
        'motivoRechazo',
    ];

    protected function casts(): array
    {
        return [
            'latitud' => 'float',
            'longitud' => 'float',
        ];
    }
}
