<?php

namespace App\Services;

use App\Models\ImpactoVoluntariado;
use App\Services\GridFsImageService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ImpactoVoluntariadoService
{
    public function __construct(private readonly ?GridFsImageService $images = null) {}

    /**
     * @return array<string, array{etiqueta: string, unidad: string, categorias: array<int, string>}>
     */
    public function catalog(): array
    {
        return [
            'residuos_recolectados' => [
                'etiqueta' => 'Basura recolectada',
                'unidad' => 'kg',
                'categorias' => ['limpieza', 'limpieza-de-playas', 'limpieza-de-playa', 'limpieza-urbana', 'limpieza-de-espacios-publicos'],
            ],
            'residuos_reciclados' => [
                'etiqueta' => 'Material separado para reciclar',
                'unidad' => 'kg',
                'categorias' => ['limpieza-de-playas', 'limpieza-de-playa', 'limpieza-urbana', 'limpieza-de-espacios-publicos'],
            ],
            'costa_limpiada' => [
                'etiqueta' => 'Kilómetros de costa limpiados',
                'unidad' => 'km',
                'categorias' => ['limpieza-de-playas', 'limpieza-de-playa', 'limpieza-costera'],
            ],
            'plantas_sembradas' => [
                'etiqueta' => 'Árboles o plantas sembrados',
                'unidad' => 'plantas',
                'categorias' => ['reforestacion', 'reforestacion-comunitaria'],
            ],
            'superficie_reforestada' => [
                'etiqueta' => 'Área reforestada',
                'unidad' => 'm²',
                'categorias' => ['reforestacion', 'reforestacion-comunitaria'],
            ],
            'superficie_restaurada' => [
                'etiqueta' => 'Área restaurada',
                'unidad' => 'm²',
                'categorias' => ['restauracion', 'restauracion-ambiental', 'conservacion'],
            ],
            'especies_restauradas' => [
                'etiqueta' => 'Especies restauradas',
                'unidad' => 'especies',
                'categorias' => ['restauracion', 'restauracion-ambiental'],
            ],
            'especies_protegidas' => [
                'etiqueta' => 'Especies protegidas',
                'unidad' => 'especies',
                'categorias' => ['restauracion', 'restauracion-ambiental', 'conservacion'],
            ],
            'areas_intervenidas' => [
                'etiqueta' => 'Zonas atendidas',
                'unidad' => 'áreas',
                'categorias' => ['limpieza-urbana', 'limpieza-de-espacios-publicos'],
            ],
            'espacios_recuperados' => [
                'etiqueta' => 'Espacios recuperados',
                'unidad' => 'espacios',
                'categorias' => ['limpieza-urbana', 'limpieza-de-espacios-publicos'],
            ],
            'otro' => [
                'etiqueta' => 'Otro resultado',
                'unidad' => '',
                'categorias' => ['*'],
            ],
        ];
    }

    /** @return array<string, array{etiqueta: string, unidad: string}> */
    public function catalogForCategory(?string $categoria): array
    {
        $category = Str::slug($categoria ?? '');
        if (str_contains($category, 'playa') && str_contains($category, 'limp')) {
            $category = 'limpieza-de-playas';
        } elseif (str_contains($category, 'reforest')) {
            $category = 'reforestacion';
        } elseif (str_contains($category, 'restaur')) {
            $category = 'restauracion';
        } elseif (str_contains($category, 'conserv')) {
            $category = 'conservacion';
        } elseif (str_contains($category, 'limpieza') && (str_contains($category, 'urbana') || str_contains($category, 'publicos'))) {
            $category = 'limpieza-urbana';
        } elseif (str_contains($category, 'limpieza')) {
            $category = 'limpieza';
        }

        return collect($this->catalog())
            ->filter(fn (array $metric) => in_array('*', $metric['categorias'], true)
                || in_array($category, $metric['categorias'], true))
            ->map(fn (array $metric) => [
                'etiqueta' => $metric['etiqueta'],
                'unidad' => $metric['unidad'],
            ])
            ->all();
    }

    public function validationRules(?string $categoria = null): array
    {
        $availableTypes = array_keys($categoria === null ? $this->catalog() : $this->catalogForCategory($categoria));

        return [
            'tipo' => ['required', 'string', Rule::in($availableTypes)],
            'cantidad' => ['required', 'numeric', 'gt:0', 'max:1000000000', 'decimal:0,2'],
            'nota' => ['nullable', 'string', 'max:500'],
            'etiquetaPersonalizada' => ['required_if:tipo,otro', 'nullable', 'string', 'max:100'],
            'unidadPersonalizada' => ['required_if:tipo,otro', 'nullable', 'string', 'max:24'],
            'especies' => ['sometimes', 'nullable', 'array', 'max:30'],
            'especies.*' => ['string', 'max:120'],
        ];
    }

    /**
     * @return array{registros: array<int, array<string, mixed>>, totales: array<int, array<string, mixed>>}
     */
    public function present(Collection $records, ?string $categoria = null, bool $includeEvidence = true): array
    {
        $catalog = $this->catalog();
        if ($categoria !== null) {
            $allowedTypes = array_keys($this->catalogForCategory($categoria));
            $records = $records->filter(fn (ImpactoVoluntariado $item) => in_array($item->tipo, $allowedTypes, true));
        }
        $groups = $records->groupBy(fn (ImpactoVoluntariado $item) => $item->tipo === 'otro'
            ? implode('|', ['otro', mb_strtolower(trim((string) $item->etiquetaPersonalizada)), mb_strtolower(trim((string) $item->unidadPersonalizada))])
            : $item->tipo);
        $totals = $groups
            ->map(function (Collection $items): array {
                /** @var ImpactoVoluntariado $first */
                $first = $items->first();
                $type = (string) $first->tipo;
                $label = $type === 'otro'
                    ? (string) $first->etiquetaPersonalizada
                    : ($this->catalog()[$type]['etiqueta'] ?? $type);
                $unit = $type === 'otro'
                    ? (string) $first->unidadPersonalizada
                    : ($this->catalog()[$type]['unidad'] ?? (string) $first->unidad);

                return [
                    'tipo' => $type,
                    'etiqueta' => $label,
                    'unidad' => $unit,
                    'cantidad' => round((float) $items->sum(fn (ImpactoVoluntariado $item) => (float) $item->cantidad), 2),
                    'registros' => $items->count(),
                    'actividades' => $items->pluck('voluntariadoId')->unique()->count(),
                ];
            })
            ->values()
            ->all();

        return [
            'registros' => $records
                ->sortByDesc('created_at')
                ->map(fn (ImpactoVoluntariado $item) => [
                    'id' => (string) $item->_id,
                    'tipo' => $item->tipo,
                    'etiqueta' => $item->tipo === 'otro'
                        ? ($item->etiquetaPersonalizada ?: 'Otro resultado')
                        : ($catalog[$item->tipo]['etiqueta'] ?? $item->tipo),
                    'cantidad' => (float) $item->cantidad,
                    'unidad' => $item->unidad,
                    'nota' => $item->nota,
                    'especies' => $item->especies ?? [],
                    'imagenes' => $includeEvidence && $this->images
                        ? $this->images->urls($item->imagenes ?? [])
                        : [],
                    'registradoEn' => $item->created_at?->toISOString(),
                ])
                ->values()
                ->all(),
            'totales' => $totals,
        ];
    }
}
