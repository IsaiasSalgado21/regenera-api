<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EventoGamificacion;
use App\Services\AvatarService;
use App\Services\GridFsImageService;
use App\Services\AccountProfile;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly GridFsImageService $gridFsImages,
        private readonly AvatarService $avatars,
        private readonly AccountProfile $profiles,
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
        $profile = $this->profiles->validate($request->all(), $correo);

        // Comprobar si el correo ya existe.
        $usuarioExistente = User::where('correo', $correo)->first();

        if ($usuarioExistente) {
            return response()->json([
                'success' => false,
                'message' => 'El correo electrónico ya está registrado.',
            ], 409);
        }

        // Crear el documento del usuario en MongoDB.
        try {
            $user = User::create([
                'nombre' => $request->nombre,
                'correo' => $correo,
                'password' => Hash::make($request->password),
                'foto' => null,
                'rol' => 'usuario',
                'organizadorVerificado' => false,
                'fechaRegistro' => now(),
                'estado' => 'activo',
                ...$profile,
                'perfilCompleto' => true,
            ]);
        } catch (\MongoDB\Driver\Exception\BulkWriteException $exception) {
            if ($exception->getCode() !== 11000) {
                throw $exception;
            }
            throw ValidationException::withMessages(['correo' => 'El correo electrónico ya está registrado.']);
        }
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

        if ($usuario->menorEdadMinima) {
            return response()->json([
                'success' => false,
                'message' => 'No es posible acceder con esta cuenta. Contacta a Regenerabcs@mail.com.',
            ], 403);
        }

        $passwordHash = $usuario->password;
        $accessToken = $usuario->createToken('regenera-web');
        if ($usuario->fresh()?->password !== $passwordHash) {
            $accessToken->accessToken->delete();
            return response()->json([
                'success' => false,
                'message' => 'La contraseña cambió. Vuelve a iniciar sesión.',
            ], 401);
        }
        $token = $accessToken->plainTextToken;

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
                'perfilCompleto' => $this->profiles->complete($usuario),
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
                'perfilCompleto' => $this->profiles->complete($usuario),
                ...$this->profileData($usuario),
            ],
        ]);
    }

    public function completeProfile(Request $request)
    {
        $user = $request->user();
        abort_if($this->profiles->complete($user), 403, 'El perfil ya está completo.');
        $date = Validator::make($request->all(), [
            'fechaNacimiento' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ])->validate()['fechaNacimiento'];
        if (CarbonImmutable::parse($date)->age < 13) {
            $user->forceFill(['menorEdadMinima' => true, 'privacidad' => true])->save();
            return response()->json([
                'message' => 'Debes tener al menos 13 años. Contacta a Regenerabcs@mail.com.',
                'code' => 'edad_minima',
            ], 403);
        }
        $data = $this->profiles->validate($request->all(), $user->correo, $user);
        $user->forceFill([...$data, 'perfilCompleto' => true])->save();

        return response()->json(['success' => true, 'message' => 'Datos guardados correctamente.']);
    }

    public function updateDetails(Request $request)
    {
        $user = $request->user();
        $input = $request->all();
        $input['fechaNacimiento'] = $user->fechaNacimiento;
        $data = $this->profiles->validate($input, $user->correo, $user);
        unset($data['fechaNacimiento']);
        $query = User::whereKey($user->getKey())
            ->where('correo', $user->correo)
            ->where('fechaNacimiento', $user->fechaNacimiento);
        $updated = (clone $query)->update($data);
        abort_if(! $updated && ! $query->exists(), 409, 'Tus datos cambiaron. Recarga la página e inténtalo de nuevo.');
        $user->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Datos actualizados correctamente.',
            'usuario' => $this->profileData($user),
        ]);
    }

    public function requestBirthDate(Request $request)
    {
        $data = $request->validate([
            'fechaNacimiento' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);
        if (CarbonImmutable::parse($data['fechaNacimiento'])->age < 13) {
            throw ValidationException::withMessages([
                'fechaNacimiento' => 'Debes tener al menos 13 años. Contacta a Regenerabcs@mail.com.',
            ]);
        }
        $request->user()->forceFill([
            'solicitudFechaNacimiento' => ['fecha' => $data['fechaNacimiento'], 'estado' => 'pendiente'],
        ])->save();

        return response()->json(['success' => true, 'message' => 'Solicitud enviada para revisión administrativa.']);
    }

    private function profileData(User $user): array
    {
        return [
            'correoSecundario' => $user->correoSecundario,
            'telefono' => $user->telefono,
            'descripcion' => $user->descripcion,
            'ciudad' => $user->ciudad,
            'fechaNacimiento' => $user->fechaNacimiento,
            'genero' => $user->genero,
            'generoOtro' => $user->generoOtro,
            'organizacion' => $user->organizacion,
            'enlacesSociales' => $user->enlacesSociales ?? [],
            'privacidad' => (bool) $user->privacidad,
            'tutor' => $user->tutor,
            'solicitudFechaNacimiento' => $user->solicitudFechaNacimiento,
        ];
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
        ]);
        $name = trim($validated['nombre']);
        if ($name === '') {
            throw ValidationException::withMessages(['nombre' => 'Escribe tu nombre.']);
        }

        $user = $request->user();
        $user->forceFill(['nombre' => $name])->save();

        return response()->json([
            'success' => true,
            'message' => 'Nombre actualizado correctamente.',
            'usuario' => ['nombre' => $user->nombre],
        ]);
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'password_actual' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $user = $request->user();
        if (! Hash::check($validated['password_actual'], $user->password)) {
            throw ValidationException::withMessages(['password_actual' => 'La contraseña actual es incorrecta.']);
        }

        $currentToken = $user->currentAccessToken();
        abort_unless($currentToken instanceof \App\Models\PersonalAccessToken, 403, 'Inicia sesión con tu cuenta para cambiar la contraseña.');

        $user->forceFill(['password' => Hash::make($validated['password'])])->save();
        foreach ($user->tokens()->get() as $token) {
            if ((string) $token->getKey() !== (string) $currentToken->getKey()) {
                $token->delete();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Contraseña actualizada. Las demás sesiones con token se cerraron.',
        ]);
    }

    public function updateEmail(Request $request)
    {
        $validated = $request->validate([
            'correo' => ['required', 'string', 'email', 'max:255'],
            'password_actual' => ['required', 'string'],
        ]);
        $user = $request->user();
        if (! Hash::check($validated['password_actual'], $user->password)) {
            throw ValidationException::withMessages(['password_actual' => 'La contraseña actual es incorrecta.']);
        }

        $email = strtolower(trim($validated['correo']));
        if ($email === strtolower((string) $user->correoSecundario)) {
            throw ValidationException::withMessages(['correo' => 'El correo principal debe ser distinto del secundario.']);
        }
        $existing = User::query()->where('correo', $email)->first();
        if ($existing && (string) $existing->getKey() !== (string) $user->getKey()) {
            throw ValidationException::withMessages(['correo' => 'El correo electrónico ya está registrado.']);
        }
        $query = User::whereKey($user->getKey())->where('correo', $user->correo)
            ->where('correoSecundario', $user->correoSecundario);
        try {
            $updated = (clone $query)->update(['correo' => $email]);
        } catch (\MongoDB\Driver\Exception\BulkWriteException $exception) {
            if ($exception->getCode() !== 11000) {
                throw $exception;
            }
            throw ValidationException::withMessages(['correo' => 'El correo electrónico ya está registrado.']);
        }
        abort_if(! $updated && ! $query->exists(), 409, 'Tus datos cambiaron. Recarga la página e inténtalo de nuevo.');
        $user->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Correo actualizado correctamente.',
            'usuario' => ['correo' => $user->correo],
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