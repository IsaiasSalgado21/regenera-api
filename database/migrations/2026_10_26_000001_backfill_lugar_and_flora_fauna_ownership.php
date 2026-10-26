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
        $lugares = $database->selectCollection('lugares');

        foreach ($lugares->find(['creadoPor' => ['$type' => 'string']]) as $lugar) {
            $creadorId = $lugar['creadoPor'];

            if (! is_string($creadorId) || ! preg_match('/^[a-f0-9]{24}$/i', $creadorId)) {
                throw new RuntimeException('No se puede convertir creadoPor a ObjectId en el documento '.$lugar['_id']);
            }

            $lugares->updateOne(
                ['_id' => $lugar['_id']],
                ['$set' => ['creadoPor' => new ObjectId($creadorId)]],
            );
        }

        $lugares->updateMany(
            ['creadoPor' => ['$exists' => false]],
            ['$set' => ['creadoPor' => null]],
        );

        $floraFauna = $database->selectCollection('floraFauna');
        $floraFauna->updateMany(
            ['estado' => ['$exists' => false]],
            ['$set' => ['estado' => 'publicado']],
        );
        $floraFauna->updateMany(
            ['creadoPor' => ['$exists' => false]],
            ['$set' => ['creadoPor' => null]],
        );

        foreach ($floraFauna->find([]) as $registro) {
            $actualizaciones = [];

            foreach (['ubicacion', 'imagenes', 'lugaresRelacionados'] as $campo) {
                if (! isset($registro[$campo])) {
                    continue;
                }

                $valor = $registro[$campo];

                if (is_string($valor)) {
                    try {
                        $valor = json_decode($valor, true, 512, JSON_THROW_ON_ERROR);
                    } catch (JsonException $exception) {
                        throw new RuntimeException(
                            "No se puede convertir {$campo} a un arreglo BSON en el documento ".$registro['_id'],
                            previous: $exception,
                        );
                    }
                } elseif ($valor instanceof Traversable) {
                    $valor = iterator_to_array($valor);
                } elseif (is_object($valor)) {
                    $valor = get_object_vars($valor);
                }

                if (! is_array($valor)) {
                    throw new RuntimeException("El campo {$campo} debe ser un arreglo en el documento ".$registro['_id']);
                }

                if ($campo === 'lugaresRelacionados') {
                    $valor = array_map(function (mixed $lugarId) use ($registro): ObjectId {
                        if ($lugarId instanceof ObjectId) {
                            return $lugarId;
                        }

                        if (! is_string($lugarId) || ! preg_match('/^[a-f0-9]{24}$/i', $lugarId)) {
                            throw new RuntimeException(
                                'No se puede convertir lugaresRelacionados a ObjectId en el documento '.$registro['_id'],
                            );
                        }

                        return new ObjectId($lugarId);
                    }, $valor);
                }

                $actualizaciones[$campo] = $valor;
            }

            if ($actualizaciones !== []) {
                $floraFauna->updateOne(
                    ['_id' => $registro['_id']],
                    ['$set' => $actualizaciones],
                );
            }
        }
    }

    public function down(): void
    {
        // Ownership and publication state are data backfills and cannot be safely rolled back.
    }
};
