<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\Voluntariado;
use App\Services\GridFsImageService;
use App\Services\NotificationService;
use App\Services\VoluntariadoHorario;
use App\Services\VoluntariadoWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VoluntariadoController extends Controller
{
    public function __construct(
        private readonly GridFsImageService $gridFsImages,
        private readonly NotificationService $notificaciones,
        private readonly VoluntariadoWorkflow $workflow,
        private readonly VoluntariadoHorario $horario,
    ) {}

    public function index(Request $request)
    {
        $voluntariados = Voluntariado::where('estado', 'publicado')
            ->orderBy('fechaInicio')
            ->get();

        return response()->json([
            'success' => true,
            'voluntariados' => $voluntariados->map(function ($voluntariado) {
                $voluntariado->imagen = $this->gridFsImages->url($voluntariado->imagen);

                return $voluntariado;
            }),
        ]);
    }

    public function categorias()
    {
        $recomendadas = [
            'limpieza-de-playas',
            'reforestacion',
            'restauracion',
            'conservacion',
            'monitoreo',
            'educacion-ambiental',
        ];
        $registradas = Voluntariado::query()
            ->get(['categoria'])
            ->pluck('categoria')
            ->filter(fn ($categoria) => is_string($categoria) && trim($categoria) !== '')
            ->map(fn (string $categoria) => trim($categoria))
            ->all();

        return response()->json([
            'success' => true,
            'categorias' => collect($recomendadas)
                ->merge($registradas)
                ->unique(fn (string $categoria) => mb_strtolower($categoria))
                ->values(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'titulo' => 'required|string|max:150',
            'descripcion' => 'required|string|max:3000',
            'categoria' => 'required|string|max:100',
            'lugarId' => 'required|string|max:100',
            'latitud' => 'nullable|numeric|between:-90,90',
            'longitud' => 'nullable|numeric|between:-180,180',
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
            'horaInicio' => [
                'required',
                'date_format:H:i',
            ],
            'horaFin' => [
                'required',
                'date_format:H:i',
                'after:horaInicio',
            ],
            'cupo' => 'required|integer|min:1|max:10000',
            'requisitos' => 'nullable|array',
            'requisitos.*' => 'string|max:255',
            'materiales' => 'nullable|array',
            'materiales.*' => 'string|max:255',
            'imagen' => 'nullable|file|image|max:5120',
        ]);

        $validated['estado'] = $this->workflow->estadoInicial($user);
        $validated['organizadorId'] = $user->getAuthIdentifier();
        $validated['requisitos'] = $validated['requisitos'] ?? [];
        $validated['materiales'] = $validated['materiales'] ?? [];

        if ($request->hasFile('imagen')) {
            $validated['imagen'] = Storage::disk('gridfs')
                ->putFile('voluntariados', $request->file('imagen'));
        }

        $voluntariado = Voluntariado::create($validated);
        if ($voluntariado->estado === 'pendiente') {
            $this->notificaciones->voluntariadoEnviado($voluntariado);
        }
        $message = $voluntariado->estado === 'publicado'
            ? 'Voluntariado publicado correctamente.'
            : 'Tu voluntariado quedó pendiente de aprobación del equipo.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'voluntariado' => $voluntariado,
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $voluntariado = Voluntariado::find($id);

        if (!$voluntariado) {
            return response()->json([
                'success' => false,
                'message' => 'Voluntariado no encontrado.',
            ], 404);
        }

        if (! in_array($voluntariado->estado, ['publicado', 'cancelado', 'finalizado'], true)) {
            $user = $request->user();
            $isOwner = $user && (string) $voluntariado->organizadorId === (string) $user->getAuthIdentifier();
            $isAdmin = $user?->rol === 'admin';
            $isParticipant = $user && Inscripcion::where('usuarioId', (string) $user->getAuthIdentifier())
                ->where('voluntariadoId', (string) $voluntariado->_id)
                ->exists();

            abort_unless($isAdmin || $isOwner || $isParticipant, 404);
        }

        return response()->json([
            'success' => true,
            'voluntariado' => tap($voluntariado, function (Voluntariado $voluntariado): void {
                $voluntariado->imagen = $this->gridFsImages->url($voluntariado->imagen);
                $voluntariado->setAttribute(
                    'finalizadoPorFecha',
                    $this->workflow->finalizado($voluntariado, $this->horario),
                );
            }),
        ]);
    }

    public function update(Request $request, string $id)
    {
        $voluntariado = Voluntariado::find($id);

        if (!$voluntariado) {
            return response()->json([
                'success' => false,
                'message' => 'Voluntariado no encontrado.',
            ], 404);
        }

        $this->ensureCanManage($request, $voluntariado);

        $validated = $request->validate([
            'titulo' => 'sometimes|required|string|max:150',
            'descripcion' => 'sometimes|required|string|max:3000',
            'lugarId' => 'sometimes|required|string|max:100',
            'latitud' => 'sometimes|nullable|numeric|between:-90,90',
            'longitud' => 'sometimes|nullable|numeric|between:-180,180',
            'fechaInicio' => 'sometimes|required|date',
            'fechaFin' => 'sometimes|required|date|after_or_equal:fechaInicio',
            'horaInicio' => 'sometimes|required|date_format:H:i',
            'horaFin' => 'sometimes|required|date_format:H:i',
            'cupo' => 'sometimes|required|integer|min:1|max:10000',
            'requisitos' => 'sometimes|nullable|array',
            'requisitos.*' => 'string|max:255',
            'materiales' => 'sometimes|nullable|array',
            'materiales.*' => 'string|max:255',
            'imagen' => 'sometimes|nullable|file|image|max:5120',
            'estado' => [
                'sometimes',
                'required',
                Rule::in([
                    'pendiente',
                    'publicado',
                    'cancelado',
                    'finalizado',
                ]),
            ],
        ]);

        $estadoAnterior = $voluntariado->estado;
        $validated['estado'] = $this->workflow->estadoTrasEdicion(
            $request->user(),
            $voluntariado,
            $validated['estado'] ?? null,
        );
        $hayCambiosImportantes = $this->workflow->hayCambiosImportantes($voluntariado, $validated);

        if ($request->hasFile('imagen')) {
            $validated['imagen'] = Storage::disk('gridfs')
                ->putFile('voluntariados', $request->file('imagen'));
        }

        $voluntariado->update($validated);
        if ($hayCambiosImportantes) {
            $voluntariadoActualizado = $voluntariado->fresh();
            $this->notificaciones->voluntariadoActualizado(
                $voluntariadoActualizado,
                $voluntariadoActualizado->estado === 'cancelado',
            );
        }
        if ($estadoAnterior !== 'pendiente' && $voluntariado->estado === 'pendiente') {
            $this->notificaciones->voluntariadoEnviado($voluntariado);
        }
        if ($request->user()->rol === 'admin' && $estadoAnterior !== 'publicado' && $voluntariado->estado === 'publicado') {
            $this->notificaciones->voluntariadoAprobado($voluntariado->fresh());
        }

        return response()->json([
            'success' => true,
            'message' => 'Voluntariado actualizado correctamente.',
            'voluntariado' => tap($voluntariado->fresh(), function (Voluntariado $voluntariado): void {
                $voluntariado->imagen = $this->gridFsImages->url($voluntariado->imagen);
            }),
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $voluntariado = Voluntariado::find($id);

        if (!$voluntariado) {
            return response()->json([
                'success' => false,
                'message' => 'Voluntariado no encontrado.',
            ], 404);
        }

        $this->ensureCanManage($request, $voluntariado);

        $voluntariado->delete();

        return response()->json([
            'success' => true,
            'message' => 'Voluntariado eliminado correctamente.',
        ]);
    }

    private function ensureCanManage(Request $request, Voluntariado $voluntariado): void
    {
        abort_unless(
            $request->user()->rol === 'admin'
                || (string) $voluntariado->organizadorId === (string) $request->user()->getAuthIdentifier(),
            403,
        );
    }
}
