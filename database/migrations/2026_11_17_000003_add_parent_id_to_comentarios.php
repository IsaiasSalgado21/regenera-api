<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        if (! Schema::connection('mongodb')->hasCollection('comentarios')) {
            return;
        }

        Schema::connection('mongodb')->table('comentarios', static function (Blueprint $collection): void {
            $collection->index('parent_id');
        });
    }

    public function down(): void
    {
        if (! Schema::connection('mongodb')->hasCollection('comentarios')) {
            return;
        }

        Schema::connection('mongodb')->table('comentarios', static function (Blueprint $collection): void {
            $collection->dropIndexIfExists('parent_id');
        });
    }
};
