<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\ObjectId;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        $database = DB::connection('mongodb')->getMongoDB();
        $voluntariados = $database->selectCollection('voluntariados');
        $inscripciones = $database->selectCollection('inscripciones');

        foreach ($voluntariados->find([], ['projection' => ['_id' => 1]]) as $voluntariado) {
            $ids = [(string) $voluntariado['_id']];
            if ($voluntariado['_id'] instanceof ObjectId) {
                $ids[] = $voluntariado['_id'];
            }

            $activas = $inscripciones->find([
                'voluntariadoId' => ['$in' => $ids],
                'estado' => ['$in' => ['inscrita', 'activa']],
            ], ['projection' => ['_id' => 1]]);
            $reservasCupo = array_map(fn ($inscripcion) => (string) $inscripcion['_id'], $activas->toArray());

            $voluntariados->updateOne(
                ['_id' => $voluntariado['_id']],
                ['$set' => [
                    'inscritosActivos' => count($reservasCupo),
                    'reservasCupo' => $reservasCupo,
                ]],
            );
        }
    }

    public function down(): void
    {
        DB::connection('mongodb')->getMongoDB()
            ->selectCollection('voluntariados')
            ->updateMany([], ['$unset' => ['inscritosActivos' => '', 'reservasCupo' => '']]);
    }
};