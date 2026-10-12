<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado correctamente.',
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

        // En esta prueba solo confirmamos las credenciales.
        // Todavía no generamos un token ni una sesión persistente.

        return response()->json([
            'success' => true,
            'message' => 'Credenciales verificadas correctamente.',
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
     * Consultar el usuario autenticado por sesión.
     *
     * Este método solo funcionará cuando la autenticación
     * por sesión esté configurada para la ruta.
     */
    public function me()
    {
        $usuario = Auth::user();

        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'No hay un usuario autenticado.',
            ], 401);
        }

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
     * Cerrar sesión.
     *
     * Este método requiere una sesión válida.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}