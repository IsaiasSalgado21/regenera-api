<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateConfigComentariosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comentarios_por_defecto' => ['required', 'string', Rule::in(['publico', 'privado'])],
        ];
    }

    public function messages(): array
    {
        return [
            'comentarios_por_defecto.required' => 'Debes indicar la visibilidad predeterminada.',
            'comentarios_por_defecto.in' => 'La visibilidad predeterminada debe ser publico o privado.',
        ];
    }
}
