<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateArticuloRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['sometimes', 'required', 'string', 'max:200'],
            'contenido' => ['sometimes', 'required', 'string'],
            'imagen' => ['sometimes', 'nullable', 'file', 'image', 'max:5120'],
            'imagenes' => ['sometimes', 'nullable', 'array', 'max:10'],
            'imagenes.*' => ['file', 'image', 'max:5120'],
            'imagenesEliminar' => ['sometimes', 'nullable', 'array', 'max:10'],
            'imagenesEliminar.*' => ['integer', 'min:0'],
            'categoria' => ['sometimes', 'required', 'string', 'max:100'],
            'autorId' => ['prohibited'],
            'estado' => ['prohibited'],
        ];
    }
}
