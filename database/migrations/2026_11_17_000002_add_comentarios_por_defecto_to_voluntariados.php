<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        if (! Schema::connection('mongodb')->hasCollection('voluntariados')) {
            return;
        }

        DB::connection('mongodb')->getMongoDB()
            ->selectCollection('voluntariados')
            ->updateMany(
                ['comentarios_por_defecto' => ['$exists' => false]],
                ['$set' => ['comentarios_por_defecto' => 'publico']],
            );
    }

    public function down(): void
    {
        if (! Schema::connection('mongodb')->hasCollection('voluntariados')) {
            return;
        }

        DB::connection('mongodb')->getMongoDB()
            ->selectCollection('voluntariados')
            ->updateMany([], ['$unset' => ['comentarios_por_defecto' => '']]);
    }
};
