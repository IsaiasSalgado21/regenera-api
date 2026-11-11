<?php

namespace App\Services;

use App\Models\Voluntariado;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\ObjectId;

class VoluntariadoCupoService
{
    public function reservar(Voluntariado $voluntariado, string $inscripcionId, bool $requierePublicado = true): bool
    {
        $collection = DB::connection('mongodb')->getMongoDB()->selectCollection('voluntariados');
        $filter = [
            '_id' => $this->mongoId($voluntariado->getKey()),
            'reservasCupo' => ['$ne' => $inscripcionId],
            '$expr' => ['$lt' => ['$inscritosActivos', '$cupo']],
        ];
        if ($requierePublicado) {
            $filter['estado'] = 'publicado';
        }

        $result = $collection->updateOne(
            $filter,
            [
                '$inc' => ['inscritosActivos' => 1],
                '$addToSet' => ['reservasCupo' => $inscripcionId],
            ],
        );

        return $result->getModifiedCount() === 1
            || $collection->findOne([
                '_id' => $this->mongoId($voluntariado->getKey()),
                'reservasCupo' => $inscripcionId,
            ]) !== null;
    }

    public function liberar(Voluntariado $voluntariado, string $inscripcionId): bool
    {
        $result = DB::connection('mongodb')->getMongoDB()->selectCollection('voluntariados')->updateOne(
            [
                '_id' => $this->mongoId($voluntariado->getKey()),
                'reservasCupo' => $inscripcionId,
                'inscritosActivos' => ['$gt' => 0],
            ],
            [
                '$inc' => ['inscritosActivos' => -1],
                '$pull' => ['reservasCupo' => $inscripcionId],
            ],
        );

        return $result->getModifiedCount() === 1;
    }

    public function actualizarCupo(Voluntariado $voluntariado, int $cupo, array $attributes = []): bool
    {
        unset($attributes['cupo'], $attributes['inscritosActivos'], $attributes['reservasCupo']);
        $attributes['cupo'] = $cupo;
        $attributes['updated_at'] = now();

        $result = DB::connection('mongodb')->getMongoDB()->selectCollection('voluntariados')->updateOne(
            [
                '_id' => $this->mongoId($voluntariado->getKey()),
                'inscritosActivos' => ['$lte' => $cupo],
            ],
            ['$set' => $attributes],
        );

        return $result->getMatchedCount() === 1;
    }

    private function mongoId(mixed $id): mixed
    {
        return is_string($id) && preg_match('/^[a-f0-9]{24}$/i', $id)
            ? new ObjectId($id)
            : $id;
    }
}