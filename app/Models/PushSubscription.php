<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class PushSubscription extends Model
{
    protected $connection = 'mongodb';

    protected $table = 'push_subscriptions';

    protected $collection = 'push_subscriptions';

    protected $fillable = [
        'usuarioId',
        'endpoint',
        'publicKey',
        'authToken',
        'contentEncoding',
        'fechaCreacion',
        'fechaActualizacion',
    ];

    protected function casts(): array
    {
        return [
            'fechaCreacion' => 'datetime',
            'fechaActualizacion' => 'datetime',
        ];
    }
}
