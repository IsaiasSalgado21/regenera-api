<?php

namespace Tests\Unit;

use App\Models\ImpactoVoluntariado;
use App\Services\ImpactoVoluntariadoService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ImpactoVoluntariadoServiceTest extends TestCase
{
    public function test_catalog_uses_fixed_metric_units_and_sums_by_metric(): void
    {
        $service = new ImpactoVoluntariadoService;
        $records = new Collection([
            new ImpactoVoluntariado([
                'voluntariadoId' => 'event-1',
                'tipo' => 'residuos_recolectados',
                'cantidad' => 12.5,
                'unidad' => 'kg',
                'nota' => null,
            ]),
            new ImpactoVoluntariado([
                'voluntariadoId' => 'event-1',
                'tipo' => 'residuos_recolectados',
                'cantidad' => 7.25,
                'unidad' => 'kg',
                'nota' => 'Retiro adicional.',
            ]),
            new ImpactoVoluntariado([
                'voluntariadoId' => 'event-2',
                'tipo' => 'plantas_sembradas',
                'cantidad' => 30,
                'unidad' => 'plantas',
            ]),
        ]);

        $presented = $service->present($records);

        self::assertSame('kg', $service->catalog()['residuos_recolectados']['unidad']);
        self::assertSame(19.75, $presented['totales'][0]['cantidad']);
        self::assertSame(2, $presented['totales'][0]['registros']);
        self::assertSame(1, $presented['totales'][0]['actividades']);
        self::assertSame('Basura recolectada', $presented['registros'][0]['etiqueta']);
    }

    public function test_metric_amount_and_note_are_validated(): void
    {
        $validator = Validator::make([
            'tipo' => 'residuos_recolectados',
            'cantidad' => '2.5',
            'nota' => 'Limpieza de playa',
        ], (new ImpactoVoluntariadoService)->validationRules());

        self::assertTrue($validator->passes(), $validator->errors()->toJson());
    }

    public function test_unknown_units_non_positive_amounts_and_long_notes_are_rejected(): void
    {
        $rules = (new ImpactoVoluntariadoService)->validationRules();
        $validator = Validator::make([
            'tipo' => 'residuos_recolectados',
            'cantidad' => 0,
            'nota' => str_repeat('x', 501),
        ], $rules);
        $unknownMetric = Validator::make([
            'tipo' => 'impacto_inventado',
            'cantidad' => 1,
        ], $rules);

        self::assertFalse($validator->passes());
        self::assertTrue($validator->errors()->has('cantidad'));
        self::assertTrue($validator->errors()->has('nota'));
        self::assertTrue($unknownMetric->errors()->has('tipo'));
    }

    public function test_catalog_only_offers_metrics_relevant_to_activity_category(): void
    {
        $service = new ImpactoVoluntariadoService;
        $beachCleanup = $service->catalogForCategory('limpieza de playas');
        $reforestation = $service->catalogForCategory('Reforestación comunitaria');
        $unknown = $service->catalogForCategory('Educación ambiental');

        self::assertSame([
            'residuos_recolectados',
            'residuos_reciclados',
            'costa_limpiada',
            'otro',
        ], array_keys($beachCleanup));
        self::assertSame([
            'plantas_sembradas',
            'superficie_reforestada',
            'otro',
        ], array_keys($reforestation));
        self::assertSame(['otro'], array_keys($unknown));
    }

    public function test_present_filters_out_metrics_not_relevant_to_activity_category(): void
    {
        $service = new ImpactoVoluntariadoService;
        $records = new Collection([
            new ImpactoVoluntariado([
                'voluntariadoId' => 'event-1',
                'tipo' => 'residuos_recolectados',
                'cantidad' => 12,
                'unidad' => 'kg',
            ]),
            new ImpactoVoluntariado([
                'voluntariadoId' => 'event-1',
                'tipo' => 'plantas_sembradas',
                'cantidad' => 5,
                'unidad' => 'plantas',
            ]),
        ]);

        $presented = $service->present($records, 'reforestacion');

        self::assertCount(1, $presented['totales']);
        self::assertSame('plantas_sembradas', $presented['totales'][0]['tipo']);
    }

    public function test_custom_metric_requires_a_clear_name_and_unit(): void
    {
        $rules = (new ImpactoVoluntariadoService)->validationRules('reforestacion');
        $valid = Validator::make([
            'tipo' => 'otro',
            'cantidad' => 12,
            'etiquetaPersonalizada' => 'Nidos revisados',
            'unidadPersonalizada' => 'nidos',
        ], $rules);
        $missingUnit = Validator::make([
            'tipo' => 'otro',
            'cantidad' => 12,
            'etiquetaPersonalizada' => 'Nidos revisados',
        ], $rules);

        self::assertTrue($valid->passes(), $valid->errors()->toJson());
        self::assertTrue($missingUnit->errors()->has('unidadPersonalizada'));
    }

    public function test_custom_metrics_with_different_labels_are_not_combined(): void
    {
        $records = new Collection([
            new ImpactoVoluntariado([
                'voluntariadoId' => 'event-1',
                'tipo' => 'otro',
                'etiquetaPersonalizada' => 'Nidos revisados',
                'unidadPersonalizada' => 'nidos',
                'cantidad' => 4,
            ]),
            new ImpactoVoluntariado([
                'voluntariadoId' => 'event-1',
                'tipo' => 'otro',
                'etiquetaPersonalizada' => 'Pláticas realizadas',
                'unidadPersonalizada' => 'pláticas',
                'cantidad' => 2,
            ]),
        ]);

        $presented = (new ImpactoVoluntariadoService)->present($records, 'limpieza-urbana');

        self::assertCount(2, $presented['totales']);
        self::assertSame(['Nidos revisados', 'Pláticas realizadas'], array_column($presented['totales'], 'etiqueta'));
    }
}
