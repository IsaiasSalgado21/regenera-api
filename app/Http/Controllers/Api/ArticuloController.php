<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreArticuloRequest;
use App\Http\Requests\UpdateArticuloRequest;
use App\Models\Articulo;
use App\Models\User;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use MongoDB\BSON\ObjectId;

class ArticuloController extends Controller
{
    public function __construct(private readonly AuditoriaService $auditoria) {}

    public function index(Request $request)
    {
        $usuario = $request->user();
        $query = Articulo::query();

        if ($usuario?->rol === 'admin') {
            // Admin sees every article state.
        } elseif ($usuario) {
            $autorId = (string) $usuario->getAuthIdentifier();
            $query->where(function ($articulos) use ($autorId): void {
                $articulos->where('estado', 'publicado')
                    ->orWhere('autorId', $autorId);

                if (preg_match('/^[a-f0-9]{24}$/i', $autorId)) {
                    $articulos->orWhere('autorId', new ObjectId($autorId));
                }
            });
        } else {
            $query->where('estado', 'publicado');
        }

        $data = $query->orderBy('fechaActualizacion', 'desc')
            ->get()
            ->map(fn (Articulo $articulo) => $this->present($articulo))
            ->values();

        return response()->json([
            'data' => $data,
            'message' => 'Artículos consultados correctamente.',
        ]);
    }

    public function store(StoreArticuloRequest $request)
    {
        $this->authorize('create', Articulo::class);
        $validated = $request->validated();
        $autorId = (string) $request->user()->getAuthIdentifier();

        $articulo = Articulo::create([
            ...$validated,
            'autorId' => preg_match('/^[a-f0-9]{24}$/i', $autorId) ? new ObjectId($autorId) : $autorId,
            'estado' => 'borrador',
            'fechaPublicacion' => null,
            'fechaActualizacion' => now(),
        ]);

        if ($request->user()->rol === 'admin') {
            $this->auditoria->registrar(
                $request->user(),
                'crear',
                'articulo',
                (string) $articulo->_id,
                $request,
                ['estado' => 'borrador'],
            );
        }

        return response()->json([
            'data' => $this->present($articulo),
            'message' => 'Artículo guardado como borrador.',
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $articulo = Articulo::findOrFail($id);
        $this->authorize('view', $articulo);

        return response()->json([
            'data' => $this->present($articulo),
            'message' => 'Artículo consultado correctamente.',
        ]);
    }

    public function update(UpdateArticuloRequest $request, string $id)
    {
        $articulo = Articulo::findOrFail($id);
        $this->authorize('update', $articulo);
        $articulo->update([
            ...$request->validated(),
            'fechaActualizacion' => now(),
        ]);

        if ($request->user()->rol === 'admin') {
            $this->auditoria->registrar(
                $request->user(),
                'actualizar',
                'articulo',
                (string) $articulo->_id,
                $request,
                ['campos' => array_keys($request->validated()), 'estado' => $articulo->estado],
            );
        }

        return response()->json([
            'data' => $this->present($articulo->fresh()),
            'message' => 'Artículo actualizado correctamente.',
        ]);
    }

    public function enviarRevision(Request $request, string $id)
    {
        $articulo = Articulo::findOrFail($id);
        $this->authorize('enviarRevision', $articulo);
        $articulo->update([
            'estado' => 'pendiente',
            'fechaActualizacion' => now(),
        ]);

        return response()->json([
            'data' => $this->present($articulo->fresh()),
            'message' => 'Artículo enviado a revisión.',
        ]);
    }

    private function present(Articulo $articulo): array
    {
        $autorId = (string) $articulo->autorId;
        $autor = preg_match('/^[a-f0-9]{24}$/i', $autorId) ? User::find($autorId) : null;

        return [
            'id' => (string) $articulo->_id,
            'autorId' => $autorId,
            'autorNombre' => $autor?->nombre,
            'titulo' => $articulo->titulo,
            'contenido' => $articulo->contenido,
            'imagen' => $articulo->imagen,
            'categoria' => $articulo->categoria,
            'estado' => $articulo->estado,
            'motivoRechazo' => $articulo->motivoRechazo,
            'fechaPublicacion' => $articulo->fechaPublicacion?->toISOString(),
            'fechaActualizacion' => $articulo->fechaActualizacion?->toISOString(),
        ];
    }
}
