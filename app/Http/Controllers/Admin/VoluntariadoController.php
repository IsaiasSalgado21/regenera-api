<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Asistencia;
use App\Models\Inscripcion;
use App\Models\Lugar;
use App\Models\Notificacion;
use App\Models\User;
use App\Models\Voluntariado;
use App\Services\AuditoriaService;
use App\Services\BajaCaliforniaSurBoundary;
use App\Services\GridFsImageService;
use App\Services\NotificationService;
use App\Services\VoluntariadoCupoService;
use App\Services\VoluntariadoWorkflow;
use App\Services\VoluntariadoHorario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use MongoDB\BSON\ObjectId;

class VoluntariadoController extends Controller
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly GridFsImageService $gridFsImages,
        private readonly NotificationService $notificaciones,
        private readonly VoluntariadoWorkflow $workflow,
        private readonly VoluntariadoHorario $horario,
        private readonly VoluntariadoCupoService $cupos,
    ) {}

    public function organizadores()
    {
        $data = User::query()
            ->where('rol', 'organizador')
            ->orderBy('nombre')
            ->get(['_id', 'nombre', 'correo', 'estado'])
            ->map(fn (User $usuario) => [
                'id' => (string) $usuario->getAuthIdentifier(),
                'nombre' => $usuario->nombre,
                'correo' => $usuario->correo,
                'estado' => $usuario->estado,
            ])
            ->values();

        return response()->json([
            'data' => $data,
            'message' => 'Organizadores consultados correctamente.',
        ]);
    }

    public function index()
    {
        $query = Voluntariado::query();
        $request = request();
        if ($request->user()->rol !== 'admin') {
            $organizadorId = (string) $request->user()->getAuthIdentifier();
            $query->where(function ($voluntariados) use ($organizadorId): void {
                $voluntariados->where('organizadorId', $organizadorId);
                if (preg_match('/^[a-f0-9]{24}$/i', $organizadorId)) {
                    $voluntariados->orWhere('organizadorId', new ObjectId($organizadorId));
                }
            });
        }

        $data = $query
            ->orderBy('fechaInicio')
            ->get()
            ->map(fn (Voluntariado $voluntariado) => $this->present($voluntariado))
            ->values();

        return response()->json([
            'data' => $data,
            'message' => 'Todos los voluntariados consultados correctamente.',
        ]);
    }

    public function dashboard(Request $request, string $id)
    {
        $voluntariado = Voluntariado::findOrFail($id);
        $this->ensureCanManage($request, $voluntariado);
        abort_unless(
            $request->user()->rol === 'admin'
                || in_array($voluntariado->estado, ['publicado', 'cancelado', 'finalizado'], true),
            404,
        );
        $voluntariadoId = (string) $voluntariado->_id;

        $inscripciones = Inscripcion::where(function ($query) use ($voluntariadoId): void {
            $query->where('voluntariadoId', $voluntariadoId);
            if (preg_match('/^[a-f0-9]{24}$/i', $voluntariadoId)) {
                $query->orWhere('voluntariadoId', new ObjectId($voluntariadoId));
            }
        })->where('estado', '!=', 'reservando')->get();

        $asistencias = Asistencia::where(function ($query) use ($voluntariadoId): void {
            $query->where('voluntariadoId', $voluntariadoId);
            if (preg_match('/^[a-f0-9]{24}$/i', $voluntariadoId)) {
                $query->orWhere('voluntariadoId', new ObjectId($voluntariadoId));
            }
        })->get();

        $usuarioIds = $inscripciones
            ->pluck('usuarioId')
            ->filter()
            ->map(fn ($usuarioId) => (string) $usuarioId)
            ->unique()
            ->values()
            ->all();
        $organizadorId = (string) $voluntariado->organizadorId;
        $organizadorStoredId = preg_match('/^[a-f0-9]{24}$/i', $organizadorId)
            ? new ObjectId($organizadorId)
            : $organizadorId;
        $solicitudesAsistencia = Notificacion::query()
            ->where('usuarioId', $organizadorStoredId)
            ->where('tipo', 'solicitud_asistencia')
            ->where('entidad.id', $voluntariadoId)
            ->get()
            ->keyBy(fn (Notificacion $notificacion) => (string) data_get($notificacion->datos, 'inscripcionId'));
        $usuarios = User::whereIn('_id', array_values(array_unique([...$usuarioIds, $organizadorId])))
            ->get(['_id', 'nombre', 'correo', 'foto'])
            ->keyBy(fn (User $usuario) => (string) $usuario->_id);
        $asistenciaPorUsuario = $asistencias
            ->sortBy('fecha')
            ->keyBy(fn (Asistencia $asistencia) => (string) $asistencia->usuarioId);
        $inscripcionesActivas = $inscripciones->filter(fn (Inscripcion $inscripcion) => $inscripcion->estado !== 'cancelada');
        $usuarioIdsActivos = $inscripcionesActivas
            ->pluck('usuarioId')
            ->map(fn ($usuarioId) => (string) $usuarioId)
            ->unique()
            ->all();
        $asistenciasConfirmadas = $asistencias
            ->filter(fn (Asistencia $asistencia) => (bool) $asistencia->confirmada)
            ->filter(fn (Asistencia $asistencia) => in_array((string) $asistencia->usuarioId, $usuarioIdsActivos, true))
            ->unique(fn (Asistencia $asistencia) => (string) $asistencia->usuarioId);
        $inscritos = $inscripcionesActivas->count();
        $faltas = $inscripcionesActivas->filter(fn (Inscripcion $inscripcion): bool => $this->workflow->esFalta(
            $voluntariado,
            $inscripcion,
            $asistenciaPorUsuario->get((string) $inscripcion->usuarioId),
            $this->horario,
        ))->count();
        $inicioEvento = $this->horario->inicio($voluntariado);
        $cupo = max(0, (int) ($voluntariado->cupo ?? 0));
        $asistentes = $asistenciasConfirmadas->count();
        $lugar = Lugar::find((string) $voluntariado->lugarId);
        $organizador = $usuarios->get($organizadorId);

        $participantes = $inscripciones
            ->map(function (Inscripcion $inscripcion) use ($usuarios, $asistenciaPorUsuario, $voluntariado, $solicitudesAsistencia): array {
                $usuarioId = (string) $inscripcion->usuarioId;
                $usuario = $usuarios->get($usuarioId);
                $asistencia = $asistenciaPorUsuario->get($usuarioId);
                $falto = $this->workflow->esFalta($voluntariado, $inscripcion, $asistencia, $this->horario);
                $solicitud = $solicitudesAsistencia->get((string) $inscripcion->_id);

                return [
                    'id' => (string) $inscripcion->_id,
                    'usuarioId' => $usuarioId,
                    'nombre' => $usuario?->nombre,
                    'correo' => $usuario?->correo,
                    'foto' => $usuario?->foto,
                    'fechaInscripcion' => $inscripcion->fechaInscripcion?->toIso8601String(),
                    'estadoInscripcion' => $inscripcion->estado,
                    'falto' => $falto,
                    'solicitudAsistencia' => $solicitud ? [
                        'id' => (string) $solicitud->_id,
                        'estado' => data_get($solicitud->datos, 'estadoSolicitud'),
                        'motivoRespuesta' => data_get($solicitud->datos, 'motivoRespuesta'),
                    ] : null,
                    'observaciones' => $inscripcion->observaciones,
                    'asistencia' => $asistencia ? [
                        'confirmada' => (bool) $asistencia->confirmada,
                        'fecha' => $asistencia->fecha?->toIso8601String(),
                        'hora' => $asistencia->hora,
                        'metodo' => $asistencia->metodo,
                    ] : null,
                ];
            })
            ->sortByDesc('fechaInscripcion')
            ->values();

        return response()->json([
            'data' => [
                'voluntariado' => [
                    ...$this->present($voluntariado),
                    'iniciadoPorFecha' => $inicioEvento?->lte($this->horario->ahora()) ?? false,
                    'finalizadoPorFecha' => $this->workflow->finalizado($voluntariado, $this->horario),
                    'cancelado' => $voluntariado->estado === 'cancelado',
                    'creadoEn' => $voluntariado->created_at?->toIso8601String(),
                    'actualizadoEn' => $voluntariado->updated_at?->toIso8601String(),
                    'lugar' => $lugar ? [
                        'nombre' => $lugar->nombre,
                        'municipio' => $lugar->municipio,
                        'direccion' => $lugar->direccion,
                    ] : null,
                    'organizador' => $organizador ? [
                        'id' => $organizadorId,
                        'nombre' => $organizador->nombre,
                        'correo' => $organizador->correo,
                    ] : null,
                ],
                'estadisticas' => [
                    'inscritos' => $inscritos,
                    'totalInscripciones' => $inscripciones->count(),
                    'cupo' => $cupo,
                    'disponibles' => max(0, $cupo - $inscritos),
                    'ocupacion' => $cupo > 0 ? round(min(100, ($inscritos / $cupo) * 100), 1) : 0,
                    'asistencias' => $asistentes,
                    'porcentajeAsistencia' => $inscritos > 0 ? round(($asistentes / $inscritos) * 100, 1) : 0,
                    'pendientes' => $voluntariado->estado === 'cancelado'
                        ? 0
                        : max(0, $inscritos - $asistentes - $faltas),
                    'faltas' => $faltas,
                    'canceladas' => $inscripciones->where('estado', 'cancelada')->count(),
                    'registrosQr' => $asistencias->where('confirmada', true)->where('metodo', 'QR')->count(),
                ],
                'participantes' => $participantes,
            ],
            'message' => 'Dashboard del voluntariado consultado correctamente.',
        ]);
    }

    public function actualizarCodigoAsistencia(Request $request, string $id)
    {
        $validated = $request->validate([
            'codigoAsistenciaActivo' => ['required', 'boolean'],
        ]);
        $voluntariado = Voluntariado::findOrFail($id);
        $this->ensureCanManage($request, $voluntariado);
        $voluntariado->update(['codigoAsistenciaActivo' => $validated['codigoAsistenciaActivo']]);

        $this->auditoria->registrar(
            $request->user(),
            $validated['codigoAsistenciaActivo'] ? 'activar_qr_asistencia' : 'desactivar_qr_asistencia',
            'voluntariado',
            (string) $voluntariado->_id,
            $request,
        );

        return response()->json([
            'data' => [
                'id' => (string) $voluntariado->_id,
                'codigoAsistenciaActivo' => (bool) $voluntariado->codigoAsistenciaActivo,
            ],
            'message' => $validated['codigoAsistenciaActivo']
                ? 'El código de asistencia quedó activo.'
                : 'El código de asistencia quedó desactivado.',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $validated = $this->normalize($validated, false);
        $lugar = $this->validatePlace($validated['lugarId']);
        $validated['latitud'] = $lugar->latitud;
        $validated['longitud'] = $lugar->longitud;
        $this->validateCoordinates($validated);
        $organizadorId = (string) $request->user()->getAuthIdentifier();
        if ($request->user()->rol === 'organizador') {
            $validated['organizadorId'] = preg_match('/^[a-f0-9]{24}$/i', $organizadorId)
                ? new ObjectId($organizadorId)
                : $organizadorId;
            $validated['estado'] = $this->workflow->estadoInicial($request->user());
        }

        [$validated, $removedImages] = $this->applyImageChanges($request, $validated);

        $voluntariado = Voluntariado::create($validated);
        $this->gridFsImages->deleteStored($removedImages);
        $this->auditoria->registrar(
            $request->user(),
            'crear',
            'voluntariado',
            (string) $voluntariado->_id,
            $request,
            ['campos' => array_keys($validated)],
        );
        if ($request->user()->rol === 'organizador' && $voluntariado->estado === 'pendiente') {
            $this->notificaciones->voluntariadoEnviado($voluntariado);
        }

        return response()->json([
            'data' => $this->present($voluntariado),
            'message' => 'Voluntariado creado correctamente.',
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $voluntariado = Voluntariado::findOrFail($id);
        $this->ensureCanManage($request, $voluntariado);
        $this->ensureBeforeStartForOwner($request, $voluntariado);
        $validated = $request->validate($this->rules(true));
        $validated = $this->normalize($validated, true);
        if (array_key_exists('lugarId', $validated)) {
            $lugar = $this->validatePlace($validated['lugarId']);
            $validated['latitud'] = $lugar->latitud;
            $validated['longitud'] = $lugar->longitud;
        }
        $this->validateCoordinates($validated, $voluntariado);
        $estadoAnterior = $voluntariado->estado;
        $camposActualizados = array_keys($validated);
        if ($request->user()->rol !== 'admin') {
            unset($validated['organizadorId']);
        }
        $validated['estado'] = $this->workflow->estadoTrasEdicion(
            $request->user(),
            $voluntariado,
            $validated['estado'] ?? null,
        );
        $hayCambiosImportantes = $this->workflow->hayCambiosImportantes($voluntariado, $validated);

        [$validated, $removedImages] = $this->applyImageChanges($request, $validated, $voluntariado);

        if (array_key_exists('cupo', $validated)) {
            if (! $this->cupos->actualizarCupo($voluntariado, (int) $validated['cupo'], $validated)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El cupo no puede ser menor que las inscripciones activas.',
                ], 409);
            }

            $validated = [];
            $voluntariado->refresh();
        } elseif ($validated !== []) {
            $voluntariado->update($validated);
        }
        if ($hayCambiosImportantes) {
            $voluntariadoActualizado = $voluntariado->fresh();
            $this->notificaciones->voluntariadoActualizado(
                $voluntariadoActualizado,
                $voluntariadoActualizado->estado === 'cancelado',
            );
        }
        $this->gridFsImages->deleteStored($removedImages);
        $this->auditoria->registrar(
            $request->user(),
            'actualizar',
            'voluntariado',
            (string) $voluntariado->_id,
            $request,
            ['campos' => $camposActualizados],
        );
        if ($request->user()->rol === 'organizador' && $estadoAnterior !== 'pendiente' && $voluntariado->estado === 'pendiente') {
            $this->notificaciones->voluntariadoEnviado($voluntariado);
        } elseif ($request->user()->rol === 'admin' && $estadoAnterior !== 'publicado' && $voluntariado->estado === 'publicado') {
            $this->notificaciones->voluntariadoAprobado($voluntariado->fresh());
        }

        return response()->json([
            'data' => $this->present($voluntariado->fresh()),
            'message' => 'Voluntariado actualizado correctamente.',
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $voluntariado = Voluntariado::findOrFail($id);
        $this->ensureCanManage($request, $voluntariado);
        $this->ensureBeforeStartForOwner($request, $voluntariado);
        $this->auditoria->registrar(
            $request->user(),
            'eliminar',
            'voluntariado',
            (string) $voluntariado->_id,
            $request,
            ['titulo' => $voluntariado->titulo],
        );
        $voluntariado->delete();
        $this->gridFsImages->deleteStored($this->imagePaths($voluntariado));

        return response()->json([
            'data' => ['id' => $id],
            'message' => 'Voluntariado eliminado correctamente.',
        ]);
    }

    public function aprobar(Request $request, string $id)
    {
        $voluntariado = Voluntariado::findOrFail($id);
        $voluntariado->update(['estado' => 'publicado']);
        $voluntariado = $voluntariado->fresh();
        $this->notificaciones->voluntariadoAprobado($voluntariado);
        $this->auditoria->registrar($request->user(), 'aprobar', 'voluntariado', (string) $voluntariado->_id, $request);

        return response()->json(['data' => $this->present($voluntariado), 'message' => 'Voluntariado aprobado y publicado.']);
    }

    public function rechazar(Request $request, string $id)
    {
        $validated = $request->validate(['motivo' => ['required', 'string', 'min:5', 'max:1000']]);
        $voluntariado = Voluntariado::findOrFail($id);
        $voluntariado->update(['estado' => 'rechazado']);
        $voluntariado = $voluntariado->fresh();
        $this->notificaciones->voluntariadoRechazado($voluntariado, $validated['motivo']);
        $this->auditoria->registrar($request->user(), 'rechazar', 'voluntariado', (string) $voluntariado->_id, $request, ['motivo' => $validated['motivo']]);

        return response()->json(['data' => $this->present($voluntariado), 'message' => 'Voluntariado rechazado y el organizador fue notificado.']);
    }

    private function rules(bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return [
            'titulo' => [...$required, 'string', 'max:150'],
            'descripcion' => [...$required, 'string', 'max:3000'],
            'categoria' => [...$required, 'string', 'max:100'],
            'lugarId' => [...$required, 'string', 'max:100'],
            'organizadorId' => [
                Rule::requiredIf(fn () => request()->user()?->rol === 'admin'),
                'nullable',
                'string',
                'max:100',
            ],
            'latitud' => [$partial ? 'sometimes' : 'nullable', 'nullable', 'numeric', 'between:-90,90'],
            'longitud' => [$partial ? 'sometimes' : 'nullable', 'nullable', 'numeric', 'between:-180,180'],
            'fechaInicio' => [...$required, 'date'],
            'fechaFin' => [...$required, 'date', 'after_or_equal:fechaInicio'],
            'horaInicio' => [...$required, 'date_format:H:i'],
            'horaFin' => [...$required, 'date_format:H:i', 'after:horaInicio'],
            'cupo' => [...$required, 'integer', 'min:1', 'max:10000'],
            'requisitos' => [$partial ? 'sometimes' : 'nullable', 'array'],
            'requisitos.*' => ['string', 'max:255'],
            'materiales' => [$partial ? 'sometimes' : 'nullable', 'array'],
            'materiales.*' => ['string', 'max:255'],
            'imagen' => [$partial ? 'sometimes' : 'nullable', 'file', 'image', 'max:5120'],
            'imagenes' => [$partial ? 'sometimes' : 'nullable', 'array', 'max:10'],
            'imagenes.*' => ['file', 'image', 'max:5120'],
            'imagenesEliminar' => [$partial ? 'sometimes' : 'nullable', 'array', 'max:10'],
            'imagenesEliminar.*' => ['integer', 'min:0'],
            'estado' => [
                $partial ? 'sometimes' : 'nullable',
                Rule::in(['pendiente', 'publicado', 'cancelado', 'finalizado']),
            ],
        ];
    }

    private function normalize(array $attributes, bool $partial): array
    {
        if (! $partial) {
            $attributes['requisitos'] = $attributes['requisitos'] ?? [];
            $attributes['materiales'] = $attributes['materiales'] ?? [];
            $attributes['estado'] = $attributes['estado'] ?? 'pendiente';
        }

        return $attributes;
    }

    private function present(Voluntariado $voluntariado): array
    {
        return [
            'id' => (string) $voluntariado->_id,
            'titulo' => $voluntariado->titulo,
            'organizadorNombre' => User::find((string) $voluntariado->organizadorId)?->nombre,
            'iniciadoPorFecha' => $this->horario->inicio($voluntariado)?->lte($this->horario->ahora()) ?? false,
            'descripcion' => $voluntariado->descripcion,
            'categoria' => $voluntariado->categoria,
            'lugarId' => (string) $voluntariado->lugarId,
            'organizadorId' => (string) $voluntariado->organizadorId,
            'latitud' => $voluntariado->latitud,
            'longitud' => $voluntariado->longitud,
            'fechaInicio' => $voluntariado->fechaInicio?->toDateString(),
            'fechaFin' => $voluntariado->fechaFin?->toDateString(),
            'horaInicio' => $voluntariado->horaInicio,
            'horaFin' => $voluntariado->horaFin,
            'cupo' => $voluntariado->cupo,
            'requisitos' => $voluntariado->requisitos ?? [],
            'materiales' => $voluntariado->materiales ?? [],
            'imagen' => $this->gridFsImages->url($this->imagePaths($voluntariado)[0] ?? null),
            'imagenes' => $this->gridFsImages->urls($this->imagePaths($voluntariado)),
            'estado' => $voluntariado->estado,
            'tokenAsistencia' => $voluntariado->tokenAsistencia,
            'codigoCorto' => $voluntariado->codigoCorto,
            'codigoAsistenciaActivo' => (bool) ($voluntariado->codigoAsistenciaActivo ?? true),
        ];
    }

    private function imagePaths(Voluntariado $voluntariado): array
    {
        return $this->gridFsImages->paths($voluntariado->imagenes, $voluntariado->imagen);
    }

    private function applyImageChanges(Request $request, array $attributes, ?Voluntariado $voluntariado = null): array
    {
        unset($attributes['imagen'], $attributes['imagenes'], $attributes['imagenesEliminar']);
        $existingImages = $voluntariado ? $this->imagePaths($voluntariado) : [];
        $requestedRemovals = array_values(array_unique(array_map(
            'intval',
            $request->input('imagenesEliminar', []),
        )));
        $files = $request->file('imagenes', []);
        $files = is_array($files) ? array_values(array_filter($files)) : ($files ? [$files] : []);
        if ($request->hasFile('imagen')) {
            $files[] = $request->file('imagen');
        }

        if ($voluntariado === null && ! $files && ! $requestedRemovals) {
            $attributes['imagenes'] = [];
            $attributes['imagen'] = null;
            return [$attributes, []];
        }
        if ($voluntariado !== null && ! $files && ! $requestedRemovals) {
            return [$attributes, []];
        }

        [$images, $removedImages] = $this->gridFsImages->applyCollectionChanges(
            $existingImages,
            $files,
            $requestedRemovals,
            10,
            'voluntariados',
            'voluntariado',
        );
        $attributes['imagenes'] = $images;
        $attributes['imagen'] = $images[0] ?? null;

        return [$attributes, $removedImages];
    }

    private function validateCoordinates(array $attributes, ?Voluntariado $voluntariado = null): void
    {
        if (
            $voluntariado !== null
            && ! array_key_exists('latitud', $attributes)
            && ! array_key_exists('longitud', $attributes)
        ) {
            return;
        }

        $latitude = array_key_exists('latitud', $attributes) ? $attributes['latitud'] : $voluntariado?->latitud;
        $longitude = array_key_exists('longitud', $attributes) ? $attributes['longitud'] : $voluntariado?->longitud;

        if (($latitude === null) !== ($longitude === null)) {
            throw ValidationException::withMessages([
                'latitud' => 'La latitud y longitud deben enviarse juntas.',
                'longitud' => 'La latitud y longitud deben enviarse juntas.',
            ]);
        }
        if ($latitude !== null && ! app(BajaCaliforniaSurBoundary::class)->contains($latitude, $longitude)) {
            throw ValidationException::withMessages([
                'latitud' => 'La ubicación debe estar dentro de Baja California Sur, México.',
                'longitud' => 'La ubicación debe estar dentro de Baja California Sur, México.',
            ]);
        }
    }

    private function validatePlace(string $id): Lugar
    {
        $lugar = preg_match('/^[a-f0-9]{24}$/i', $id) ? Lugar::find($id) : null;
        if (! $lugar) {
            throw ValidationException::withMessages([
                'lugarId' => 'Selecciona un lugar existente de la lista.',
            ]);
        }

        return $lugar;
    }

    private function ensureCanManage(Request $request, Voluntariado $voluntariado): void
    {
        if ($request->user()->rol === 'admin') {
            return;
        }

        abort_unless(
            (string) $voluntariado->organizadorId === (string) $request->user()->getAuthIdentifier(),
            403,
        );
    }

    private function ensureBeforeStartForOwner(Request $request, Voluntariado $voluntariado): void
    {
        if ($request->user()->rol === 'admin') {
            return;
        }

        $inicio = $this->horario->inicio($voluntariado);
        abort_if(
            $inicio && $this->horario->ahora()->greaterThanOrEqualTo($inicio),
            409,
            'No puedes editar, cancelar o eliminar un voluntariado después de su inicio.',
        );
    }
}
