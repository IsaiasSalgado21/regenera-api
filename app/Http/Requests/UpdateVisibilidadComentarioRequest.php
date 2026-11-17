<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVisibilidadComentarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'es_publicado' => ['required', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'es_publicado.required' => 'Debes indicar si el comentario se publica.',
            'es_publicado.boolean' => 'El campo es_publicado debe ser verdadero o falso.',
        ];
    }
}
