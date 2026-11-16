<?php

namespace Tests\Unit;

use App\Services\EvaluacionCuestionario;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EvaluacionCuestionarioTest extends TestCase
{
    private function question(): array
    {
        return [
            'id' => 'equipo',
            'texto' => '¿Qué equipo debes utilizar?',
            'opciones' => ['Guantes y calzado cerrado.', 'Sandalias.'],
            'respuestaCorrecta' => 0,
            'explicacion' => 'El calzado cerrado protege los pies.',
        ];
    }

    #[Test]
    public function public_questions_never_include_the_answer_or_explanation(): void
    {
        $public = (new EvaluacionCuestionario)->preguntasPublicas([$this->question()]);

        self::assertSame([
            ['id' => 'equipo', 'texto' => '¿Qué equipo debes utilizar?', 'opciones' => ['Guantes y calzado cerrado.', 'Sandalias.']],
        ], $public);

        $newQuestions = (new EvaluacionCuestionario)->preguntasPublicas([[
            'id' => 'short', 'tipo' => 'corta', 'texto' => 'Escribe',
            'respuestasAceptadas' => ['Secreto'], 'explicacion' => 'Secreto',
        ]]);
        self::assertSame(['id' => 'short', 'texto' => 'Escribe', 'opciones' => [], 'tipo' => 'corta'], $newQuestions[0]);
    }

    #[Test]
    public function feedback_is_returned_only_by_evaluating_a_submitted_answer(): void
    {
        $evaluator = new EvaluacionCuestionario;

        self::assertSame([
            'correcta' => true,
            'respuestaCorrecta' => 'Guantes y calzado cerrado.',
            'explicacion' => 'El calzado cerrado protege los pies.',
        ], $evaluator->evaluarRespuesta($this->question(), 0));
        self::assertFalse($evaluator->evaluarRespuesta($this->question(), 1)['correcta']);
    }

    #[Test]
    public function invalid_option_indices_are_rejected(): void
    {
        $this->expectException(ValidationException::class);

        (new EvaluacionCuestionario)->evaluarRespuesta($this->question(), 8);
    }

    #[Test]
    public function score_and_threshold_are_calculated_on_the_server(): void
    {
        $evaluator = new EvaluacionCuestionario;
        $score = $evaluator->puntaje(3, 4);

        self::assertSame(75.0, $score);
        self::assertTrue($evaluator->aprobado($score, 75.0));
        self::assertFalse($evaluator->aprobado($score, 80.0));
        self::assertSame(0.0, $evaluator->puntaje(0, 0));
    }

    #[Test]
    public function multiple_answers_receive_partial_credit_with_a_penalty_for_errors(): void
    {
        $evaluator = new EvaluacionCuestionario;
        $question = [
            'tipo' => 'multiple', 'opciones' => ['A', 'B', 'C', 'D'],
            'respuestasCorrectas' => [0, 1, 2], 'explicacion' => 'Explicación',
        ];

        self::assertEqualsWithDelta(2 / 3, $evaluator->evaluarRespuesta($question, [0, 1])['credito'], 0.00001);
        self::assertEqualsWithDelta(1 / 3, $evaluator->evaluarRespuesta($question, [0, 1, 3])['credito'], 0.00001);
        self::assertEquals(0, $evaluator->evaluarRespuesta($question, [3])['credito']);
        self::assertTrue($evaluator->evaluarRespuesta($question, [0, 1, 2])['correcta']);
    }

    #[Test]
    public function short_answers_ignore_case_accents_and_extra_spaces(): void
    {
        $question = [
            'tipo' => 'corta', 'respuestasAceptadas' => ['Bahía Magdalena', 'La Paz'],
            'explicacion' => 'Explicación',
        ];
        $evaluator = new EvaluacionCuestionario;
        self::assertTrue($evaluator->evaluarRespuesta($question, "  BAHIA   magdalena  ")['correcta']);
        self::assertTrue($evaluator->evaluarRespuesta($question, 'la paz')['correcta']);
        self::assertFalse($evaluator->evaluarRespuesta($question, 'Los Cabos')['correcta']);
    }

    #[Test]
    public function weighted_score_preserves_legacy_answers_and_counts_partial_credit(): void
    {
        $score = (new EvaluacionCuestionario)->puntajePonderado([
            ['id' => 'legacy'], ['id' => 'new', 'peso' => 3],
        ], [
            'legacy' => ['correcta' => true],
            'new' => ['correcta' => false, 'credito' => 0.5],
        ]);
        self::assertSame(62.5, $score);
    }
}