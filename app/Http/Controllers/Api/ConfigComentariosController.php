<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateConfigComentariosRequest;
use App\Models\Voluntariado;
use App\Services\AuditoriaService;
use Illuminate\Http\JsonResponse;

class ConfigComentariosController extends Controller
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
    ) {}

    public function update(
        UpdateConfigComentariosRequest $request,
        Voluntariado $voluntariado,
    ): JsonResponse {
        $usuario = $request->user();
        abort_unless(
            $usuario->rol === 'admin'
                || ($usuario->rol === 'organizador'
                    && (string) $voluntariado->organizadorId === (string) $usuario->getAuthIdentifier()),
            403,
            'Solo el organizador propietario o un administrador puede configurar los comentarios.',
        );

        $anterior = $voluntariado->comentarios_por_defecto ?? 'publico';
        $voluntariado->update($request->validated());

        $this->auditoria->registrar(
            $usuario,
            'configurar_comentarios',
            'voluntariado',
            (string) $voluntariado->getKey(),
            $request,
            [
                'comentarios_por_defecto_anterior' => $anterior,
                'comentarios_por_defecto' => $voluntariado->comentarios_por_defecto,
            ],
        );

        return response()->json([
            'data' => [
                'voluntariado_id' => (string) $voluntariado->getKey(),
                'comentarios_por_defecto' => $voluntariado->comentarios_por_defecto,
            ],
            'message' => 'Configuración de comentarios actualizada correctamente.',
        ]);
    }
}
