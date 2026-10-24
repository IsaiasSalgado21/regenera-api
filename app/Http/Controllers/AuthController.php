<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Registrar un usuario.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'correo' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Los datos enviados no son válidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $correo = strtolower(trim($request->correo));

        // Comprobar si el correo ya existe.
        $usuarioExistente = User::where('correo', $correo)->first();

        if ($usuarioExistente) {
            return response()->json([
                'success' => false,
                'message' => 'El correo electrónico ya está registrado.',
            ], 409);
        }

        // Crear el documento del usuario en MongoDB.
        $user = User::create([
            'nombre' => $request->nombre,
            'correo' => $correo,
            'password' => Hash::make($request->password),
            'foto' => null,
            'rol' => 'usuario',
            'organizadorVerificado' => false,
            'fechaRegistro' => now(),
            'estado' => 'activo',
        ]);
        $token = $user->createToken('regenera-web')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado correctamente.',
            'token' => $token,
            'usuario' => [
                'id' => (string) $user->_id,
                'nombre' => $user->nombre,
                'correo' => $user->correo,
                'rol' => $user->rol,
                'estado' => $user->estado,
            ],
        ], 201);
    }

    /**
     * Iniciar sesión.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'correo' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Los datos enviados no son válidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $correo = strtolower(trim($request->correo));

        $usuario = User::where('correo', $correo)->first();

        if (
            !$usuario ||
            !Hash::check($request->password, $usuario->password)
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Correo o contraseña incorrectos.',
            ], 401);
        }

        if ($usuario->estado !== 'activo') {
            return response()->json([
                'success' => false,
                'message' => 'La cuenta no está activa.',
            ], 403);
        }

        $token = $usuario->createToken('regenera-web')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión correcto.',
            'token' => $token,
            'usuario' => [
                'id' => (string) $usuario->_id,
                'nombre' => $usuario->nombre,
                'correo' => $usuario->correo,
                'rol' => $usuario->rol,
                'estado' => $usuario->estado,
            ],
        ]);
    }

    /**
     * Consultar el usuario autenticado con su token de acceso.
     */
    public function me(Request $request)
    {
        $usuario = $request->user();

        return response()->json([
            'success' => true,
            'usuario' => [
                'id' => (string) $usuario->_id,
                'nombre' => $usuario->nombre,
                'correo' => $usuario->correo,
                'rol' => $usuario->rol,
                'organizadorVerificado' => $usuario->organizadorVerificado,
                'estado' => $usuario->estado,
            ],
        ]);
    }

    /**
     * Revocar el token usado en la solicitud.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}