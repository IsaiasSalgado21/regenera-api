<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'parent_id',
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

    public function padre(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at');
    }

    public function scopeVisiblesPara(Builder $query, User $user): Builder
    {
        if (in_array($user->rol, ['organizador', 'admin'], true)) {
            return $query;
        }

        $userId = $user->getKey();
        $userIds = $userId === null ? [] : [$userId, (string) $userId];
        $publishedParentIds = self::query()
            ->where('es_privado', false)
            ->where('es_publicado', true)
            ->whereNull('parent_id')
            ->get(['_id'])
            ->flatMap(static fn (self $comment) => [$comment->getKey(), (string) $comment->getKey()])
            ->all();
        $privateParentIds = self::query()
            ->whereIn('user_id', $userIds)
            ->whereNull('parent_id')
            ->get(['_id'])
            ->flatMap(static fn (self $comment) => [$comment->getKey(), (string) $comment->getKey()])
            ->all();

        return $query->where(function (Builder $visible) use ($userIds, $publishedParentIds, $privateParentIds): void {
            $visible
                ->where(function (Builder $publicos): void {
                    $publicos
                        ->where('es_privado', false)
                        ->where('es_publicado', true)
                        ->whereNull('parent_id');
                })
                ->orWhere(function (Builder $propios) use ($userIds): void {
                    $propios
                        ->where('es_privado', true)
                        ->whereIn('user_id', $userIds)
                        ->whereNull('parent_id');
                });

            if ($publishedParentIds !== []) {
                $visible->orWhere(function (Builder $replies) use ($publishedParentIds): void {
                    $replies
                        ->where('es_privado', false)
                        ->where('es_publicado', true)
                        ->whereIn('parent_id', $publishedParentIds);
                });
            }

            if ($privateParentIds !== []) {
                $visible->orWhere(function (Builder $replies) use ($privateParentIds): void {
                    $replies
                        ->where('es_privado', true)
                        ->whereIn('parent_id', $privateParentIds);
                });
            }
        });
    }

    public function esVisiblePara(User $user): bool
    {
        if (in_array($user->rol, ['organizador', 'admin'], true)) {
            return true;
        }

        if ((bool) $this->es_privado) {
            $userId = $user->getKey();

            if ($userId !== null && (string) $this->user_id === (string) $userId) {
                return true;
            }

            $padre = $this->padre;

            return $userId !== null
                && $this->parent_id !== null
                && $padre !== null
                && (string) $padre->user_id === (string) $userId;
        }

        if (! (bool) $this->es_publicado) {
            return false;
        }

        if ($this->parent_id === null) {
            return true;
        }

        $padre = $this->padre;

        return $padre !== null
            && ! (bool) $padre->es_privado
            && (bool) $padre->es_publicado;
    }
}
