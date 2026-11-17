<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AccountProfile
{
    public function validate(array $input, string $email, ?User $user = null): array
    {
        $validator = Validator::make($input, [
            'correoSecundario' => ['required', 'string', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:40'],
            'descripcion' => ['nullable', 'string', 'max:1000'],
            'ciudad' => ['required', 'string', 'max:150'],
            'fechaNacimiento' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'genero' => ['required', Rule::in(['mujer', 'hombre', 'otro', 'prefiero_no_decirlo'])],
            'generoOtro' => ['nullable', 'string', 'max:100'],
            'organizacion' => ['nullable', 'string', 'max:150'],
            'enlacesSociales' => ['nullable', 'array', 'max:5'],
            'enlacesSociales.*' => ['required', 'url:http,https', 'max:2048'],
            'privacidad' => ['sometimes', 'boolean'],
            'tutor' => ['nullable', 'array:nombre,correo,telefono,fechaNacimiento,autorizacion'],
            'tutor.nombre' => ['nullable', 'string', 'max:150'],
            'tutor.correo' => ['nullable', 'email', 'max:255'],
            'tutor.telefono' => ['nullable', 'string', 'max:40'],
            'tutor.fechaNacimiento' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'tutor.autorizacion' => ['nullable', 'boolean'],
        ]);
        $validator->after(function ($validator) use ($input, $email): void {
            $secondary = $input['correoSecundario'] ?? null;
            if (is_string($secondary) && strtolower(trim($secondary)) === strtolower(trim($email))) {
                $validator->errors()->add('correoSecundario', 'El correo secundario debe ser distinto del principal.');
            }
            if (isset($input['ciudad']) && is_string($input['ciudad']) && trim($input['ciudad']) === '') {
                $validator->errors()->add('ciudad', 'Escribe tu ciudad.');
            }
            if ($validator->errors()->has('fechaNacimiento') || empty($input['fechaNacimiento'])) {
                return;
            }
            $age = CarbonImmutable::parse($input['fechaNacimiento'])->age;
            if ($age < 13) {
                $validator->errors()->add('fechaNacimiento', 'Debes tener al menos 13 años. Contacta a Regenerabcs@mail.com.');
                return;
            }
            if ($age < 18) {
                foreach (['nombre', 'correo', 'telefono', 'fechaNacimiento'] as $field) {
                    $value = data_get($input, "tutor.$field");
                    if (! is_string($value) || trim($value) === '') {
                        $validator->errors()->add("tutor.$field", 'Este dato del tutor es obligatorio.');
                    }
                }
                if (! data_get($input, 'tutor.autorizacion')) {
                    $validator->errors()->add('tutor.autorizacion', 'Declara que cuentas con autorización de tu tutor.');
                }
                $birth = data_get($input, 'tutor.fechaNacimiento');
                if ($birth && ! $validator->errors()->has('tutor.fechaNacimiento')
                    && CarbonImmutable::parse($birth)->age < 18) {
                    $validator->errors()->add('tutor.fechaNacimiento', 'El tutor debe ser mayor de edad.');
                }
                if (array_key_exists('privacidad', $input) && ! $input['privacidad']) {
                    $validator->errors()->add('privacidad', 'La privacidad es obligatoria para menores de edad.');
                }
            }
        });
        $data = $validator->validate();
        $data['correoSecundario'] = strtolower(trim($data['correoSecundario']));
        $data['ciudad'] = trim($data['ciudad']);
        $data['generoOtro'] = $data['genero'] === 'otro' ? ($data['generoOtro'] ?? null) : null;
        $data['enlacesSociales'] = array_values($data['enlacesSociales'] ?? []);
        $data['privacidad'] = CarbonImmutable::parse($data['fechaNacimiento'])->age < 18
            ? true
            : ($data['privacidad'] ?? $user?->privacidad ?? false);
        $data['tutor'] = CarbonImmutable::parse($data['fechaNacimiento'])->age < 18
            ? $data['tutor'] : ($user?->tutor ?? null);
        if (($user?->rol ?? 'usuario') !== 'organizador') {
            unset($data['organizacion'], $data['enlacesSociales']);
        }

        return $data;
    }

    public function complete(User $user): bool
    {
        return (bool) ($user->perfilCompleto ?? false);
    }

    public function minor(User $user): bool
    {
        return $user->fechaNacimiento && CarbonImmutable::parse($user->fechaNacimiento)->age < 18;
    }
}
