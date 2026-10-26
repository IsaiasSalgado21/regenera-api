<?php

namespace App\Policies;

use App\Models\FloraFauna;
use App\Models\User;

class FloraFaunaPolicy
{
    public function view(?User $user, FloraFauna $registro): bool
    {
        return $user?->rol === 'admin'
            || $registro->estado === 'publicado'
            || ($user?->rol === 'organizador' && (string) $registro->creadoPor === (string) $user->getAuthIdentifier());
    }

    public function create(User $user): bool
    {
        return in_array($user->rol, ['admin', 'organizador'], true);
    }

    public function update(User $user, FloraFauna $registro): bool
    {
        return $user->rol === 'admin'
            || ($user->rol === 'organizador' && (string) $registro->creadoPor === (string) $user->getAuthIdentifier());
    }

    public function archivar(User $user, FloraFauna $registro): bool
    {
        return $user->rol === 'admin';
    }
}
