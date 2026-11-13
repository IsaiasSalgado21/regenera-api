<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EventoGamificacion;
use App\Services\AvatarService;
use App\Services\GridFsImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct(
        private readonly GridFsImageService $gridFsImages,
        private readonly AvatarService $avatars,
    ) {}

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
                'organizadorVerificado' => (bool) $user->organizadorVerificado,
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
                'organizadorVerificado' => (bool) $usuario->organizadorVerificado,
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
                'foto' => $this->gridFsImages->url($usuario->foto),
                'avatarConfig' => $usuario->avatarConfig,
                'rol' => $usuario->rol,
                'organizadorVerificado' => $usuario->organizadorVerificado,
                'fechaRegistro' => $usuario->fechaRegistro?->toISOString(),
                'estado' => $usuario->estado,
            ],
        ]);
    }

    public function updateAvatar(Request $request)
    {
        $validated = $request->validate([
            'avatarConfig' => ['required', 'json', 'max:2048'],
            'avatar' => ['required', 'image', 'mimes:png', 'max:1024'],
        ]);
        $configuration = json_decode($validated['avatarConfig'], true);
        if (! is_array($configuration)) {
            return response()->json([
                'success' => false,
                'message' => 'La configuración del avatar no es válida.',
            ], 422);
        }

        $user = $request->user();
        $earnedMedals = EventoGamificacion::query()
            ->where('usuarioId', (string) $user->getAuthIdentifier())
            ->where('tipo', 'medalla')
            ->pluck('referenciaId')
            ->map(fn ($slug) => (string) $slug)
            ->all();
        $configuration = $this->avatars->validateConfiguration($configuration, $earnedMedals);
        $newPhoto = $this->gridFsImages->storeUploaded($request->file('avatar'), 'usuarios/avatares');
        $oldPhoto = $user->foto;

        try {
            $user->forceFill([
                'avatarConfig' => $configuration,
                'foto' => $newPhoto,
            ])->save();
        } catch (\Throwable $exception) {
            $this->gridFsImages->deleteStored([$newPhoto]);
            throw $exception;
        }

        if ($oldPhoto && $oldPhoto !== $newPhoto) {
            $this->gridFsImages->deleteStored([$oldPhoto]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Avatar guardado correctamente.',
            'usuario' => [
                'id' => (string) $user->getAuthIdentifier(),
                'avatarConfig' => $configuration,
                'foto' => $this->gridFsImages->url($newPhoto),
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