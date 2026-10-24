<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use MongoDB\Laravel\Schema\Blueprint;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        if (! Schema::connection('mongodb')->hasCollection('personal_access_tokens')) {
            Schema::connection('mongodb')->create('personal_access_tokens', function (Blueprint $collection) {
                $collection->index('token');
                $collection->index(['tokenable_type', 'tokenable_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::connection('mongodb')->dropIfExists('personal_access_tokens');
    }
};
