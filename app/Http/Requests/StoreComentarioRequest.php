<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComentarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'contenido' => ['required', 'string', 'max:1000'],
            'es_privado' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'contenido.required' => 'El contenido del comentario es obligatorio.',
            'contenido.string' => 'El contenido debe ser texto.',
            'contenido.max' => 'El comentario no puede exceder 1000 caracteres.',
            'es_privado.boolean' => 'El campo es_privado debe ser verdadero o falso.',
        ];
    }
}
