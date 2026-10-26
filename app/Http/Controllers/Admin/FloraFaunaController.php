<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FloraFauna;
use App\Services\AuditoriaService;
use App\Services\FloraFaunaService;
use Illuminate\Http\Request;

class FloraFaunaController extends Controller
{
    public function __construct(
        private readonly FloraFaunaService $floraFaunaService,
        private readonly AuditoriaService $auditoria,
    ) {}

    public function index()
    {
        $data = FloraFauna::query()
            ->orderBy('nombre')
            ->get()
            ->map(fn (FloraFauna $registro) => $this->floraFaunaService->present($registro, true))
            ->values();

        return response()->json([
            'data' => $data,
            'message' => 'Todos los registros de flora y fauna consultados correctamente.',
        ]);
    }

    public function archivar(Request $request, string $id)
    {
        $registro = FloraFauna::findOrFail($id);
        $this->authorize('archivar', $registro);

        if ($registro->estado !== 'archivado') {
            $estadoAnterior = $registro->estado;
            $registro->update(['estado' => 'archivado']);
            $this->auditoria->registrar(
                $request->user(),
                'archivar',
                'flora_fauna',
                (string) $registro->_id,
                $request,
                ['estado_anterior' => $estadoAnterior],
            );
        }

        return response()->json([
            'data' => ['id' => (string) $registro->_id, 'estado' => $registro->estado],
            'message' => 'Registro de flora o fauna archivado correctamente.',
        ]);
    }
}
