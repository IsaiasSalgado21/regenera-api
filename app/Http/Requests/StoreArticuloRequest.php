<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreArticuloRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['required', 'string', 'max:200'],
            'contenido' => ['required', 'string'],
            'imagen' => ['nullable', 'string', 'max:500'],
            'categoria' => ['required', 'string', 'max:100'],
            'autorId' => ['prohibited'],
            'estado' => ['prohibited'],
        ];
    }
}
