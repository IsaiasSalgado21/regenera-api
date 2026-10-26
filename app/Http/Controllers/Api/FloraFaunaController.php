<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFloraFaunaRequest;
use App\Http\Requests\UpdateFloraFaunaRequest;
use App\Models\FloraFauna;
use App\Services\AuditoriaService;
use App\Services\FloraFaunaService;
use Illuminate\Http\Request;
use MongoDB\BSON\ObjectId;

class FloraFaunaController extends Controller
{
    public function __construct(
        private readonly FloraFaunaService $floraFaunaService,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function index(Request $request)
    {
        $usuario = $request->user();
        $query = FloraFauna::query();

        if ($usuario?->rol === 'organizador') {
            $creadorId = new ObjectId((string) $usuario->getAuthIdentifier());
            $query->where(function ($registros) use ($creadorId): void {
                $registros->where('estado', 'publicado')
                    ->orWhere('creadoPor', $creadorId);
            });
        } else {
            $query->where('estado', 'publicado');
        }

        $data = $query->orderBy('nombre')
            ->get()
            ->map(fn (FloraFauna $registro) => $this->floraFaunaService->present(
                $registro,
                false,
                (string) $registro->creadoPor === (string) $usuario?->getAuthIdentifier(),
            ))
            ->values();

        return response()->json([
            'data' => $data,
            'message' => 'Registros de flora y fauna consultados correctamente.',
        ]);
    }

    public function store(StoreFloraFaunaRequest $request)
    {
        $this->authorize('create', FloraFauna::class);
        $validated = $this->convertLugarIds($request->validated());

        // Flora/fauna is published on creation; this phase has no review workflow.
        $registro = FloraFauna::create([
            ...$validated,
            'estado' => 'publicado',
            'creadoPor' => new ObjectId((string) $request->user()->getAuthIdentifier()),
        ]);

        if ($request->user()->rol === 'admin') {
            $this->auditoria->registrar(
                $request->user(),
                'crear',
                'flora_fauna',
                (string) $registro->_id,
                $request,
                ['campos' => array_keys($validated)],
            );
        }

        return response()->json([
            'data' => $this->floraFaunaService->present(
                $registro,
                $request->user()->rol === 'admin',
                $request->user()->rol === 'organizador',
            ),
            'message' => 'Registro de flora o fauna creado correctamente.',
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $registro = FloraFauna::findOrFail($id);
        $this->authorize('view', $registro);

        return response()->json([
            'data' => $this->floraFaunaService->present($registro, $request->user()?->rol === 'admin'),
            'message' => 'Registro de flora o fauna consultado correctamente.',
        ]);
    }

    public function update(UpdateFloraFaunaRequest $request, string $id)
    {
        $registro = FloraFauna::findOrFail($id);
        $this->authorize('update', $registro);
        $validated = $this->convertLugarIds($request->validated());
        $registro->update($validated);

        if ($request->user()->rol === 'admin') {
            $this->auditoria->registrar(
                $request->user(),
                'actualizar',
                'flora_fauna',
                (string) $registro->_id,
                $request,
                ['campos' => array_keys($validated)],
            );
        }

        return response()->json([
            'data' => $this->floraFaunaService->present(
                $registro->fresh(),
                $request->user()->rol === 'admin',
                $request->user()->rol === 'organizador',
            ),
            'message' => 'Registro de flora o fauna actualizado correctamente.',
        ]);
    }

    private function convertLugarIds(array $attributes): array
    {
        if (isset($attributes['lugaresRelacionados'])) {
            $attributes['lugaresRelacionados'] = array_map(
                fn (string $id) => new ObjectId($id),
                $attributes['lugaresRelacionados'],
            );
        }

        return $attributes;
    }
}
