<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        DB::connection('mongodb')->getMongoDB()->selectCollection('medallas')
            ->createIndex(['slug' => 1], ['unique' => true, 'name' => 'medallas_slug_unique']);
    }

    public function down(): void
    {
        DB::connection('mongodb')->getMongoDB()->selectCollection('medallas')
            ->dropIndex('medallas_slug_unique');
    }
};
