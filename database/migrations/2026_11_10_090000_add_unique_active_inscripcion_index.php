<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\ObjectId;

return new class extends Migration
{
    protected $connection = 'mongodb';

    public function up(): void
    {
        $collection = DB::connection('mongodb')->getMongoDB()->selectCollection('inscripciones');
        $registrations = [];
        $activeKeys = [];

        foreach ($collection->find([]) as $registration) {
            $userId = $this->normalizeId($registration['usuarioId'] ?? null, 'usuarioId');
            $volunteerId = $this->normalizeId($registration['voluntariadoId'] ?? null, 'voluntariadoId');
            $estado = $registration['estado'] ?? null;
            $key = null;

            if ($estado !== 'cancelada') {
                $key = $userId.'|'.$volunteerId;
                if (isset($activeKeys[$key])) {
                    throw new RuntimeException(
                        'Hay inscripciones activas duplicadas para usuario y voluntariado; resuélvelas antes de crear el índice único.',
                    );
                }
                $activeKeys[$key] = true;
            }

            $registrations[] = [
                'id' => $registration['_id'],
                'usuarioId' => $userId,
                'voluntariadoId' => $volunteerId,
                'claveRegistroActiva' => $key,
            ];
        }

        foreach ($registrations as $registration) {
            $update = ['$set' => [
                'usuarioId' => $registration['usuarioId'],
                'voluntariadoId' => $registration['voluntariadoId'],
            ]];
            if ($registration['claveRegistroActiva'] === null) {
                $update['$unset'] = ['claveRegistroActiva' => ''];
            } else {
                $update['$set']['claveRegistroActiva'] = $registration['claveRegistroActiva'];
            }

            $collection->updateOne(['_id' => $registration['id']], $update);
        }

        $collection->createIndex(
            ['claveRegistroActiva' => 1],
            [
                'unique' => true,
                'name' => 'inscripciones_active_registration_unique',
                'partialFilterExpression' => ['claveRegistroActiva' => ['$type' => 'string']],
            ],
        );
    }

    public function down(): void
    {
        DB::connection('mongodb')
            ->getMongoDB()
            ->selectCollection('inscripciones')
            ->dropIndex('inscripciones_active_registration_unique');
    }

    private function normalizeId(mixed $value, string $field): string
    {
        if ($value instanceof ObjectId) {
            return (string) $value;
        }

        if (is_string($value) && $value !== '') {
            return $value;
        }

        throw new RuntimeException("El campo {$field} de una inscripción debe ser un identificador válido.");
    }
};