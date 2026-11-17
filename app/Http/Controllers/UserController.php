<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccountProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function __construct(private readonly AccountProfile $profiles) {}
    public function index(): JsonResponse
    {
        $usuarios = User::query()->orderBy('nombre')->get();

        return response()->json([
            'success' => true,
            'data' => $usuarios->map(fn (User $usuario) => $this->present($usuario))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->has('correo')) {
            $request->merge(['correo' => strtolower(trim((string) $request->input('correo')))]);
        }
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'correo' => [
                'required',
                'email',
                'max:255',
                'unique:usuarios,correo',
            ],
            'password' => ['required', 'string', 'min:8'],
            'foto' => ['nullable', 'string', 'max:2048'],
            'rol' => [
                'sometimes',
                Rule::in(['usuario', 'organizador', 'admin']),
            ],
            'organizadorVerificado' => ['sometimes', 'boolean'],
            'estado' => [
                'sometimes',
                Rule::in(['activo', 'inactivo']),
            ],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['rol'] = $validated['rol'] ?? 'usuario';
        $validated['organizadorVerificado'] =
            $validated['organizadorVerificado'] ?? false;
        $validated['fechaRegistro'] = now();
        $validated['estado'] = $validated['estado'] ?? 'activo';
        $validated['correo'] = strtolower(trim($validated['correo']));
        $validated['perfilCompleto'] = false;

        $usuario = User::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado correctamente.',
            'data' => $this->present($usuario),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->present($usuario),
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        if ($request->has('correo')) {
            $request->merge(['correo' => strtolower(trim((string) $request->input('correo')))]);
        }
        $validated = $request->validate([
            'nombre' => ['sometimes', 'required', 'string', 'max:150'],
            'correo' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('usuarios', 'correo')->ignore($id),
            ],
            'password' => ['sometimes', 'required', 'string', 'min:8'],
            'foto' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'rol' => [
                'sometimes',
                Rule::in(['usuario', 'organizador', 'admin']),
            ],
            'organizadorVerificado' => ['sometimes', 'boolean'],
            'estado' => [
                'sometimes',
                Rule::in(['activo', 'inactivo']),
            ],
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }
        if (isset($validated['correo'])) {
            $validated['correo'] = strtolower(trim($validated['correo']));
        }

        if ($request->has('datosPerfil')) {
            $profile = $request->input('datosPerfil');
            if (! is_array($profile)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['datosPerfil' => 'Los datos del perfil no son válidos.']);
            }
            if (isset($validated['rol'])) {
                $usuario->rol = $validated['rol'];
            }
            $profile = $this->profiles->validate($profile, $validated['correo'] ?? $usuario->correo, $usuario);
            $validated = [...$validated, ...$profile, 'perfilCompleto' => true, 'menorEdadMinima' => false];
        } elseif (isset($validated['correo']) && $validated['correo'] === strtolower((string) $usuario->correoSecundario)) {
            throw \Illuminate\Validation\ValidationException::withMessages(['correo' => 'El correo principal debe ser distinto del secundario.']);
        }

        $usuario->fill($validated);
        $usuario->save();
        if (isset($validated['password'])) {
            $currentToken = (string) $usuario->getKey() === (string) $request->user()->getKey()
                ? $request->user()->currentAccessToken()
                : null;
            foreach ($usuario->tokens()->get() as $token) {
                if (! ($currentToken instanceof \App\Models\PersonalAccessToken)
                    || (string) $token->getKey() !== (string) $currentToken->getKey()) {
                    $token->delete();
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente.',
            'data' => $this->present($usuario),
        ]);
    }

    public function reviewBirthDate(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'decision' => ['required', Rule::in(['aprobar', 'rechazar'])],
            'fecha' => ['required', 'date_format:Y-m-d'],
        ]);
        $user = User::findOrFail($id);
        $pending = $user->solicitudFechaNacimiento;
        abort_unless(is_array($pending) && ($pending['estado'] ?? null) === 'pendiente'
            && ($pending['fecha'] ?? null) === $validated['fecha'], 409, 'La solicitud cambió. Recarga la página.');
        $changes = [];
        if ($validated['decision'] === 'aprobar') {
            $profile = $this->profiles->validate([
                'correoSecundario' => $user->correoSecundario,
                'telefono' => $user->telefono,
                'descripcion' => $user->descripcion,
                'ciudad' => $user->ciudad,
                'fechaNacimiento' => $pending['fecha'],
                'genero' => $user->genero,
                'generoOtro' => $user->generoOtro,
                'organizacion' => $user->organizacion,
                'enlacesSociales' => $user->enlacesSociales ?? [],
                'tutor' => $user->tutor,
            ], $user->correo, $user);
            $changes = [
                'fechaNacimiento' => $profile['fechaNacimiento'],
                'privacidad' => $profile['privacidad'],
            ];
        }
        $pending['estado'] = $validated['decision'] === 'aprobar' ? 'aprobada' : 'rechazada';
        $changes['solicitudFechaNacimiento'] = $pending;
        $updated = User::whereKey($user->getKey())
            ->where('solicitudFechaNacimiento.fecha', $validated['fecha'])
            ->where('solicitudFechaNacimiento.estado', 'pendiente')
            ->where('fechaNacimiento', $user->fechaNacimiento)
            ->where('correo', $user->correo)
            ->update($changes);
        abort_if(! $updated, 409, 'Los datos cambiaron. Recarga la página.');

        return response()->json(['message' => 'Solicitud revisada.', 'data' => $this->present($user->fresh())]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $usuario = User::find($id);

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        if ((string) $usuario->getAuthIdentifier() === (string) $request->user()->getAuthIdentifier()) {
            return response()->json([
                'success' => false,
                'message' => 'No puedes eliminar tu propia cuenta de administrador.',
            ], 422);
        }

        $usuario->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado correctamente.',
        ]);
    }

    private function present(User $usuario): array
    {
        return [
            'id' => (string) $usuario->getAuthIdentifier(),
            'nombre' => $usuario->nombre,
            'correo' => $usuario->correo,
            'foto' => $usuario->foto,
            'rol' => $usuario->rol,
            'organizadorVerificado' => (bool) $usuario->organizadorVerificado,
            'fechaRegistro' => $usuario->fechaRegistro?->toISOString(),
            'estado' => $usuario->estado,
            'correoSecundario' => $usuario->correoSecundario,
            'telefono' => $usuario->telefono,
            'descripcion' => $usuario->descripcion,
            'ciudad' => $usuario->ciudad,
            'fechaNacimiento' => $usuario->fechaNacimiento,
            'genero' => $usuario->genero,
            'generoOtro' => $usuario->generoOtro,
            'organizacion' => $usuario->organizacion,
            'enlacesSociales' => $usuario->enlacesSociales ?? [],
            'privacidad' => (bool) $usuario->privacidad,
            'tutor' => $usuario->tutor,
            'perfilCompleto' => (bool) $usuario->perfilCompleto,
            'menorEdadMinima' => (bool) $usuario->menorEdadMinima,
            'solicitudFechaNacimiento' => $usuario->solicitudFechaNacimiento,
        ];
    }
}
