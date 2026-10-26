<?php

namespace App\Services;

use App\Models\Articulo;
use App\Models\Notificacion;
use MongoDB\BSON\ObjectId;

class NotificationService
{
    public function articuloAprobado(Articulo $articulo): Notificacion
    {
        return $this->crearParaAutor(
            $articulo,
            'articulo_aprobado',
            'Artículo aprobado',
            'Tu artículo «'.$articulo->titulo.'» fue aprobado y publicado.',
            ['estado' => 'publicado'],
        );
    }

    public function articuloRechazado(Articulo $articulo, string $motivo): Notificacion
    {
        return $this->crearParaAutor(
            $articulo,
            'articulo_rechazado',
            'Artículo requiere cambios',
            'Tu artículo «'.$articulo->titulo.'» fue rechazado. Motivo: '.$motivo,
            ['estado' => 'rechazado', 'motivo' => $motivo],
        );
    }

    private function crearParaAutor(
        Articulo $articulo,
        string $tipo,
        string $titulo,
        string $mensaje,
        array $datos,
    ): Notificacion {
        $autorId = (string) $articulo->autorId;

        return Notificacion::create([
            'usuarioId' => preg_match('/^[a-f0-9]{24}$/i', $autorId) ? new ObjectId($autorId) : $autorId,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'entidad' => ['tipo' => 'articulo', 'id' => (string) $articulo->_id],
            'prioridad' => 'normal',
            'leida' => false,
            'fechaCreacion' => now(),
            'fechaLectura' => null,
            'fechaExpiracion' => null,
            'datos' => $datos,
        ]);
    }
}
