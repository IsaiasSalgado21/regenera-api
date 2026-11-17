<?php

namespace App\Policies;

use App\Models\Comentario;
use App\Models\User;
use App\Models\Voluntariado;

class ComentarioPolicy
{
    public function viewAny(User $user, Voluntariado $voluntariado): bool
    {
        return true;
    }

    public function create(User $user, Voluntariado $voluntariado): bool
    {
        return $voluntariado->exists;
    }

    public function view(User $user, Comentario $comentario): bool
    {
        return (
            ! (bool) $comentario->es_privado
            && (bool) $comentario->es_publicado
        ) || (string) $comentario->user_id === (string) $user->getAuthIdentifier()
            || $this->esOrganizadorPropietario($user, $comentario)
            || $user->rol === 'admin';
    }

    public function moderar(User $user, Comentario $comentario): bool
    {
        return $user->rol === 'admin'
            || $this->esOrganizadorPropietario($user, $comentario);
    }

    public function delete(User $user, Comentario $comentario): bool
    {
        return (string) $comentario->user_id === (string) $user->getAuthIdentifier()
            || $this->moderar($user, $comentario);
    }

    public function publicarDuda(User $user, Comentario $comentario): bool
    {
        return (bool) $comentario->es_privado
            && $this->moderar($user, $comentario);
    }

    private function esOrganizadorPropietario(User $user, Comentario $comentario): bool
    {
        if ($user->rol !== 'organizador') {
            return false;
        }

        $voluntariado = $comentario->voluntariado;

        return $voluntariado !== null
            && (string) $voluntariado->organizadorId === (string) $user->getAuthIdentifier();
    }
}
