<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lugar;
use App\Services\AuditoriaService;
use App\Services\BajaCaliforniaSurBoundary;
use App\Services\GridFsImageService;
use Illuminate\Http\Request;

class LugarController extends Controller
{
    private readonly BajaCaliforniaSurBoundary $boundary;

    public function __construct(
        private readonly AuditoriaService $auditoria,
        BajaCaliforniaSurBoundary $boundary,
        private readonly GridFsImageService $gridFsImages,
    ) {
        $this->boundary = $boundary;
    }

    public function index()
    {
        return response()->json([
            'data' => Lugar::query()->orderBy('nombre')->get()->map(function (Lugar $lugar): array {
                $hasValidCoordinates = $this->boundary->contains($lugar->latitud, $lugar->longitud);

                return [
                    'id' => (string) $lugar->_id,
                    'nombre' => $lugar->nombre,
                    'descripcion' => $lugar->descripcion,
                    'municipio' => $lugar->municipio,
                    'direccion' => $lugar->direccion,
                    'latitud' => $hasValidCoordinates ? $lugar->latitud : null,
                    'longitud' => $hasValidCoordinates ? $lugar->longitud : null,
                    'tipo' => $lugar->tipo,
                    'imagen' => $this->gridFsImages->url($lugar->imagen),
                    'estado' => $lugar->estado,
                    'propuesto' => (bool) $lugar->propuesto,
                    'motivoRechazo' => $lugar->motivoRechazo,
                    'creadoPor' => (string) $lugar->creadoPor,
                ];
            })->values(),
            'message' => 'Todos los lugares consultados correctamente.',
        ]);
    }

    public function aprobar(Request $request, string $id)
    {
        $lugar = Lugar::findOrFail($id);
        $this->authorize('revisar', $lugar);
        if (! $this->boundary->contains($lugar->latitud, $lugar->longitud)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'latitud' => 'El lugar debe tener coordenadas dentro de Baja California Sur.',
            ]);
        }
        $lugar->update(['estado' => 'activo', 'motivoRechazo' => null]);
        $this->auditoria->registrar($request->user(), 'aprobar', 'lugar', (string) $lugar->_id, $request);

        return response()->json(['data' => ['id' => (string) $lugar->_id, 'estado' => $lugar->estado], 'message' => 'Lugar aprobado correctamente.']);
    }

    public function rechazar(Request $request, string $id)
    {
        $lugar = Lugar::findOrFail($id);
        $this->authorize('revisar', $lugar);
        $validated = $request->validate(['motivo' => ['required', 'string', 'max:2000']]);
        $lugar->update(['estado' => 'rechazado', 'motivoRechazo' => $validated['motivo']]);
        $this->auditoria->registrar($request->user(), 'rechazar', 'lugar', (string) $lugar->_id, $request, ['motivo' => $validated['motivo']]);

        return response()->json(['data' => ['id' => (string) $lugar->_id, 'estado' => $lugar->estado], 'message' => 'Lugar rechazado correctamente.']);
    }
    public function publicar(string $id)
    {
        $lugar = Lugar::findOrFail($id);

        $this->authorize('publicar', $lugar);

        $lugar->estado = 'activo';
        $lugar->save();

        app(AuditoriaService::class)->registrar(
            auth()->user(),
            'publicar',
            'lugar',
            (string) $lugar->id,
            request()
        );

        return response()->json([
            'message' => 'Lugar publicado correctamente.',
            'data' => [
                'id' => (string) $lugar->_id,
                'estado' => $lugar->estado,
            ],
        ]);
    }
    public function archivar(Request $request, string $id)
    {
        $lugar = Lugar::findOrFail($id);
        $this->authorize('archivar', $lugar);
        abort_if(in_array($lugar->estado, ['pendiente_de_aprobacion', 'rechazado'], true), 422, 'Solo se pueden archivar lugares aprobados.');

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
