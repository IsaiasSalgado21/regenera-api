<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RechazarArticuloRequest;
use App\Models\Articulo;
use App\Services\AuditoriaService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ArticuloController extends Controller
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly NotificationService $notificaciones,
    ) {}

    public function pendientes()
    {
        $data = Articulo::query()
            ->where('estado', 'pendiente')
            ->orderBy('fechaActualizacion')
            ->get()
            ->map(fn (Articulo $articulo) => [
                'id' => (string) $articulo->_id,
                'autorId' => (string) $articulo->autorId,
                'titulo' => $articulo->titulo,
                'contenido' => $articulo->contenido,
                'imagen' => $articulo->imagen,
                'categoria' => $articulo->categoria,
                'estado' => $articulo->estado,
                'fechaActualizacion' => $articulo->fechaActualizacion?->toISOString(),
            ])
            ->values();

        return response()->json([
            'data' => $data,
            'message' => 'Artículos pendientes consultados correctamente.',
        ]);
    }

    public function aprobar(Request $request, string $id)
    {
        $articulo = Articulo::findOrFail($id);
        $this->authorize('aprobar', $articulo);

        $articulo->update([
            'estado' => 'publicado',
            'fechaPublicacion' => now(),
            'fechaActualizacion' => now(),
            'motivoRechazo' => null,
        ]);
        $articulo = $articulo->fresh();
        $this->notificaciones->articuloAprobado($articulo);
        $this->auditoria->registrar(
            $request->user(),
            'aprobar',
            'articulo',
            (string) $articulo->_id,
            $request,
            ['estado_anterior' => 'pendiente', 'estado_nuevo' => 'publicado'],
        );

        return response()->json([
            'data' => ['id' => (string) $articulo->_id, 'estado' => $articulo->estado],
            'message' => 'Artículo aprobado y publicado.',
        ]);
    }

    public function rechazar(RechazarArticuloRequest $request, string $id)
    {
        $articulo = Articulo::findOrFail($id);
        $this->authorize('rechazar', $articulo);
        $motivo = $request->validated('motivo');

        $articulo->update([
            'estado' => 'rechazado',
            'motivoRechazo' => $motivo,
            'fechaActualizacion' => now(),
        ]);
        $articulo = $articulo->fresh();
        $this->notificaciones->articuloRechazado($articulo, $motivo);
        $this->auditoria->registrar(
            $request->user(),
            'rechazar',
            'articulo',
            (string) $articulo->_id,
            $request,
            ['estado_anterior' => 'pendiente', 'estado_nuevo' => 'rechazado', 'motivo' => $motivo],
        );

        return response()->json([
            'data' => ['id' => (string) $articulo->_id, 'estado' => $articulo->estado],
            'message' => 'Artículo rechazado y el autor fue notificado.',
        ]);
    }

    public function despublicar(Request $request, string $id)
    {
        $articulo = Articulo::findOrFail($id);
        $this->authorize('despublicar', $articulo);
        $articulo->update([
            'estado' => 'borrador',
            'fechaPublicacion' => null,
            'fechaActualizacion' => now(),
        ]);
        $this->auditoria->registrar(
            $request->user(),
            'despublicar',
            'articulo',
            (string) $articulo->_id,
            $request,
            ['estado_anterior' => 'publicado', 'estado_nuevo' => 'borrador'],
        );

        return response()->json([
            'data' => ['id' => (string) $articulo->_id, 'estado' => 'borrador'],
            'message' => 'Artículo retirado de publicación.',
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $articulo = Articulo::findOrFail($id);
        $this->authorize('eliminar', $articulo);
        $this->auditoria->registrar(
            $request->user(),
            'eliminar_borrador',
            'articulo',
            (string) $articulo->_id,
            $request,
            ['estado_anterior' => 'borrador'],
        );
        $articulo->delete();

        return response()->json([
            'data' => ['id' => $id],
            'message' => 'Borrador eliminado correctamente.',
        ]);
    }
}
