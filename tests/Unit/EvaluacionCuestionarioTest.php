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
}