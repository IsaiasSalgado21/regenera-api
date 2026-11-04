<?php

namespace App\Http\Requests;

use App\Services\BajaCaliforniaSurBoundary;
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
            'imagenEliminar' => ['sometimes', 'boolean'],
            'estado' => ['prohibited'],
            'creadoPor' => ['prohibited'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                $hasLatitude = $this->exists('latitud');
                $hasLongitude = $this->exists('longitud');
                if (! $hasLatitude && ! $hasLongitude) {
                    return;
                }

                $latitude = $this->input('latitud');
                $longitude = $this->input('longitud');
                if (($latitude === null) !== ($longitude === null)) {
                    $validator->errors()->add('latitud', 'La latitud y longitud deben enviarse juntas.');
                    $validator->errors()->add('longitud', 'La latitud y longitud deben enviarse juntas.');
                    return;
                }

                if ($latitude === null || $longitude === null
                    || $validator->errors()->hasAny(['latitud', 'longitud'])) {
                    return;
                }

                if (! app(BajaCaliforniaSurBoundary::class)->contains($latitude, $longitude)) {
                    $validator->errors()->add('latitud', 'La ubicación debe estar dentro de Baja California Sur, México.');
                    $validator->errors()->add('longitud', 'La ubicación debe estar dentro de Baja California Sur, México.');
                }
            },
        ];
    }
}
