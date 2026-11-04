<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lugar;
use App\Models\User;
use App\Models\Voluntariado;
use App\Services\AuditoriaService;
use App\Services\BajaCaliforniaSurBoundary;
use App\Services\GridFsImageService;
use App\Services\NotificationService;
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
            $validated['estado'] = 'pendiente';
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
        if ($request->user()->rol === 'organizador') {
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
        $validated = $request->validate($this->rules(true));
        $validated = $this->normalize($validated, true);
        if (array_key_exists('lugarId', $validated)) {
            $lugar = $this->validatePlace($validated['lugarId']);
            $validated['latitud'] = $lugar->latitud;
            $validated['longitud'] = $lugar->longitud;
        }
        $this->validateCoordinates($validated, $voluntariado);
        if ($request->user()->rol === 'organizador') {
            $validated['estado'] = 'pendiente';
        }

        [$validated, $removedImages] = $this->applyImageChanges($request, $validated, $voluntariado);

        $voluntariado->update($validated);
        $this->gridFsImages->deleteStored($removedImages);
        $this->auditoria->registrar(
            $request->user(),
            'actualizar',
            'voluntariado',
            (string) $voluntariado->_id,
            $request,
            ['campos' => array_keys($validated)],
        );
        if ($request->user()->rol === 'organizador') {
            $this->notificaciones->voluntariadoEnviado($voluntariado);
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
        $required = $partial ? 'sometimes|required' : 'required';

        return [
            'titulo' => [$required, 'string', 'max:150'],
            'descripcion' => [$required, 'string', 'max:3000'],
            'categoria' => [$required, 'string', 'max:100'],
            'lugarId' => [$required, 'string', 'max:100'],
            'organizadorId' => [
                Rule::requiredIf(fn () => request()->user()?->rol === 'admin'),
                'nullable',
                'string',
                'max:100',
            ],
            'latitud' => [$partial ? 'sometimes' : 'nullable', 'nullable', 'numeric', 'between:-90,90'],
            'longitud' => [$partial ? 'sometimes' : 'nullable', 'nullable', 'numeric', 'between:-180,180'],
            'fechaInicio' => [$required, 'date'],
            'fechaFin' => [$required, 'date', 'after_or_equal:fechaInicio'],
            'horaInicio' => [$required, 'date_format:H:i'],
            'horaFin' => [$required, 'date_format:H:i', 'after:horaInicio'],
            'cupo' => [$required, 'integer', 'min:1', 'max:10000'],
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
}
