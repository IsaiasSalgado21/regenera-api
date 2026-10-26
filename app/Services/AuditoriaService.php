<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Http\Request;
use MongoDB\BSON\ObjectId;

class AuditoriaService
{
    public function registrar(
        User $usuario,
        string $accion,
        string $entidad,
        string $entidadId,
        Request $request,
        array $detalles = [],
    ): Auditoria {
        return Auditoria::create([
            'usuarioId' => new ObjectId((string) $usuario->getAuthIdentifier()),
            'accion' => $accion,
            'entidad' => $entidad,
            'entidadId' => new ObjectId($entidadId),
            'fecha' => now(),
            'ip' => $request->ip(),
            'detalles' => $detalles,
        ]);
    }
}
