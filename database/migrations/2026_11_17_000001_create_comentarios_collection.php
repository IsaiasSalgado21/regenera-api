<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        $defineCollection = static function (Blueprint $collection): void {
            $collection->jsonSchema([
                'bsonType' => 'object',
                'required' => [
                    'voluntariado_id',
                    'user_id',
                    'contenido',
                    'es_privado',
                    'es_publicado',
                    'created_at',
                    'updated_at',
                ],
                'properties' => [
                    '_id' => ['bsonType' => 'objectId'],
                    'voluntariado_id' => ['bsonType' => ['objectId', 'string']],
                    'user_id' => ['bsonType' => ['objectId', 'string']],
                    'contenido' => ['bsonType' => 'string'],
                    'es_privado' => ['bsonType' => 'bool'],
                    'es_publicado' => ['bsonType' => 'bool'],
                    'created_at' => ['bsonType' => 'date'],
                    'updated_at' => ['bsonType' => 'date'],
                ],
            ]);
            $collection->index('voluntariado_id');
            $collection->index('user_id');
            $collection->index(['voluntariado_id', 'es_publicado']);
        };

        if (! Schema::connection('mongodb')->hasCollection('comentarios')) {
            Schema::connection('mongodb')->create('comentarios', $defineCollection);

            return;
        }

        Schema::connection('mongodb')->table('comentarios', $defineCollection);
    }

    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('comentarios');
    }
};
