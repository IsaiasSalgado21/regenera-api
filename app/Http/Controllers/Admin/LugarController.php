<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lugar;
use App\Services\AuditoriaService;
use Illuminate\Http\Request;

class LugarController extends Controller
{
    public function __construct(private readonly AuditoriaService $auditoria) {}

    public function index()
    {
        return response()->json([
            'data' => Lugar::query()->orderBy('nombre')->get()->map(fn (Lugar $lugar) => [
                'id' => (string) $lugar->_id,
                'nombre' => $lugar->nombre,
                'descripcion' => $lugar->descripcion,
                'municipio' => $lugar->municipio,
                'direccion' => $lugar->direccion,
                'latitud' => $lugar->latitud,
                'longitud' => $lugar->longitud,
                'tipo' => $lugar->tipo,
                'estado' => $lugar->estado,
                'creadoPor' => (string) $lugar->creadoPor,
            ])->values(),
            'message' => 'Todos los lugares consultados correctamente.',
        ]);
    }

    public function archivar(Request $request, string $id)
    {
        $lugar = Lugar::findOrFail($id);
        $this->authorize('archivar', $lugar);

        if ($lugar->estado !== 'archivado') {
            $estadoAnterior = $lugar->estado;
            $lugar->update(['estado' => 'archivado']);
            $this->auditoria->registrar(
                $request->user(),
                'archivar',
                'lugar',
                (string) $lugar->_id,
                $request,
                ['estado_anterior' => $estadoAnterior],
            );
        }

        return response()->json([
            'data' => ['id' => (string) $lugar->_id, 'estado' => $lugar->estado],
            'message' => 'Lugar archivado correctamente.',
        ]);
    }
}
