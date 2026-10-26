@extends('layouts.app')

@section('title', ($especie->nombre ?? 'Especie') . ' · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contenido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages-detail.css') }}">
    <link rel="stylesheet" href="{{ asset('css/flora-detail.css') }}">
@endpush
@section('content')
@php
    $imagenes = is_array($especie->imagenes)
        ? $especie->imagenes
        : [];

    $ubicacion = is_array($especie->ubicacion)
        ? $especie->ubicacion
        : [];

    $estadoConservacion = $especie->getAttribute('estadoConservacion')
        ?? $especie->getAttribute('estadoConservation');

    $fuentes = $especie->fuenteInformacion ?? null;

    $nombreCientifico = $especie->nombreCientifico ?? null;
    $tipo = strtolower((string) ($especie->tipo ?? ''));

    $tipoLabel = match ($tipo) {
        'flora' => 'Flora',
        'fauna' => 'Fauna',
        default => 'Especie',
    };
@endphp

<main class="page flora-species-detail">

    <header class="flora-detail-header">
        <a href="{{ url('/flora') }}" class="flora-detail-back">
            <span aria-hidden="true">←</span>
            Volver a flora y fauna
        </a>

        <div class="flora-detail-heading">
            <span class="flora-detail-eyebrow">
                REGISTRO NATURAL · BAJA CALIFORNIA SUR
            </span>

            <div class="flora-detail-title-row">
                <div>
                    <span class="flora-detail-type">
                        {{ $tipoLabel }}
                    </span>

                    <h1>{{ $especie->nombre }}</h1>

                    @if ($nombreCientifico)
                        <p class="flora-scientific-name">
                            {{ $nombreCientifico }}
                        </p>
                    @endif
                </div>

                @if ($estadoConservacion)
                    <div class="flora-detail-status">
                        <span>Conservación</span>
                        <strong>{{ $estadoConservacion }}</strong>
                    </div>
                @endif
            </div>
        </div>

        @if (!empty($imagenes))
            <div
                class="flora-detail-cover"
                style="background-image: url('{{ $imagenes[0] }}')"
                role="img"
                aria-label="Imagen de {{ $especie->nombre }}"
            ></div>
        @else
            <div
                class="flora-detail-cover flora-detail-cover--placeholder"
                role="img"
                aria-label="Imagen no disponible de {{ $especie->nombre }}"
            >
                <span aria-hidden="true">🌿</span>
            </div>
        @endif
    </header>

    <section class="flora-detail-content">

        <div class="flora-detail-intro">
            <span class="flora-detail-eyebrow">
                CONOCE SU ENTORNO
            </span>

            <h2>
                Cada especie forma parte del equilibrio natural.
            </h2>

            <p>
                Explora la información disponible sobre esta especie
                y conoce su hábitat, distribución y estado de conservación.
            </p>
        </div>

        <div class="flora-stage-navigation" data-flora-navigation>
            <div
                class="flora-stage-progress"
                role="progressbar"
                aria-label="Progreso de la ficha"
                aria-valuemin="1"
                aria-valuemax="4"
                aria-valuenow="1"
                data-stage-progress
            >
                <span data-stage-progress-bar></span>
            </div>

            <div class="flora-stage-steps" role="group" aria-label="Etapas de la ficha">
                <button
                    type="button"
                    class="flora-stage-step is-active"
                    data-stage-target="0"
                    aria-controls="flora-stage-1"
                    aria-current="step"
                >
                    <span class="flora-stage-number">01</span>
                    <span class="flora-stage-step-label">Identificación</span>
                </button>

                <button
                    type="button"
                    class="flora-stage-step"
                    data-stage-target="1"
                    aria-controls="flora-stage-2"
                >
                    <span class="flora-stage-number">02</span>
                    <span class="flora-stage-step-label">Descripción</span>
                </button>

                <button
                    type="button"
                    class="flora-stage-step"
                    data-stage-target="2"
                    aria-controls="flora-stage-3"
                >
                    <span class="flora-stage-number">03</span>
                    <span class="flora-stage-step-label">Distribución</span>
                </button>

                <button
                    type="button"
                    class="flora-stage-step"
                    data-stage-target="3"
                    aria-controls="flora-stage-4"
                >
                    <span class="flora-stage-number">04</span>
                    <span class="flora-stage-step-label">Conservación</span>
                </button>
            </div>
        </div>

        <div class="flora-detail-stages" data-flora-stages>

            {{-- ETAPA 1: IDENTIFICACIÓN --}}
            <article
                id="flora-stage-1"
                class="flora-detail-stage"
                data-stage-panel
                aria-labelledby="flora-stage-heading-1"
            >
                <span class="flora-stage-kicker">ETAPA 01 / 04</span>

                <h2 id="flora-stage-heading-1">
                    Identificación de la especie
                </h2>

                <p class="flora-stage-description">
                    Conoce el nombre y la clasificación de este elemento
                    de la biodiversidad sudcaliforniana.
                </p>

                <div class="flora-stage-data-grid">
                    <div class="flora-stage-data">
                        <span>Nombre común</span>
                        <strong>{{ $especie->nombre }}</strong>
                    </div>

                    <div class="flora-stage-data">
                        <span>Nombre científico</span>
                        <strong>
                            @if ($nombreCientifico)
                                <em>{{ $nombreCientifico }}</em>
                            @else
                                No disponible en el registro
                            @endif
                        </strong>
                    </div>

                    <div class="flora-stage-data">
                        <span>Clasificación</span>
                        <strong>{{ $tipoLabel }}</strong>
                    </div>
                </div>

                @if (count($imagenes) > 1)
                    <div class="flora-stage-gallery">
                        <h3>Galería de imágenes</h3>

                        <div class="especie-galeria">
                            @foreach (array_slice($imagenes, 1) as $imagen)
                                <div
                                    class="especie-galeria-item"
                                    style="background-image: url('{{ $imagen }}')"
                                    role="img"
                                    aria-label="Otra imagen de {{ $especie->nombre }}"
                                    loading="lazy"
                                ></div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </article>

            {{-- ETAPA 2: DESCRIPCIÓN --}}
            <article
                id="flora-stage-2"
                class="flora-detail-stage"
                data-stage-panel
                aria-labelledby="flora-stage-heading-2"
                hidden
            >
                <span class="flora-stage-kicker">ETAPA 02 / 04</span>

                <h2 id="flora-stage-heading-2">
                    Descripción y hábitat
                </h2>

                <p class="flora-stage-description">
                    Características generales y entorno natural registrado
                    para esta especie.
                </p>

                <div class="flora-stage-information">
                    <div>
                        <span class="flora-stage-section-label">
                            DESCRIPCIÓN
                        </span>

                        @if (filled($especie->descripcion))
                            <p>{{ $especie->descripcion }}</p>
                        @else
                            <p class="flora-stage-unavailable">
                                Todavía no hay una descripción registrada.
                            </p>
                        @endif
                    </div>

                    <div>
                        <span class="flora-stage-section-label">
                            HÁBITAT
                        </span>

                        @if (filled($especie->habitat))
                            <p>{{ $especie->habitat }}</p>
                        @else
                            <p class="flora-stage-unavailable">
                                Todavía no hay información del hábitat.
                            </p>
                        @endif
                    </div>
                </div>
            </article>

            {{-- ETAPA 3: DISTRIBUCIÓN --}}
            <article
                id="flora-stage-3"
                class="flora-detail-stage"
                data-stage-panel
                aria-labelledby="flora-stage-heading-3"
                hidden
            >
                <span class="flora-stage-kicker">ETAPA 03 / 04</span>

                <h2 id="flora-stage-heading-3">
                    Distribución y ubicación
                </h2>

                <p class="flora-stage-description">
                    Consulta los datos geográficos disponibles para conocer
                    el entorno donde se registra esta especie.
                </p>

                @if (!empty($ubicacion))
                    <div class="flora-location-card">
                        <span class="flora-stage-section-label">
                            UBICACIÓN REGISTRADA
                        </span>

                        <div class="flora-location-list">
                            @foreach ($ubicacion as $clave => $valor)
                                @if (is_scalar($valor) && filled($valor))
                                    <div class="flora-location-item">
                                        <span>
                                            {{ ucfirst(str_replace(
                                                ['_', '-'],
                                                ' ',
                                                (string) $clave
                                            )) }}
                                        </span>

                                        <strong>{{ $valor }}</strong>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        @if (!empty($ubicacion['latitud']) && !empty($ubicacion['longitud']))
                            <p class="flora-location-note">
                                Las coordenadas mostradas corresponden
                                al nivel de precisión disponible en el registro.
                            </p>
                        @endif
                    </div>
                @else
                    <div class="flora-stage-empty">
                        <span aria-hidden="true">⌖</span>

                        <h3>Ubicación pendiente de registro</h3>

                        <p>
                            Aún no hay datos de ubicación disponibles
                            para mostrar en esta ficha.
                        </p>
                    </div>
                @endif
            </article>

            {{-- ETAPA 4: CONSERVACIÓN --}}
            <article
                id="flora-stage-4"
                class="flora-detail-stage"
                data-stage-panel
                aria-labelledby="flora-stage-heading-4"
                hidden
            >
                <span class="flora-stage-kicker">ETAPA 04 / 04</span>

                <h2 id="flora-stage-heading-4">
                    Conservación y fuentes
                </h2>

                <p class="flora-stage-description">
                    Revisa el estado de conservación registrado y la
                    procedencia de la información utilizada en esta ficha.
                </p>

                <div class="flora-conservation-card">
                    <span class="flora-stage-section-label">
                        ESTADO DE CONSERVACIÓN
                    </span>

                    @if (filled($estadoConservacion))
                        <h3>{{ $estadoConservacion }}</h3>
                    @else
                        <h3>Sin información registrada</h3>

                        <p>
                            El estado de conservación todavía no está
                            especificado en esta ficha.
                        </p>
                    @endif
                </div>

                <div class="flora-source-section">
                    <span class="flora-stage-section-label">
                        FUENTES DE INFORMACIÓN
                    </span>

                    @if (is_array($fuentes) || $fuentes instanceof \Traversable)
                        @php
                            $listaFuentes = collect($fuentes)
                                ->filter(fn ($fuente) => is_scalar($fuente) && filled($fuente));
                        @endphp

                        @if ($listaFuentes->isNotEmpty())
                            <ul class="flora-source-list">
                                @foreach ($listaFuentes as $fuente)
                                    <li>{{ $fuente }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="flora-stage-unavailable">
                                No hay fuentes registradas.
                            </p>
                        @endif
                    @elseif (is_string($fuentes) && filled($fuentes))
                        <p>{{ $fuentes }}</p>
                    @else
                        <p class="flora-stage-unavailable">
                            No hay fuentes de información registradas.
                        </p>
                    @endif
                </div>
            </article>

        </div>

        <div class="flora-stage-controls" data-stage-controls>
            <button
                type="button"
                class="flora-stage-arrow flora-stage-arrow--previous"
                data-stage-previous
                disabled
            >
                <span aria-hidden="true">←</span>
                Anterior
            </button>

            <span
                class="flora-stage-counter"
                data-stage-counter
                aria-live="polite"
            >
                Etapa 1 de 4
            </span>

            <button
                type="button"
                class="flora-stage-arrow flora-stage-arrow--next"
                data-stage-next
            >
                Siguiente
                <span aria-hidden="true">→</span>
            </button>
        </div>

        <div class="flora-detail-back-bottom">
            <a href="{{ url('/flora') }}">
                ← Volver al listado de flora y fauna
            </a>
        </div>

    </section>
</main>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const navigation = document.querySelector('[data-flora-navigation]');
    const stagesContainer = document.querySelector('[data-flora-stages]');

    if (!navigation || !stagesContainer) return;

    const panels = [...stagesContainer.querySelectorAll('[data-stage-panel]')];
    const steps = [...navigation.querySelectorAll('[data-stage-target]')];

    const previousButton = document.querySelector('[data-stage-previous]');
    const nextButton = document.querySelector('[data-stage-next]');
    const counter = document.querySelector('[data-stage-counter]');
    const progress = navigation.querySelector('[data-stage-progress]');
    const progressBar = navigation.querySelector('[data-stage-progress-bar]');

    if (
        panels.length !== 4 ||
        steps.length !== 4 ||
        !previousButton ||
        !nextButton ||
        !counter ||
        !progress ||
        !progressBar
    ) {
        return;
    }

    let currentStage = 0;

    // Solo ocultamos las etapas cuando confirmamos que la navegación funciona.
    stagesContainer.classList.add('flora-stages-enabled');

    function showStage(index, moveFocus = false) {
        currentStage = Math.max(0, Math.min(index, panels.length - 1));

        panels.forEach((panel, position) => {
            const isActive = position === currentStage;

            panel.hidden = !isActive;
            panel.setAttribute('aria-hidden', String(!isActive));
        });

        steps.forEach((step, position) => {
            const isActive = position === currentStage;

            step.classList.toggle('is-active', isActive);

            if (isActive) {
                step.setAttribute('aria-current', 'step');
            } else {
                step.removeAttribute('aria-current');
            }
        });

        previousButton.disabled = currentStage === 0;
        nextButton.disabled = currentStage === panels.length - 1;

        counter.textContent = `Etapa ${currentStage + 1} de ${panels.length}`;

        progress.setAttribute('aria-valuenow', String(currentStage + 1));
        progressBar.style.width =
            `${((currentStage + 1) / panels.length) * 100}%`;

        if (moveFocus) {
            const heading = panels[currentStage].querySelector('h2');

            if (heading) {
                heading.setAttribute('tabindex', '-1');
                heading.focus({ preventScroll: true });
            }

            const bounds = navigation.getBoundingClientRect();

            if (bounds.top < 0 || bounds.top > window.innerHeight) {
                navigation.scrollIntoView({
                    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches
                        ? 'auto'
                        : 'smooth',
                    block: 'start'
                });
            }
        }
    }

    steps.forEach((step) => {
        step.addEventListener('click', () => {
            showStage(Number(step.dataset.stageTarget), true);
        });
    });

    previousButton.addEventListener('click', () => {
        showStage(currentStage - 1, true);
    });

    nextButton.addEventListener('click', () => {
        showStage(currentStage + 1, true);
    });

    showStage(0);
});
</script>
@endpush