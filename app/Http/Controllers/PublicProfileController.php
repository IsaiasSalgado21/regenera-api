<?php

namespace App\Http\Controllers;

use App\Models\Cuestionario;
use App\Models\EventoGamificacion;
use App\Models\User;
use App\Services\GridFsImageService;
use App\Services\AccountProfile;
use App\Services\CatalogoMedallas;
use Illuminate\Http\JsonResponse;

class PublicProfileController extends Controller
{
    public function __construct(
        private readonly GridFsImageService $images,
        private readonly AccountProfile $profiles,
        private readonly CatalogoMedallas $catalogoMedallas,
    ) {}

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

        $catalogo = $this->catalogoMedallas->todas();
        $medals = Cuestionario::query()
            ->orderByDesc('version')
            ->get()
            ->unique(fn ($quiz) => (string) ($quiz->medalla['slug'] ?? ''))
            ->map(function ($quiz) use ($events, $catalogo): ?array {
                $medal = $quiz->medalla ?? [];
                $slug = (string) ($medal['slug'] ?? '');
                $event = $slug !== '' ? $events->get($slug) : null;
                if (! $event) {
                    return null;
                }
                $medal = $catalogo->get($slug) ?? $medal;

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
        $visibleMedals = $medals->pluck('slug')->all();
        foreach ($events as $slug => $event) {
            $medal = $catalogo->get((string) $slug);
            if (! $medal || in_array((string) $slug, $visibleMedals, true)) {
                continue;
            }
            $medals->push([
                'slug' => (string) $slug,
                'nombre' => $medal['nombre'],
                'descripcion' => $medal['descripcion'],
                'icono' => $medal['icono'],
                'fechaObtencion' => $event->creadoEn?->toISOString() ?? $event->created_at?->toISOString(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => (string) $user->getAuthIdentifier(),
                'nombre' => (string) $user->nombre,
                'foto' => $this->images->url($user->foto),
                'medallas' => $medals,
                ...($this->profiles->complete($user) && ! $this->profiles->minor($user) && ! $user->privacidad
                    ? [
                        'descripcion' => $user->descripcion,
                        'organizacion' => $user->rol === 'organizador' ? $user->organizacion : null,
                        'enlacesSociales' => $user->rol === 'organizador' ? ($user->enlacesSociales ?? []) : [],
                    ]
                    : []),
            ],
        ]);
    }
}