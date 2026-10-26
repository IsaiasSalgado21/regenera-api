<?php

namespace App\Policies;

use App\Models\Articulo;
use App\Models\User;

class ArticuloPolicy
{
    public function view(?User $user, Articulo $articulo): bool
    {
        return $articulo->estado === 'publicado'
            || $user?->rol === 'admin'
            || ($user?->rol === 'organizador' && (string) $articulo->autorId === (string) $user->getAuthIdentifier());
    }

    public function create(User $user): bool
    {
        return in_array($user->rol, ['admin', 'organizador'], true);
    }

    public function update(User $user, Articulo $articulo): bool
    {
        return $user->rol === 'admin'
            || ($user->rol === 'organizador'
                && (string) $articulo->autorId === (string) $user->getAuthIdentifier()
                && in_array($articulo->estado, ['borrador', 'rechazado'], true));
    }

    public function enviarRevision(User $user, Articulo $articulo): bool
    {
        return $user->rol === 'organizador'
            && (string) $articulo->autorId === (string) $user->getAuthIdentifier()
            && in_array($articulo->estado, ['borrador', 'rechazado'], true);
    }

    public function aprobar(User $user, Articulo $articulo): bool
    {
        return $user->rol === 'admin' && $articulo->estado === 'pendiente';
    }

    public function rechazar(User $user, Articulo $articulo): bool
    {
        return $user->rol === 'admin' && $articulo->estado === 'pendiente';
    }

    public function despublicar(User $user, Articulo $articulo): bool
    {
        return $user->rol === 'admin' && $articulo->estado === 'publicado';
    }

    public function eliminar(User $user, Articulo $articulo): bool
    {
        return $user->rol === 'admin' && $articulo->estado === 'borrador';
    }
}
