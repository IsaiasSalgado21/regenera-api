<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use MongoDB\BSON\Regex;

class AccountProfile
{
    public function assertEmailAvailable(string $email, ?User $user = null, string $field = 'correo'): void
    {
        $pattern = new Regex('^\s*'.preg_quote(strtolower(trim($email)), '/').'\s*$', 'i');
        $matches = User::query()->where(function ($query) use ($pattern) {
            $query->where('correo', $pattern)->orWhere('correoSecundario', $pattern);
        })->get(['_id']);
        if ($matches->contains(fn ($match) => ! $user || (string) $match->getKey() !== (string) $user->getKey())) {
            throw ValidationException::withMessages([$field => 'Este correo ya está asociado a otra cuenta.']);
        }
    }

    private function normalizePhone(?string $phone): ?string
    {
        if ($phone === null || trim($phone) === '') {
            return null;
        }
        $phone = trim($phone);
        if (! preg_match('/^\+?[0-9\s().-]+$/', $phone)) {
            return null;
        }
        $digits = preg_replace('/\D/', '', $phone);
        if (! str_starts_with($phone, '+')) {
            if (strlen($digits) !== 10) {
                return null;
            }
            $digits = '52'.$digits;
        }
        return preg_match('/^[1-9][0-9]{6,14}$/', $digits) ? '+'.$digits : null;
    }

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
        $this->assertEmailAvailable($data['correoSecundario'], $user, 'correoSecundario');
        if (! empty($data['telefono'])) {
            $phone = $this->normalizePhone($data['telefono']);
            if ($phone === null && $data['telefono'] !== $user?->telefono) {
                throw ValidationException::withMessages(['telefono' => 'Escribe un teléfono válido con lada internacional, por ejemplo +52.']);
            }
            if ($phone !== null) {
                foreach (User::query()->select(['_id', 'telefono'])->whereNotNull('telefono')->cursor() as $existing) {
                    if ((! $user || (string) $existing->getKey() !== (string) $user->getKey())
                        && $this->normalizePhone($existing->telefono) === $phone) {
                        throw ValidationException::withMessages(['telefono' => 'Este teléfono ya está asociado a otra cuenta.']);
                    }
                }
                $data['telefono'] = $phone;
            }
        } elseif (array_key_exists('telefono', $data)) {
            $data['telefono'] = null;
        }
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
