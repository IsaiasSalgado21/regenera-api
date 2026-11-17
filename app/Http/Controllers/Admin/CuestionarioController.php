<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cuestionario;
use App\Models\Medalla;
use App\Services\CatalogoMedallas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CuestionarioController extends Controller
{
    public function __construct(private readonly CatalogoMedallas $medallas) {}

    public function index()
    {
        $latest = Cuestionario::query()
            ->orderByDesc('version')
            ->get()
            ->unique('slug')
            ->sortBy('titulo')
            ->values();

        return response()->json(['data' => $latest]);
    }

    public function store(Request $request)
    {
        $data = $this->validateQuiz($request, true);
        abort_if(
            Cuestionario::query()->where('slug', $data['slug'])->exists(),
            409,
            'Ya existe un cuestionario con ese identificador.',
        );

        $data['version'] = 1;
        $data['activo'] = true;
        $quiz = Cuestionario::create($data);

        return response()->json([
            'data' => $quiz,
            'message' => 'Cuestionario creado correctamente.',
        ], 201);
    }

    public function update(Request $request, string $slug)
    {
        $current = Cuestionario::query()->where('slug', $slug)->orderByDesc('version')->firstOrFail();
        $data = $this->validateQuiz($request, false, $current);
        $data['slug'] = $slug;
        $data['version'] = (int) $current->version + 1;
        $data['activo'] = true;

        $nextVersion = Cuestionario::create($data);
        Cuestionario::query()
            ->where('slug', $slug)
            ->where('version', (int) $current->version)
            ->update(['activo' => false]);

        return response()->json([
            'data' => $nextVersion,
            'message' => 'Se guardó una nueva versión. Los intentos anteriores se conservaron.',
        ]);
    }

    public function destroy(string $slug)
    {
        $quiz = Cuestionario::query()->where('slug', $slug)->orderByDesc('version')->firstOrFail();
        $quiz->update(['activo' => false]);

        return response()->json([
            'data' => ['slug' => $slug, 'version' => (int) $quiz->version, 'activo' => false],
            'message' => 'Cuestionario desactivado; sus intentos históricos se conservaron.',
        ]);
    }

    private function validateQuiz(Request $request, bool $creating = false, ?Cuestionario $current = null): array
    {
        if ($request->has('medallaSlug')) {
            $slug = $request->validate(['medallaSlug' => ['required', 'string', 'max:80', 'alpha_dash']])['medallaSlug'];
            $medalla = $this->medallas->todas()->get($slug);
            abort_unless($medalla, 422, 'Selecciona una medalla existente.');
            abort_if(! $medalla['activo'] && ($current?->medalla['slug'] ?? null) !== $slug, 422, 'No se puede vincular una medalla desactivada.');
            $request->merge(['medalla' => collect($medalla)->only(['slug', 'nombre', 'descripcion', 'icono'])->all()]);
        } elseif (is_string($request->input('medalla.slug'))) {
            $slug = $request->input('medalla.slug');
            $medalla = Medalla::query()->where('slug', $slug)->first();
            abort_if($medalla && ! $medalla->activo && ($current?->medalla['slug'] ?? null) !== $slug, 422, 'No se puede vincular una medalla desactivada.');
        }
        $rules = [
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['required', 'string', 'max:500'],
            'guia' => ['required', 'string', 'max:2000'],
            'audiencia' => ['required', 'array', 'min:1'],
            'audiencia.*' => ['required', 'string', 'distinct', Rule::in(['usuario', 'organizador', 'admin'])],
            'requiereOrganizadorVerificado' => ['sometimes', 'boolean'],
            'minimoAprobacion' => ['required', 'numeric', 'min:0', 'max:100'],
            'puntosAprobacion' => ['required', 'integer', 'min:0', 'max:10000'],
            'preguntas' => ['required', 'array', 'min:1', 'max:30'],
            'preguntas.*.id' => ['required', 'string', 'max:80', 'distinct'],
            'preguntas.*.texto' => ['required', 'string', 'max:500'],
            'preguntas.*.tipo' => ['sometimes', Rule::in(['unica', 'multiple', 'verdadero_falso', 'corta'])],
            'preguntas.*.peso' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'preguntas.*.opciones' => ['nullable', 'array', 'max:20'],
            'preguntas.*.opciones.*' => ['required', 'string', 'max:300'],
            'preguntas.*.respuestaCorrecta' => ['nullable', 'integer', 'min:0'],
            'preguntas.*.respuestasCorrectas' => ['nullable', 'array', 'max:20'],
            'preguntas.*.respuestasCorrectas.*' => ['integer', 'min:0'],
            'preguntas.*.respuestasAceptadas' => ['nullable', 'array', 'max:20'],
            'preguntas.*.respuestasAceptadas.*' => ['required', 'string', 'max:300'],
            'preguntas.*.explicacion' => ['required', 'string', 'max:1200'],
            'medalla.slug' => ['required', 'string', 'max:80', 'alpha_dash'],
            'medalla.nombre' => ['required', 'string', 'max:100'],
            'medalla.descripcion' => ['required', 'string', 'max:300'],
            'medalla.icono' => ['required', 'string', 'max:40', 'alpha_dash'],
        ];
        if ($creating) {
            $rules['slug'] = ['required', 'string', 'max:80', 'alpha_dash'];
        }

        $validator = Validator::make($request->all(), $rules);
        $validator->after(function ($validator) use ($request): void {
            foreach ((array) $request->input('preguntas', []) as $index => $question) {
                if (! is_array($question)) {
                    $validator->errors()->add("preguntas.$index", 'La pregunta no es válida.');
                    continue;
                }
                $type = $question['tipo'] ?? 'unica';
                $options = $question['opciones'] ?? [];
                if ($type === 'corta') {
                    if (! is_array($question['respuestasAceptadas'] ?? null) || count($question['respuestasAceptadas']) < 1) {
                        $validator->errors()->add("preguntas.$index.respuestasAceptadas", 'Agrega al menos una respuesta aceptada.');
                    }
                    continue;
                }
                if (! is_array($options) || count($options) < 2 || ($type === 'verdadero_falso' && count($options) !== 2)) {
                    $validator->errors()->add("preguntas.$index.opciones", 'La pregunta necesita opciones válidas.');
                    continue;
                }
                $correct = $type === 'multiple' ? ($question['respuestasCorrectas'] ?? []) : [$question['respuestaCorrecta'] ?? null];
                if (! is_array($correct) || ! count($correct)
                    || count($correct) !== count(array_unique($correct, SORT_REGULAR))
                    || collect($correct)->contains(fn ($answer) => ! is_numeric($answer) || (int) $answer < 0 || (int) $answer >= count($options))) {
                    $validator->errors()->add("preguntas.$index.respuestaCorrecta", 'Selecciona al menos una respuesta correcta válida.');
                }
            }
            if ($request->boolean('requiereOrganizadorVerificado')
                && ! in_array('organizador', (array) $request->input('audiencia', []), true)) {
                $validator->errors()->add('audiencia', 'Un cuestionario de verificación debe incluir al rol organizador.');
            }
        });

        $data = $validator->validate();
        foreach ($data['preguntas'] as &$question) {
            if (isset($question['respuestasCorrectas'])) {
                $question['respuestasCorrectas'] = array_map('intval', $question['respuestasCorrectas']);
            }
        }
        unset($question);

        return $data;
    }
}