<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    protected $connection = 'mongodb';

    public function up(): void
    {
        if (!Schema::hasCollection('voluntariados')) {
            Schema::create('voluntariados', function (Blueprint $collection) {
                $collection->index('titulo');
                $collection->index('lugarId');
                $collection->index('organizadorId');
                $collection->index('fechaInicio');
                $collection->index('estado');
            });

            return;
        }

        Schema::table('voluntariados', function (Blueprint $collection) {
            $collection->index('titulo');
            $collection->index('lugarId');
            $collection->index('organizadorId');
            $collection->index('fechaInicio');
            $collection->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voluntariados_collection');
    }
};
