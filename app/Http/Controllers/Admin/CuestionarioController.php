<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cuestionario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CuestionarioController extends Controller
{
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
        $data = $this->validateQuiz($request);
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

    private function validateQuiz(Request $request, bool $creating = false): array
    {
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
            'preguntas.*.opciones' => ['required', 'array', 'min:2', 'max:20'],
            'preguntas.*.opciones.*' => ['required', 'string', 'max:300'],
            'preguntas.*.respuestaCorrecta' => ['required', 'integer', 'min:0'],
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
                $options = $question['opciones'] ?? [];
                $correct = $question['respuestaCorrecta'] ?? null;
                if (is_numeric($correct) && (int) $correct >= count($options)) {
                    $validator->errors()->add("preguntas.$index.respuestaCorrecta", 'La respuesta correcta debe coincidir con una opción existente.');
                }
            }
            if ($request->boolean('requiereOrganizadorVerificado')
                && ! in_array('organizador', (array) $request->input('audiencia', []), true)) {
                $validator->errors()->add('audiencia', 'Un cuestionario de verificación debe incluir al rol organizador.');
            }
        });

        return $validator->validate();
    }
}