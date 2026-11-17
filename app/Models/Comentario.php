<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MongoDB\Laravel\Eloquent\Model;

class Comentario extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'comentarios';

    protected $fillable = [
        'voluntariado_id',
        'user_id',
        'contenido',
        'es_privado',
        'es_publicado',
    ];

    protected function casts(): array
    {
        return [
            'es_privado' => 'boolean',
            'es_publicado' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function voluntariado(): BelongsTo
    {
        return $this->belongsTo(Voluntariado::class, 'voluntariado_id');
    }

    public function scopeVisiblesPara(Builder $query, User $user): Builder
    {
        if (in_array($user->rol, ['organizador', 'admin'], true)) {
            return $query;
        }

        $userId = $user->getKey();
        $userIds = $userId === null ? [] : [$userId, (string) $userId];

        return $query->where(function (Builder $visible) use ($userIds): void {
            $visible
                ->where(function (Builder $publicos): void {
                    $publicos
                        ->where('es_privado', false)
                        ->where('es_publicado', true);
                })
                ->orWhere(function (Builder $propios) use ($userIds): void {
                    $propios
                        ->where('es_privado', true)
                        ->whereIn('user_id', $userIds);
                });
        });
    }

    public function esVisiblePara(User $user): bool
    {
        if (in_array($user->rol, ['organizador', 'admin'], true)) {
            return true;
        }

        if ((bool) $this->es_privado) {
            $userId = $user->getKey();

            return $userId !== null && (string) $this->user_id === (string) $userId;
        }

        return (bool) $this->es_publicado;
    }
}
