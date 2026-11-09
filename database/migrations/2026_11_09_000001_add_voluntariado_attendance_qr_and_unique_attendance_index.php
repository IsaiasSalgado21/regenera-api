<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use MongoDB\BSON\ObjectId;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        $database = DB::connection('mongodb')->getMongoDB();
        $voluntariados = $database->selectCollection('voluntariados');

        foreach ($voluntariados->find([]) as $voluntariado) {
            $actualizaciones = [];
            $token = $voluntariado['tokenAsistencia'] ?? null;
            if (! is_string($token) || $token === '') {
                do {
                    $token = Str::random(64);
                } while ($voluntariados->findOne(['tokenAsistencia' => $token]) !== null);
                $actualizaciones['tokenAsistencia'] = $token;
            }
            if (! isset($voluntariado['codigoAsistenciaActivo'])) {
                $actualizaciones['codigoAsistenciaActivo'] = true;
            }

            if ($actualizaciones !== []) {
                $voluntariados->updateOne(
                    ['_id' => $voluntariado['_id']],
                    ['$set' => $actualizaciones],
                );
            }
        }

        $duplicateTokens = $voluntariados->aggregate([
            ['$group' => [
                '_id' => '$tokenAsistencia',
                'count' => ['$sum' => 1],
            ]],
            ['$match' => ['count' => ['$gt' => 1]]],
            ['$limit' => 1],
        ])->toArray();

        if ($duplicateTokens !== []) {
            throw new RuntimeException('Hay tokens de asistencia repetidos en voluntariados; corrígelos antes de crear el índice único.');
        }

        $asistencias = $database->selectCollection('asistencias');
        foreach ($asistencias->find([]) as $asistencia) {
            $identificadores = [];
            foreach (['usuarioId', 'voluntariadoId'] as $campo) {
                if (! isset($asistencia[$campo])) {
                    throw new RuntimeException("Falta {$campo} en una asistencia; no se puede crear el índice de unicidad.");
                }

                $identificador = $asistencia[$campo];
                if ($identificador instanceof ObjectId) {
                    $identificador = (string) $identificador;
                }
                if (! is_string($identificador) || $identificador === '') {
                    throw new RuntimeException("El campo {$campo} de una asistencia debe ser un identificador de texto válido.");
                }

                $identificadores[$campo] = $identificador;
            }

            if (
                $identificadores['usuarioId'] !== $asistencia['usuarioId']
                || $identificadores['voluntariadoId'] !== $asistencia['voluntariadoId']
            ) {
                $asistencias->updateOne(
                    ['_id' => $asistencia['_id']],
                    ['$set' => $identificadores],
                );
            }
        }

        $duplicateAttendance = $asistencias->aggregate([
            ['$group' => [
                '_id' => [
                    'usuarioId' => '$usuarioId',
                    'voluntariadoId' => '$voluntariadoId',
                ],
                'count' => ['$sum' => 1],
            ]],
            ['$match' => ['count' => ['$gt' => 1]]],
            ['$limit' => 1],
        ])->toArray();

        if ($duplicateAttendance !== []) {
            throw new RuntimeException('Hay asistencias duplicadas por usuario y voluntariado; resuélvelas antes de crear el índice único.');
        }

        $voluntariados->createIndex(
            ['tokenAsistencia' => 1],
            ['unique' => true, 'name' => 'voluntariados_token_asistencia_unique'],
        );
        $asistencias->createIndex(
            ['usuarioId' => 1, 'voluntariadoId' => 1],
            ['unique' => true, 'name' => 'asistencias_usuario_voluntariado_unique'],
        );
    }

    public function down(): void
    {
        $database = DB::connection('mongodb')->getMongoDB();
        $database->selectCollection('voluntariados')->dropIndex('voluntariados_token_asistencia_unique');
        $database->selectCollection('asistencias')->dropIndex('asistencias_usuario_voluntariado_unique');
    }
};
