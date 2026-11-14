<?php

namespace App\Http\Controllers;

use App\Models\Cuestionario;
use App\Models\EventoGamificacion;
use App\Models\IntentoCuestionario;
use App\Models\RespuestaCuestionario;
use App\Models\User;
use App\Services\CuestionarioService;
use App\Services\GamificacionService;
use Illuminate\Http\Request;

class GamificacionController extends Controller
{
    public function __construct(
        private readonly CuestionarioService $cuestionarios,
        private readonly GamificacionService $gamificacion,
    ) {}

    public function cuestionarios(Request $request)
    {
        return response()->json([
            'data' => $this->cuestionarios->catalogo($request->user()),
        ]);
    }

    public function iniciar(Request $request, string $slug)
    {
        $validated = $request->validate([
            'actividadId' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'data' => $this->cuestionarios->iniciar(
                $request->user(),
                $slug,
                $validated['actividadId'] ?? null,
            ),
        ], 201);
    }

    public function responder(Request $request, string $attemptId)
    {
        $validated = $request->validate([
            'preguntaId' => ['required', 'string', 'max:80'],
            'opcion' => ['required', 'integer', 'min:0', 'max:20'],
        ]);
        $result = $this->cuestionarios->responder(
            $request->user(),
            $attemptId,
            $validated['preguntaId'],
            (int) $validated['opcion'],
        );

        if ($result['estado'] === 'aprobado') {
            $attempt = IntentoCuestionario::find($result['intentoId']);
            $quiz = $attempt
                ? Cuestionario::query()
                    ->where('slug', $attempt->cuestionarioSlug)
                    ->where('version', $attempt->cuestionarioVersion)
                    ->first()
                : null;
            if ($attempt && $quiz) {
                $this->gamificacion->registrarAprobacion($request->user(), $quiz, $attempt);
            }
        }

        return response()->json(['data' => $result]);
    }

    public function progreso(Request $request)
    {
        return response()->json([
            'data' => $this->gamificacion->progreso($request->user()),
        ]);
    }

    public function estadisticas()
    {
        $attempts = IntentoCuestionario::query()->get();
        $events = EventoGamificacion::query()->get();
        $quizzes = Cuestionario::query()->where('activo', true)->get();
        $users = User::query()->get(['_id', 'nombre', 'correo'])->keyBy(fn ($user) => (string) $user->_id);
        $certifications = $events->where('tipo', 'certificacion')->pluck('usuarioId')->unique()->count();

        return response()->json([
            'data' => [
                'cuestionarios' => $quizzes->map(fn ($quiz) => [
                    'slug' => (string) $quiz->slug,
                    'titulo' => (string) $quiz->titulo,
                    'intentos' => $attempts->where('cuestionarioSlug', $quiz->slug)->count(),
                    'aprobados' => $attempts
                        ->where('cuestionarioSlug', $quiz->slug)
                        ->where('estado', 'aprobado')
                        ->count(),
                ])->values(),
                'puntosOtorgados' => (int) $events->where('tipo', 'puntos')->sum('cantidad'),
                'medallasDesbloqueadas' => $events->where('tipo', 'medalla')->count(),
                'organizadoresCertificados' => $certifications,
                'intentosRecientes' => $attempts
                    ->sortByDesc('created_at')
                    ->take(100)
                    ->map(fn ($attempt) => [
                        'id' => (string) $attempt->getKey(),
                        'usuarioId' => (string) $attempt->usuarioId,
                        'usuarioNombre' => $users->get((string) $attempt->usuarioId)?->nombre ?? 'Cuenta no disponible',
                        'usuarioCorreo' => $users->get((string) $attempt->usuarioId)?->correo,
                        'cuestionarioSlug' => (string) $attempt->cuestionarioSlug,
                        'estado' => (string) $attempt->estado,
                        'puntuacion' => $attempt->puntuacion === null ? null : (float) $attempt->puntuacion,
                        'fechaUltimoIntento' => $attempt->fechaUltimoIntento?->toISOString(),
                        'fechaAprobacion' => $attempt->fechaAprobacion?->toISOString(),
                    ])
                    ->values(),
            ],
        ]);
    }

    public function detalleIntento(string $attemptId)
    {
        $attempt = IntentoCuestionario::findOrFail($attemptId);
        $quiz = Cuestionario::query()
            ->where('slug', $attempt->cuestionarioSlug)
            ->where('version', $attempt->cuestionarioVersion)
            ->firstOrFail();
        $answers = RespuestaCuestionario::query()
            ->where('intentoId', (string) $attempt->getKey())
            ->where('usuarioId', (string) $attempt->usuarioId)
            ->get()
            ->keyBy(fn (RespuestaCuestionario $answer) => (string) $answer->preguntaId);
        $user = User::find((string) $attempt->usuarioId);

        return response()->json([
            'data' => [
                'id' => (string) $attempt->getKey(),
                'usuarioNombre' => $user?->nombre ?? 'Cuenta no disponible',
                'cuestionarioTitulo' => (string) $quiz->titulo,
                'cuestionarioVersion' => (int) $quiz->version,
                'estado' => (string) $attempt->estado,
                'puntuacion' => $attempt->puntuacion === null ? null : (float) $attempt->puntuacion,
                'preguntas' => collect($quiz->preguntas ?? [])
                    ->map(function (array $question) use ($answers): array {
                        $answer = $answers->get((string) $question['id']);
                        $options = array_values($question['opciones'] ?? []);
                        $selectedIndex = $answer?->opcion;
                        $correctIndex = $question['respuestaCorrecta'] ?? null;

                        return [
                            'id' => (string) $question['id'],
                            'texto' => (string) $question['texto'],
                            'respuestaElegida' => $selectedIndex !== null ? ($options[(int) $selectedIndex] ?? null) : null,
                            'respuestaCorrecta' => $correctIndex !== null ? ($options[(int) $correctIndex] ?? null) : null,
                            'correcta' => $answer ? (bool) $answer->correcta : null,
                            'explicacion' => (string) ($answer?->explicacion ?? $question['explicacion'] ?? ''),
                            'respondidaEn' => $answer?->respondidaEn?->toISOString(),
                        ];
                    })
                    ->values(),
            ],
        ]);
    }
}