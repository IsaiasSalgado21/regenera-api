<?php

namespace App\Policies;

use App\Models\Lugar;
use App\Models\User;

class LugarPolicy
{
    public function view(?User $user, Lugar $lugar): bool
    {
        return $user?->rol === 'admin'
            || $lugar->estado === 'activo'
            || ($user?->rol === 'organizador' && (string) $lugar->creadoPor === (string) $user->getAuthIdentifier());
    }

    public function create(User $user): bool
    {
        return $user->rol === 'admin'
            || ($user->rol === 'organizador' && $user->organizadorVerificado);
    }

    public function update(User $user, Lugar $lugar): bool
    {
        return $user->rol === 'admin'
            || ($user->rol === 'organizador'
                && (string) $lugar->creadoPor === (string) $user->getAuthIdentifier()
                && (! $lugar->propuesto || $lugar->estado === 'pendiente_de_aprobacion'));
    }

    public function archivar(User $user, Lugar $lugar): bool
    {
        return $user->rol === 'admin';
    }

    public function publicar(User $user, Lugar $lugar): bool
    {
        return $user->rol === 'admin'
            && $lugar->estado === 'archivado';
    }

    public function revisar(User $user, Lugar $lugar): bool
    {
        return $user->rol === 'admin' && $lugar->estado === 'pendiente_de_aprobacion';
    }
}
