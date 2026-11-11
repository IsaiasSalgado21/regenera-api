<?php

namespace App\Services;

use App\Models\ImpactoVoluntariado;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class ImpactoVoluntariadoService
{
    /**
     * @return array<string, array{etiqueta: string, unidad: string}>
     */
    public function catalog(): array
    {
        return [
            'residuos_recolectados' => ['etiqueta' => 'Residuos recolectados', 'unidad' => 'kg'],
            'plantas_sembradas' => ['etiqueta' => 'Plantas sembradas', 'unidad' => 'plantas'],
            'superficie_restaurada' => ['etiqueta' => 'Superficie restaurada', 'unidad' => 'm²'],
            'costa_limpiada' => ['etiqueta' => 'Costa limpiada', 'unidad' => 'km'],
        ];
    }

    public function validationRules(): array
    {
        return [
            'tipo' => ['required', 'string', Rule::in(array_keys($this->catalog()))],
            'cantidad' => ['required', 'numeric', 'gt:0', 'max:1000000000', 'decimal:0,2'],
            'nota' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array{registros: array<int, array<string, mixed>>, totales: array<int, array<string, mixed>>}
     */
    public function present(Collection $records): array
    {
        $catalog = $this->catalog();
        $groups = $records->groupBy('tipo');
        $totals = collect(array_keys($catalog))
            ->filter(fn (string $type) => $groups->has($type))
            ->map(function (string $type) use ($groups, $catalog): array {
                $items = $groups->get($type);

                return [
                    'tipo' => $type,
                    ...$catalog[$type],
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
                    'etiqueta' => $catalog[$item->tipo]['etiqueta'] ?? $item->tipo,
                    'cantidad' => (float) $item->cantidad,
                    'unidad' => $item->unidad,
                    'nota' => $item->nota,
                    'registradoEn' => $item->created_at?->toISOString(),
                ])
                ->values()
                ->all(),
            'totales' => $totals,
        ];
    }
}
