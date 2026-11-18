<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComentarioRequest;
use App\Http\Requests\UpdateVisibilidadComentarioRequest;
use App\Models\Comentario;
use App\Models\User;
use App\Models\Voluntariado;
use App\Services\AuditoriaService;
use App\Services\ComentarioService;
use App\Services\GridFsImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComentarioController extends Controller
{
    public function __construct(
        private readonly ComentarioService $comentarios,
        private readonly AuditoriaService $auditoria,
        private readonly GridFsImageService $gridFsImages,
    ) {}

    public function index(Request $request, Voluntariado $voluntariado): JsonResponse
    {
        $usuario = $request->user();
        $this->authorize('viewAny', [Comentario::class, $voluntariado]);
        $data = $this->comentarios
            ->listarParaUsuario($voluntariado, $usuario)
            ->map(fn (Comentario $comentario) => $this->presentar($comentario, $usuario))
            ->values();

        return response()->json([
            'data' => $data,
            'message' => 'Comentarios consultados correctamente.',
        ]);
    }

    public function store(StoreComentarioRequest $request, Voluntariado $voluntariado): JsonResponse
    {
        $usuario = $request->user();
        $this->authorize('create', [Comentario::class, $voluntariado]);
        $comentario = $this->comentarios->crear($voluntariado, $usuario, $request->validated());

        return response()->json([
            'data' => $this->presentar($comentario, $usuario),
            'message' => 'Comentario creado correctamente.',
        ], 201);
    }

    public function responder(StoreComentarioRequest $request, Comentario $comentario): JsonResponse
    {
        $usuario = $request->user();
        $this->authorize('moderar', $comentario);
        $respuesta = $this->comentarios->responder(
            $comentario,
            $usuario,
            $request->validated()['contenido'],
        );

        return response()->json([
            'data' => $this->presentar($respuesta, $usuario),
            'message' => 'Respuesta creada correctamente.',
        ], 201);
    }

    public function updateVisibilidad(
        UpdateVisibilidadComentarioRequest $request,
        Comentario $comentario,
    ): JsonResponse {
        $this->authorize('moderar', $comentario);
        $anterior = (bool) $comentario->es_publicado;
        $comentario = $this->comentarios->cambiarVisibilidad(
            $comentario,
            $request->boolean('es_publicado'),
        );

        $this->auditoria->registrar(
            $request->user(),
            'moderar_comentario',
            'comentario',
            (string) $comentario->getKey(),
            $request,
            [
                'es_publicado_anterior' => $anterior,
                'es_publicado' => (bool) $comentario->es_publicado,
            ],
        );

        return response()->json([
            'data' => $this->presentar($comentario, $request->user()),
            'message' => 'Visibilidad del comentario actualizada correctamente.',
        ]);
    }

    public function publicar(Request $request, Comentario $comentario): JsonResponse
    {
        $this->authorize('publicarDuda', $comentario);
        $comentario = $this->comentarios->publicarDudaPrivada($comentario);

        $this->auditoria->registrar(
            $request->user(),
            'publicar_duda_privada',
            'comentario',
            (string) $comentario->getKey(),
            $request,
        );

        return response()->json([
            'data' => $this->presentar($comentario, $request->user()),
            'message' => 'Duda publicada correctamente.',
        ]);
    }

    public function destroy(Request $request, Comentario $comentario): JsonResponse
    {
        $this->authorize('delete', $comentario);
        $comentarioId = (string) $comentario->getKey();
        $this->comentarios->eliminar($comentario);

        $this->auditoria->registrar(
            $request->user(),
            'eliminar_comentario',
            'comentario',
            $comentarioId,
            $request,
        );

        return response()->json([
            'message' => 'Comentario eliminado correctamente.',
        ]);
    }

    private function presentar(Comentario $comentario, User $usuario): array
    {
        $comentario->loadMissing('user');
        $autor = $comentario->user;

        return [
            'id' => (string) $comentario->getKey(),
            'parent_id' => $comentario->parent_id === null ? null : (string) $comentario->parent_id,
            'contenido' => $comentario->contenido,
            'es_privado' => (bool) $comentario->es_privado,
            'es_publicado' => (bool) $comentario->es_publicado,
            'created_at' => $comentario->created_at?->toIso8601String(),
            'autor' => [
                'nombre' => $autor?->nombre,
                'rol' => $autor?->rol,
                'foto' => $this->gridFsImages->url($autor?->foto),
            ],
            'puede_moderar' => $usuario->can('moderar', $comentario),
            'puede_publicar' => $usuario->can('publicarDuda', $comentario),
            'puede_eliminar' => $usuario->can('delete', $comentario),
        ];
    }
}
