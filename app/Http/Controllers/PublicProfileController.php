<?php

namespace App\Http\Controllers;

use App\Models\Cuestionario;
use App\Models\EventoGamificacion;
use App\Models\User;
use App\Services\GridFsImageService;
use Illuminate\Http\JsonResponse;

class PublicProfileController extends Controller
{
    public function __construct(private readonly GridFsImageService $images) {}

    public function show(string $id): JsonResponse
    {
        abort_unless(preg_match('/^[a-f0-9]{24}$/i', $id), 404);

        $user = User::find($id);
        abort_if(! $user || $user->estado !== 'activo', 404);

        $events = EventoGamificacion::query()
            ->where('usuarioId', (string) $user->getAuthIdentifier())
            ->where('tipo', 'medalla')
            ->get()
            ->keyBy('referenciaId');

        $medals = Cuestionario::query()
            ->where('activo', true)
            ->orderByDesc('version')
            ->get()
            ->unique('slug')
            ->map(function ($quiz) use ($events): ?array {
                $medal = $quiz->medalla ?? [];
                $slug = (string) ($medal['slug'] ?? '');
                $event = $slug !== '' ? $events->get($slug) : null;
                if (! $event) {
                    return null;
                }

                return [
                    'slug' => $slug,
                    'nombre' => (string) ($medal['nombre'] ?? $slug),
                    'descripcion' => (string) ($medal['descripcion'] ?? ''),
                    'icono' => (string) ($medal['icono'] ?? 'award'),
                    'fechaObtencion' => $event->creadoEn?->toISOString() ?? $event->created_at?->toISOString(),
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (string) $user->getAuthIdentifier(),
                'nombre' => (string) $user->nombre,
                'foto' => $this->images->url($user->foto),
                'medallas' => $medals,
            ],
        ]);
    }
}