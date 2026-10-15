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
        if (!Schema::hasCollection('lugares')) {
            Schema::create('lugares', function (Blueprint $collection) {
                $collection->index('nombre');
                $collection->index('municipio');
                $collection->index('tipo');
                $collection->index('estado');
            });

            return;
        }

        Schema::table('lugares', function (Blueprint $collection) {
            $collection->index('nombre');
            $collection->index('municipio');
            $collection->index('tipo');
            $collection->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lugares_collection');
    }
};
