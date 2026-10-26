<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLugarRequest;
use App\Http\Requests\UpdateLugarRequest;
use App\Models\Lugar;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;
use MongoDB\BSON\ObjectId;

class LugarController extends Controller
{
    public function __construct(private readonly AuditoriaService $auditoria) {}

    public function index(Request $request)
    {
        $query = Lugar::query();
        $usuario = $request->user();

        if ($usuario?->rol === 'organizador') {
            $creadorId = new ObjectId((string) $usuario->getAuthIdentifier());
            $query->where(function ($lugares) use ($creadorId): void {
                $lugares->where('estado', 'activo')
                    ->orWhere('creadoPor', $creadorId);
            });
        } else {
            $query->where('estado', 'activo');
        }

        $data = $query->orderBy('nombre')
            ->get()
            ->map(fn (Lugar $lugar) => $this->present($lugar, $usuario?->rol === 'admin'
                || (string) $lugar->creadoPor === (string) $usuario?->getAuthIdentifier()))
            ->values();

        return response()->json([
            'data' => $data,
            'message' => 'Lugares consultados correctamente.',
        ]);
    }

    public function store(StoreLugarRequest $request)
    {
        $this->authorize('create', Lugar::class);

        $lugar = Lugar::create([
            ...$request->validated(),
            'estado' => 'activo',
            'creadoPor' => new ObjectId((string) $request->user()->getAuthIdentifier()),
        ]);

        if ($request->user()->rol === 'admin') {
            $this->auditoria->registrar(
                $request->user(),
                'crear',
                'lugar',
                (string) $lugar->_id,
                $request,
                ['campos' => array_keys($request->validated())],
            );
        }

        return response()->json([
            'data' => $this->present($lugar, true),
            'message' => 'Lugar creado correctamente.',
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $lugar = Lugar::findOrFail($id);
        $this->authorize('view', $lugar);
        $esAdminOPropietario = $request->user()?->rol === 'admin'
            || (string) $lugar->creadoPor === (string) $request->user()?->getAuthIdentifier();

        return response()->json([
            'data' => $this->present($lugar, $esAdminOPropietario),
            'message' => 'Lugar consultado correctamente.',
        ]);
    }

    public function update(UpdateLugarRequest $request, string $id)
    {
        $lugar = Lugar::findOrFail($id);
        $this->authorize('update', $lugar);
        $validated = $request->validated();
        $lugar->update($validated);

        if ($request->user()->rol === 'admin') {
            $this->auditoria->registrar(
                $request->user(),
                'actualizar',
                'lugar',
                (string) $lugar->_id,
                $request,
                ['campos' => array_keys($validated)],
            );
        }

        return response()->json([
            'data' => $this->present($lugar->fresh(), true),
            'message' => 'Lugar actualizado correctamente.',
        ]);
    }

    private function present(Lugar $lugar, bool $mostrarCreador): array
    {
        return [
            'id' => (string) $lugar->_id,
            'nombre' => $lugar->nombre,
            'descripcion' => $lugar->descripcion,
            'municipio' => $lugar->municipio,
            'direccion' => $lugar->direccion,
            'latitud' => $lugar->latitud,
            'longitud' => $lugar->longitud,
            'tipo' => $lugar->tipo,
            'estado' => $lugar->estado,
            'creadoPor' => $mostrarCreador ? (string) $lugar->creadoPor : null,
        ];
    }
}
