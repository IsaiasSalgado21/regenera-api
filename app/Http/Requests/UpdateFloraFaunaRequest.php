<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFloraFaunaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'required', 'string', 'max:150'],
            'nombreCientifico' => ['sometimes', 'nullable', 'string', 'max:150'],
            'tipo' => ['sometimes', 'required', Rule::in(['flora', 'fauna'])],
            'descripcion' => ['sometimes', 'required', 'string', 'max:2000'],
            'habitat' => ['sometimes', 'nullable', 'string', 'max:500'],
            'estadoConservacion' => ['sometimes', 'nullable', 'string', 'max:100'],
            'imagenes' => ['sometimes', 'nullable', 'array'],
            'imagenes.*' => ['file', 'image', 'max:5120'],
            'ubicacion' => ['sometimes', 'nullable', 'array:nombre,latitud,longitud'],
            'ubicacion.nombre' => ['nullable', 'string', 'max:255'],
            'ubicacion.latitud' => ['nullable', 'numeric', 'between:-90,90'],
            'ubicacion.longitud' => ['nullable', 'numeric', 'between:-180,180'],
            'lugaresRelacionados' => ['sometimes', 'nullable', 'array'],
            'lugaresRelacionados.*' => ['string', 'regex:/^[a-f0-9]{24}$/i'],
            'fuenteInformacion' => ['sometimes', 'nullable', 'string', 'max:255'],
            'estado' => ['prohibited'],
            'creadoPor' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                if (! $this->exists('ubicacion')) {
                    return;
                }

                $ubicacion = $this->input('ubicacion', []);
                if (! is_array($ubicacion)) {
                    return;
                }
                $tieneLatitud = isset($ubicacion['latitud']);
                $tieneLongitud = isset($ubicacion['longitud']);

                if ($tieneLatitud !== $tieneLongitud) {
                    $validator->errors()->add('ubicacion', 'La latitud y longitud deben enviarse juntas.');
                }
            },
        ];
    }
}
