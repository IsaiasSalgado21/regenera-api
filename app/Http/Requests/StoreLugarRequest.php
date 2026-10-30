<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLugarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string', 'max:2000'],
            'municipio' => ['required', 'string', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'longitud' => ['nullable', 'numeric', 'between:-180,180'],
            'tipo' => ['required', Rule::in(['playa', 'parque', 'reserva', 'zona_urbana', 'otro'])],
            'imagen' => ['nullable', 'file', 'image', 'max:5120'],
            'estado' => ['prohibited'],
            'creadoPor' => ['prohibited'],
        ];
    }
}
