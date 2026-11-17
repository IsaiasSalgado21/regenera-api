<?php

namespace App\Services;

use App\Models\Comentario;
use App\Models\User;
use App\Models\Voluntariado;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class ComentarioService
{
    public function listarParaUsuario(Voluntariado $voluntariado, User $user): Collection
    {
        $voluntariadoIds = $this->idsCompatibles($voluntariado->getKey());
        $esPropietario = $user->rol === 'organizador'
            && (string) $voluntariado->organizadorId === (string) $user->getAuthIdentifier();
        $puedeVerTodos = $user->rol === 'admin' || $esPropietario;

        $query = Comentario::query()
            ->whereIn('voluntariado_id', $voluntariadoIds)
            ->with([
                'user' => static fn ($usuarios) => $usuarios->select(['_id', 'nombre', 'rol']),
                'voluntariado' => static fn ($voluntariados) => $voluntariados->select(['_id', 'organizadorId']),
            ]);

        if (! $puedeVerTodos) {
            $userIds = $this->idsCompatibles($user->getKey());
            $query->where(function ($visibles) use ($userIds): void {
                $visibles
                    ->where(function ($publicos): void {
                        $publicos
                            ->where('es_privado', false)
                            ->where('es_publicado', true);
                    })
                    ->orWhere(function ($propios) use ($userIds): void {
                        $propios
                            ->where('es_privado', true)
                            ->whereIn('user_id', $userIds);
                    });
            });
        }

        return $query->orderBy('created_at', 'asc')->get();
    }

    public function crear(Voluntariado $voluntariado, User $user, array $data): Comentario
    {
        $esPrivado = (bool) ($data['es_privado'] ?? false);
        if ($user->rol === 'usuario' && $voluntariado->comentarios_por_defecto === 'privado') {
            $esPrivado = true;
        }

        return Comentario::create([
            'voluntariado_id' => $voluntariado->getKey(),
            'user_id' => $user->getKey(),
            'contenido' => $data['contenido'],
            'es_privado' => $esPrivado,
            'es_publicado' => ! $esPrivado,
        ])->load([
            'user' => static fn ($usuarios) => $usuarios->select(['_id', 'nombre', 'rol']),
            'voluntariado' => static fn ($voluntariados) => $voluntariados->select(['_id', 'organizadorId']),
        ]);
    }

    public function responder(Comentario $comentarioPadre, User $organizador, string $contenido): Comentario
    {
        $esPrivado = (bool) $comentarioPadre->es_privado;

        // A private question keeps its discussion private; public comments receive a public, published reply.
        return Comentario::create([
            'voluntariado_id' => $comentarioPadre->voluntariado_id,
            'user_id' => $organizador->getKey(),
            'contenido' => $contenido,
            'es_privado' => $esPrivado,
            'es_publicado' => ! $esPrivado,
        ])->load([
            'user' => static fn ($usuarios) => $usuarios->select(['_id', 'nombre', 'rol']),
            'voluntariado' => static fn ($voluntariados) => $voluntariados->select(['_id', 'organizadorId']),
        ]);
    }

    public function cambiarVisibilidad(Comentario $comentario, bool $esPublicado): Comentario
    {
        if ((bool) $comentario->es_privado) {
            throw ValidationException::withMessages([
                'es_publicado' => 'Una duda privada debe publicarse mediante la acción publicar.',
            ]);
        }

        $comentario->update(['es_publicado' => $esPublicado]);

        return $comentario->fresh([
            'user' => static fn ($usuarios) => $usuarios->select(['_id', 'nombre', 'rol']),
            'voluntariado' => static fn ($voluntariados) => $voluntariados->select(['_id', 'organizadorId']),
        ]);
    }

    public function publicarDudaPrivada(Comentario $comentario): Comentario
    {
        if (! (bool) $comentario->es_privado) {
            throw ValidationException::withMessages([
                'comentario' => 'Solo se puede publicar una duda privada.',
            ]);
        }

        $comentario->update([
            'es_privado' => false,
            'es_publicado' => true,
        ]);

        return $comentario->fresh([
            'user' => static fn ($usuarios) => $usuarios->select(['_id', 'nombre', 'rol']),
            'voluntariado' => static fn ($voluntariados) => $voluntariados->select(['_id', 'organizadorId']),
        ]);
    }

    public function eliminar(Comentario $comentario): void
    {
        $comentario->delete();
    }

    private function idsCompatibles(mixed $id): array
    {
        return $id === null ? [] : [$id, (string) $id];
    }
}
