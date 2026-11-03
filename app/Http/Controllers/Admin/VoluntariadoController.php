<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voluntariado;
use App\Services\AuditoriaService;
use App\Services\GridFsImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class VoluntariadoController extends Controller
{
    public function __construct(
        private readonly AuditoriaService $auditoria,
        private readonly GridFsImageService $gridFsImages,
    ) {}

    public function index()
    {
        $data = Voluntariado::query()
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

        if ($request->hasFile('imagen')) {
            $validated['imagen'] = Storage::disk('gridfs')
                ->putFile('voluntariados', $request->file('imagen'));
        }

        $voluntariado = Voluntariado::create($validated);
        $this->auditoria->registrar(
            $request->user(),
            'crear',
            'voluntariado',
            (string) $voluntariado->_id,
            $request,
            ['campos' => array_keys($validated)],
        );

        return response()->json([
            'data' => $this->present($voluntariado),
            'message' => 'Voluntariado creado correctamente.',
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $voluntariado = Voluntariado::findOrFail($id);
        $imagenAnterior = $voluntariado->imagen;
        $validated = $request->validate($this->rules(true));
        $validated = $this->normalize($validated, true);

        if ($request->hasFile('imagen')) {
            $validated['imagen'] = Storage::disk('gridfs')
                ->putFile('voluntariados', $request->file('imagen'));
        }

        $voluntariado->update($validated);
        if (array_key_exists('imagen', $validated) && $imagenAnterior !== $validated['imagen']) {
            $this->deleteStoredImage($imagenAnterior);
        }
        $this->auditoria->registrar(
            $request->user(),
            'actualizar',
            'voluntariado',
            (string) $voluntariado->_id,
            $request,
            ['campos' => array_keys($validated)],
        );

        return response()->json([
            'data' => $this->present($voluntariado->fresh()),
            'message' => 'Voluntariado actualizado correctamente.',
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $voluntariado = Voluntariado::findOrFail($id);
        $this->auditoria->registrar(
            $request->user(),
            'eliminar',
            'voluntariado',
            (string) $voluntariado->_id,
            $request,
            ['titulo' => $voluntariado->titulo],
        );
        $voluntariado->delete();
        $this->deleteStoredImage($voluntariado->imagen);

        return response()->json([
            'data' => ['id' => $id],
            'message' => 'Voluntariado eliminado correctamente.',
        ]);
    }

    private function rules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes|required' : 'required';

        return [
            'titulo' => [$required, 'string', 'max:150'],
            'descripcion' => [$required, 'string', 'max:3000'],
            'categoria' => [$required, 'string', 'max:100'],
            'lugarId' => [$required, 'string', 'max:100'],
            'organizadorId' => [$required, 'string', 'max:100'],
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
            'imagen' => $this->gridFsImages->url($voluntariado->imagen),
            'estado' => $voluntariado->estado,
        ];
    }

    private function deleteStoredImage(?string $path): void
    {
        if ($path && ! filter_var($path, FILTER_VALIDATE_URL)) {
            Storage::disk('gridfs')->delete($path);
        }
    }
}
