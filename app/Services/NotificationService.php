<?php

namespace App\Services;

use App\Models\Articulo;
use App\Models\Inscripcion;
use App\Models\Notificacion;
use App\Models\User;
use App\Models\Voluntariado;
use MongoDB\BSON\ObjectId;

class NotificationService
{
    private const PRIORITY_NORMAL = 'normal';

    public function articuloAprobado(Articulo $articulo): Notificacion
    {
        return $this->crearParaAutor(
            $articulo,
            'articulo_aprobado',
            '¡Tu artículo fue aprobado!',
            'Tu artículo «'.$articulo->titulo.'» ya está publicado. ¡Gracias por compartirlo!',
            ['estado' => 'publicado'],
        );
    }

    public function articuloRechazado(Articulo $articulo, string $motivo): Notificacion
    {
        return $this->crearParaAutor(
            $articulo,
            'articulo_rechazado',
            'Tu artículo necesita cambios',
            'Revisamos «'.$articulo->titulo.'» y necesitamos algunos cambios. Motivo: '.$motivo,
            ['estado' => 'rechazado', 'motivo' => $motivo],
        );
    }

    public function voluntariadoEnviado(Voluntariado $voluntariado): void
    {
        foreach (User::query()->where('rol', 'admin')->get() as $admin) {
            Notificacion::create([
                'usuarioId' => new ObjectId((string) $admin->getAuthIdentifier()),
                'tipo' => 'sistema',
                'titulo' => 'Hay un voluntariado pendiente de revisión',
                'mensaje' => 'El voluntariado «'.$voluntariado->titulo.'» espera tu revisión en el panel de control.',
                'entidad' => ['tipo' => 'voluntariado', 'id' => (string) $voluntariado->_id],
                'prioridad' => 'normal',
                'leida' => false,
                'fechaCreacion' => now(),
                'fechaLectura' => null,
                'fechaExpiracion' => null,
                'datos' => ['estado' => 'pendiente', 'url' => url('/admin/dashboard')],
            ]);
        }
    }

    public function articuloEnviadoRevision(Articulo $articulo): void
    {
        foreach (User::query()->where('rol', 'admin')->get() as $admin) {
            $this->crear(
                (string) $admin->getAuthIdentifier(),
                'sistema',
                'Hay un nuevo artículo pendiente de revisión',
                'El artículo «'.$articulo->titulo.'» espera tu revisión en el panel de control.',
                'articulo',
                (string) $articulo->_id,
                ['estado' => 'pendiente', 'url' => url('/admin/dashboard')],
            );
        }
    }

    public function voluntariadoAprobado(Voluntariado $voluntariado): Notificacion
    {
        return $this->crearParaVoluntariado(
            $voluntariado,
            'sistema',
            '¡Tu voluntariado fue aprobado!',
            '«'.$voluntariado->titulo.'» ya está publicado y listo para recibir participantes.',
            ['estado' => 'publicado'],
        );
    }

    public function voluntariadoRechazado(Voluntariado $voluntariado, string $motivo): Notificacion
    {
        return $this->crearParaVoluntariado(
            $voluntariado,
            'sistema',
            'Tu voluntariado necesita cambios',
            'Revisamos «'.$voluntariado->titulo.'» y necesitamos algunos cambios. Motivo: '.$motivo,
            ['estado' => 'rechazado', 'motivo' => $motivo],
        );
    }

    public function inscripcionConfirmada(Inscripcion $inscripcion, Voluntariado $voluntariado): Notificacion
    {
        return $this->crear(
            (string) $inscripcion->usuarioId,
            'inscripcion_confirmada',
            '¡Tu inscripción está confirmada!',
            'Tu inscripción a «'.$voluntariado->titulo.'» está confirmada. ¡Nos vemos pronto!',
            'voluntariado',
            (string) $voluntariado->_id,
            ['estado' => 'inscrita'],
        );
    }

    public function inscripcionCancelada(Inscripcion $inscripcion, Voluntariado $voluntariado): Notificacion
    {
        return $this->crear(
            (string) $inscripcion->usuarioId,
            'inscripcion_cancelada',
            'Tu inscripción fue cancelada',
            'Tu inscripción a «'.$voluntariado->titulo.'» fue cancelada. Si fue un error, revisa los detalles del voluntariado.',
            'voluntariado',
            (string) $voluntariado->_id,
            ['estado' => 'cancelada'],
        );
    }

    public function asistenciaRegistrada(string $usuarioId, Voluntariado $voluntariado): Notificacion
    {
        return $this->crear(
            $usuarioId,
            'asistencia_registrada',
            '¡Gracias por participar!',
            'Tu asistencia a «'.$voluntariado->titulo.'» ha sido registrada. ¡Tu ayuda hace la diferencia!',
            'voluntariado',
            (string) $voluntariado->_id,
            ['estado' => 'completada'],
        );
    }

    private function crear(
        string $usuarioId,
        string $tipo,
        string $titulo,
        string $mensaje,
        string $entidadTipo,
        string $entidadId,
        array $datos = [],
    ): Notificacion {
        return Notificacion::create([
            'usuarioId' => preg_match('/^[a-f0-9]{24}$/i', $usuarioId) ? new ObjectId($usuarioId) : $usuarioId,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'entidad' => ['tipo' => $entidadTipo, 'id' => $entidadId],
            'prioridad' => self::PRIORITY_NORMAL,
            'leida' => false,
            'fechaCreacion' => now(),
            'fechaLectura' => null,
            'fechaExpiracion' => null,
            'datos' => $datos,
        ]);
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
            'prioridad' => self::PRIORITY_NORMAL,
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
