@extends('layouts.app')

@section('title', 'Aprendizaje ambiental · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
@endpush

@section('content')
<main class="learning-page" data-learning-page>
    <div class="learning-shell">
        <header class="learning-heading">
            <div>
                <span class="profile-eyebrow">REGENERA BCS / APRENDIZAJE</span>
                <h1>Aprendizaje ambiental</h1>
                <p>Seguridad, conservación y prácticas regenerativas para actuar en Baja California Sur.</p>
            </div>
            <a class="learning-profile-link" href="{{ route('profile.show') }}">Mi progreso <span aria-hidden="true">↗</span></a>
        </header>

        <div class="learning-access" data-learning-access hidden role="status" aria-live="polite"></div>
        <div class="learning-error" data-learning-error hidden role="alert"></div>

        <section class="learning-summary" aria-label="Resumen de aprendizaje">
            <article><span>Puntos</span><strong data-learning-points>—</strong></article>
            <article><span>Cuestionarios aprobados</span><strong data-learning-approved>—</strong></article>
            <article><span>Medallas</span><strong data-learning-medals>—</strong></article>
            <article><span>Ecosistemas BCS aprendidos</span><strong data-learning-ecosystems>—</strong></article>
            <article><span>Conocimiento en economía circular</span><strong data-learning-circular>—</strong></article>
        </section>

        <section class="learning-catalog-section" aria-labelledby="learningCatalogTitle">
            <div class="learning-section-heading">
                <div><span class="profile-eyebrow">RUTAS DE APRENDIZAJE</span><h2 id="learningCatalogTitle">Cuestionarios</h2></div>
                <span data-learning-catalog-status role="status" aria-live="polite">Cargando cuestionarios…</span>
            </div>
            <div class="learning-course-grid" data-learning-catalog></div>
        </section>

        <section class="learning-player" data-learning-player hidden aria-live="polite">
            <header class="learning-player-heading">
                <div>
                    <span class="profile-eyebrow" data-learning-player-step></span>
                    <h2 data-learning-player-title></h2>
                </div>
                <button class="learning-close-button" type="button" data-learning-close aria-label="Cerrar cuestionario">×</button>
            </header>
            <p class="learning-question" data-learning-question></p>
            <form data-learning-answer-form>
                <fieldset>
                    <legend>Elige una respuesta</legend>
                    <div class="learning-options" data-learning-options></div>
                </fieldset>
                <button class="learning-submit" type="submit">Responder</button>
            </form>
            <section class="learning-feedback" data-learning-feedback hidden aria-live="polite">
                <strong data-learning-feedback-title></strong>
                <p data-learning-correct-answer></p>
                <p data-learning-explanation></p>
                <p class="learning-final-score" data-learning-score hidden></p>
                <div class="learning-feedback-actions">
                    <button class="learning-next-button" type="button" data-learning-next hidden>Siguiente pregunta</button>
                    <a class="learning-return-button" data-learning-return hidden>Volver a la actividad</a>
                    <button class="learning-catalog-button" type="button" data-learning-back>Ver cuestionarios</button>
                </div>
            </section>
        </section>
    </div>
</main>
@endsection

@push('scripts')
    @vite('resources/js/learning.js')
@endpush