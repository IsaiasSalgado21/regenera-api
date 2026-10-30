<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLugarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'required', 'string', 'max:150'],
            'descripcion' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'municipio' => ['sometimes', 'required', 'string', 'max:100'],
            'direccion' => ['sometimes', 'nullable', 'string', 'max:255'],
            'latitud' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'tipo' => ['sometimes', 'required', Rule::in(['playa', 'parque', 'reserva', 'zona_urbana', 'otro'])],
            'imagen' => ['sometimes', 'nullable', 'file', 'image', 'max:5120'],
            'estado' => ['prohibited'],
            'creadoPor' => ['prohibited'],
        ];
    }
}
