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
        // MVP: organizadorVerificado no bloquea la creación de voluntariados.
        return in_array($user->rol, ['admin', 'organizador'], true);
    }

    public function update(User $user, Lugar $lugar): bool
    {
        return $user->rol === 'admin'
            || ($user->rol === 'organizador' && (string) $lugar->creadoPor === (string) $user->getAuthIdentifier());
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
}
