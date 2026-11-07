<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
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

        $usuario->fill($validated);
        $usuario->save();

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente.',
            'data' => $usuario->makeHidden(['password']),
        ]);
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
        ];
    }
}
