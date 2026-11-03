<?php

namespace App\Services;

use App\Models\Articulo;
use App\Models\Notificacion;
use App\Models\User;
use App\Models\Voluntariado;
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

    public function voluntariadoEnviado(Voluntariado $voluntariado): void
    {
        foreach (User::query()->where('rol', 'admin')->get() as $admin) {
            Notificacion::create([
                'usuarioId' => new ObjectId((string) $admin->getAuthIdentifier()),
                'tipo' => 'voluntariado_pendiente',
                'titulo' => 'Voluntariado pendiente de aprobación',
                'mensaje' => 'El voluntariado «'.$voluntariado->titulo.'» requiere revisión.',
                'entidad' => ['tipo' => 'voluntariado', 'id' => (string) $voluntariado->_id],
                'prioridad' => 'normal',
                'leida' => false,
                'fechaCreacion' => now(),
                'fechaLectura' => null,
                'fechaExpiracion' => null,
                'datos' => ['estado' => 'pendiente'],
            ]);
        }
    }

    public function voluntariadoAprobado(Voluntariado $voluntariado): Notificacion
    {
        return $this->crearParaVoluntariado(
            $voluntariado,
            'voluntariado_aprobado',
            'Voluntariado aprobado',
            'Tu voluntariado «'.$voluntariado->titulo.'» fue aprobado y publicado.',
            ['estado' => 'publicado'],
        );
    }

    public function voluntariadoRechazado(Voluntariado $voluntariado, string $motivo): Notificacion
    {
        return $this->crearParaVoluntariado(
            $voluntariado,
            'voluntariado_rechazado',
            'Voluntariado requiere cambios',
            'Tu voluntariado «'.$voluntariado->titulo.'» fue rechazado. Motivo: '.$motivo,
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

    private function crearParaVoluntariado(
        Voluntariado $voluntariado,
        string $tipo,
        string $titulo,
        string $mensaje,
        array $datos,
    ): Notificacion {
        $organizadorId = (string) $voluntariado->organizadorId;

        return Notificacion::create([
            'usuarioId' => preg_match('/^[a-f0-9]{24}$/i', $organizadorId) ? new ObjectId($organizadorId) : $organizadorId,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'entidad' => ['tipo' => 'voluntariado', 'id' => (string) $voluntariado->_id],
            'prioridad' => 'normal',
            'leida' => false,
            'fechaCreacion' => now(),
            'fechaLectura' => null,
            'fechaExpiracion' => null,
            'datos' => $datos,
        ]);
    }
}
