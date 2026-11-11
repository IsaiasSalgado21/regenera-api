<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'mongodb';

    private const ALFABETO = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    public function up(): void
    {
        $voluntariados = DB::connection('mongodb')->getMongoDB()->selectCollection('voluntariados');

        foreach ($voluntariados->find([]) as $voluntariado) {
            if (isset($voluntariado['codigoCorto']) && is_string($voluntariado['codigoCorto']) && $voluntariado['codigoCorto'] !== '') {
                continue;
            }

            do {
                $codigo = $this->generarCodigo();
            } while ($voluntariados->findOne(['codigoCorto' => $codigo]) !== null);

            $voluntariados->updateOne(
                ['_id' => $voluntariado['_id']],
                ['$set' => ['codigoCorto' => $codigo]],
            );
        }

        $voluntariados->createIndex(
            ['codigoCorto' => 1],
            ['name' => 'voluntariados_codigo_corto_unique', 'unique' => true],
        );
    }

    public function down(): void
    {
        DB::connection('mongodb')->getMongoDB()
            ->selectCollection('voluntariados')
            ->dropIndex('voluntariados_codigo_corto_unique');
    }

    private function generarCodigo(): string
    {
        $codigo = '';
        for ($i = 0; $i < 8; $i++) {
            $codigo .= self::ALFABETO[random_int(0, strlen(self::ALFABETO) - 1)];
        }

        return $codigo;
    }
};
