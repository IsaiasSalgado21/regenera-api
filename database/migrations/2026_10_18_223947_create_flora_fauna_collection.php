<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        if (!Schema::hasCollection('floraFauna')) {
            Schema::create('floraFauna', function (Blueprint $collection) {
                // Creación de colección sin índices adicionales por restricción de espacio inicial.
            });

        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flora_fauna_collection');
    }
};
