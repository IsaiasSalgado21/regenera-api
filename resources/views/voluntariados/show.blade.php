@extends('layouts.app')

@section('title', $voluntariado->titulo . ' · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contenido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/voluntariados.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages-detail.css') }}">
@endpush

@section('content')
<main class="page volunteer-detail-page">
    <section class="page-hero">
        @if ($voluntariado->imagen)
            <div class="page-hero-background" style="background-image: url('{{ $voluntariado->imagen }}')"></div>
        @else
            <div class="page-hero-background volunteer-detail-placeholder"></div>
        @endif
        <div class="page-hero-overlay"></div>
        <div class="page-hero-content volunteer-detail-hero-content">
            @if ($voluntariado->categoria)
                <span class="volunteer-detail-category">{{ ucfirst(str_replace('-', ' ', $voluntariado->categoria)) }}</span>
            @endif
            <span class="page-label">PARTICIPA</span>
            <h1>{{ $voluntariado->titulo }}</h1>
            <p>{{ \Illuminate\Support\Str::limit($voluntariado->descripcion, 180) }}</p>
        </div>
    </section>

    <section class="page-content volunteer-detail-content">
        <a href="{{ url('/voluntariados') }}" class="volunteer-detail-back">← Volver a voluntariados</a>

        @if (count($voluntariado->imagenes ?? []))
            <section class="volunteer-gallery" data-volunteer-gallery aria-label="Galería del voluntariado">
                <div class="volunteer-gallery-main">
                    <button type="button" class="volunteer-gallery-image" data-gallery-open aria-label="Abrir imagen en grande">
                        <img src="{{ $voluntariado->imagenes[0] }}" alt="Imagen 1 de {{ $voluntariado->titulo }}" data-gallery-main>
                        <span class="volunteer-gallery-zoom" aria-hidden="true">⤢ Ver en grande</span>
                    </button>
                    @if (count($voluntariado->imagenes) > 1)
                        <button type="button" class="volunteer-gallery-arrow is-previous" data-gallery-step="-1" aria-label="Imagen anterior">‹</button>
                        <button type="button" class="volunteer-gallery-arrow is-next" data-gallery-step="1" aria-label="Imagen siguiente">›</button>
                        <span class="volunteer-gallery-count" data-gallery-count>1 / {{ count($voluntariado->imagenes) }}</span>
                    @endif
                </div>
                @if (count($voluntariado->imagenes) > 1)
                    <div class="volunteer-gallery-thumbnails" role="group" aria-label="Seleccionar imagen">
                        @foreach ($voluntariado->imagenes as $index => $image)
                            <button type="button" class="volunteer-gallery-thumb {{ $index === 0 ? 'is-active' : '' }}"
                                data-gallery-index="{{ $index }}"
                                aria-label="Mostrar imagen {{ $index + 1 }}"
                                aria-pressed="{{ $index === 0 ? 'true' : 'false' }}">
                                <img src="{{ $image }}" alt="" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        <div class="volunteer-detail-grid">
            <article class="volunteer-detail-main">
                <header class="volunteer-detail-section-heading">
                    <span>UNA ACCIÓN QUE SUMA</span>
                    <h2>Detalles de la actividad</h2>
                </header>
                <section class="volunteer-detail-description">
                    <h3>Sobre esta actividad</h3>
                    <p>{{ $voluntariado->descripcion }}</p>
                </section>

                @if (is_array($voluntariado->requisitos) && count($voluntariado->requisitos))
                    <section class="volunteer-detail-list-section">
                        <h3><span aria-hidden="true">✓</span> Requisitos para participar</h3>
                        <ul class="volunteer-detail-chips">
                            @foreach ($voluntariado->requisitos as $requisito)
                                <li><span aria-hidden="true">✓</span>{{ $requisito }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if (is_array($voluntariado->materiales) && count($voluntariado->materiales))
                    <section class="volunteer-detail-list-section">
                        <h3><span aria-hidden="true">🎒</span> Qué llevar</h3>
                        <ul class="volunteer-detail-chips volunteer-detail-chips--materials">
                            @foreach ($voluntariado->materiales as $material)
                                <li><span aria-hidden="true">✦</span>{{ $material }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </article>

            <aside class="volunteer-detail-aside">
                <section class="volunteer-detail-card">
                    <span class="volunteer-detail-card-kicker">ÚNETE A LA ACCIÓN</span>
                    <h2>Tu participación importa.</h2>
                    <div class="volunteer-detail-facts">
                        @if ($voluntariado->fechaInicio)
                            <div class="volunteer-detail-fact">
                                <span class="volunteer-detail-fact-icon" aria-hidden="true">📅</span>
                                <span><small>Fecha</small><strong>{{ $voluntariado->fechaInicio->format('d/m/Y') }}@if ($voluntariado->fechaFin && $voluntariado->fechaFin->format('Y-m-d') !== $voluntariado->fechaInicio->format('Y-m-d')) – {{ $voluntariado->fechaFin->format('d/m/Y') }}@endif</strong></span>
                            </div>
                        @endif
                        @if ($voluntariado->horaInicio)
                            <div class="volunteer-detail-fact">
                                <span class="volunteer-detail-fact-icon" aria-hidden="true">🕒</span>
                                <span>
                                    <small>Horario de la actividad</small>
                                    <strong>Inicio · {{ $voluntariado->horaInicio }}</strong>
                                    @if ($voluntariado->horaFin)
                                        <strong>Fin · {{ $voluntariado->horaFin }}</strong>
                                    @endif
                                </span>
                            </div>
                        @endif
                        @if ($lugar)
                            <div class="volunteer-detail-fact">
                                <span class="volunteer-detail-fact-icon" aria-hidden="true">📍</span>
                                <span><small>Lugar</small><strong>{{ $lugar->nombre }}</strong>@if ($lugar->municipio)<small>{{ $lugar->municipio }}</small>@endif</span>
                            </div>
                        @endif
                        @if ($voluntariado->cupo)
                            <div class="volunteer-detail-fact">
                                <span class="volunteer-detail-fact-icon" aria-hidden="true">👥</span>
                                <span><small>Cupo</small><strong>{{ $voluntariado->cupo }} personas</strong></span>
                            </div>
                        @endif
                        @if ($voluntariado->estado)
                            <span class="volunteer-detail-status volunteer-detail-status--{{ $voluntariado->estado }}">
                                <span aria-hidden="true">●</span>{{ ucfirst($voluntariado->estado) }}
                            </span>
                        @endif
                    </div>

                    <a href="{{ route('login', ['redirect' => request()->getPathInfo()]) }}"
                        class="volunteer-cta"
                        data-voluntariado-id="{{ (string) $voluntariado->_id }}">
                        <span aria-hidden="true">🙌</span> Quiero participar
                        <span class="volunteer-cta-arrow" aria-hidden="true">→</span>
                    </a>
                    <p class="volunteer-cta-note">Súmate y forma parte del cambio en Baja California Sur.</p>
                    <p id="enrollmentMessage" class="volunteer-enrollment-message" role="status" aria-live="polite"></p>
                </section>

                @php
                    $voluntariadoEndDate = $voluntariado->fechaFin ?? $voluntariado->fechaInicio;
                    $voluntariadoAlreadyEnded = $voluntariadoEndDate
                        && $voluntariadoEndDate->copy()->startOfDay()->lt(now()->startOfDay());
                @endphp
                @if ($voluntariadoAlreadyEnded)
                    <div class="volunteer-ended-notice" role="status">
                        <span class="volunteer-ended-notice-icon" aria-hidden="true">✓</span>
                        <span>
                            <strong>Este voluntariado ya finalizó</strong>
                            <small>La fecha de esta actividad ya pasó.</small>
                        </span>
                    </div>
                @endif

                <section class="volunteer-calendar-card"
                    data-event-date="{{ $voluntariado->fechaInicio ? $voluntariado->fechaInicio->format('Y-m-d') : '' }}"
                    data-event-end="{{ $voluntariado->fechaFin ? $voluntariado->fechaFin->format('Y-m-d') : ($voluntariado->fechaInicio ? $voluntariado->fechaInicio->format('Y-m-d') : '') }}">
                    <h3>Duración del voluntariado</h3>
                    <p class="volunteer-calendar-range">
                        @if ($voluntariado->fechaInicio)
                            {{ $voluntariado->fechaInicio->format('d/m/Y') }}
                            @if ($voluntariado->fechaFin && $voluntariado->fechaFin->format('Y-m-d') !== $voluntariado->fechaInicio->format('Y-m-d'))
                                al {{ $voluntariado->fechaFin->format('d/m/Y') }}
                            @endif
                        @else
                            Fecha por confirmar
                        @endif
                    </p>
                    <div class="calendar-header">
                        <button type="button" id="calendarPrev" aria-label="Mes anterior">←</button>
                        <h4 id="calendarMonth"></h4>
                        <button type="button" id="calendarNext" aria-label="Mes siguiente">→</button>
                    </div>
                    <div class="calendar-weekdays">
                        <span>Lun</span><span>Ma</span><span>Mi</span><span>Ju</span><span>Vi</span><span>Sá</span><span>Do</span>
                    </div>
                    <div class="calendar-days" id="calendarDays"></div>
                    <p class="calendar-event-date"><span class="calendar-event-dot"></span>Días de actividad:<strong id="calendarSelectedDate"></strong></p>
                </section>
                <div class="calendar-legend">
                    <div class="calendar-legend-item"><span class="calendar-legend-event">15</span><span>Días del voluntariado</span></div>
                    <div class="calendar-legend-item"><span class="calendar-legend-today">15</span><span>Día actual</span></div>
                </div>
            </aside>
        </div>

        <section class="volunteer-map-section">
            <header class="volunteer-detail-section-heading">
                <span>ENCUENTRA EL PUNTO</span>
                <h2>Ubicación de la actividad</h2>
                @if ($lugar)
                    <p>{{ $lugar->nombre }}@if ($lugar->municipio) · {{ $lugar->municipio }}@endif</p>
                @endif
            </header>
            @if ($ubicacion)
                <div id="volunteerDetailMap" class="volunteer-detail-map"
                    data-latitude="{{ $ubicacion['latitud'] }}"
                    data-longitude="{{ $ubicacion['longitud'] }}"
                    data-location-name="{{ $lugar?->nombre ?? $voluntariado->titulo }}"
                    data-location-address="{{ $lugar?->direccion ?? '' }}"
                    role="application" aria-label="Mapa de ubicación del voluntariado"></div>
            @else
                <p class="volunteer-map-unavailable">Este voluntariado aún no tiene una ubicación válida para mostrar en el mapa.</p>
            @endif
        </section>

        <div class="detail-back">
            <a href="{{ url('/voluntariados') }}" class="section-link">← Volver a voluntariados</a>
        </div>
    </section>
</main>

<dialog class="volunteer-lightbox" data-gallery-lightbox aria-label="Visor de imágenes">
    <button type="button" class="volunteer-lightbox-close" data-gallery-close aria-label="Cerrar imagen">×</button>
    <button type="button" class="volunteer-lightbox-arrow is-previous" data-lightbox-step="-1" aria-label="Imagen anterior">‹</button>
    <figure>
        <img src="{{ $voluntariado->imagenes[0] ?? '' }}" alt="Imagen del voluntariado" data-lightbox-image>
        <figcaption data-lightbox-caption>1 / {{ count($voluntariado->imagenes ?? []) }}</figcaption>
    </figure>
    <button type="button" class="volunteer-lightbox-arrow is-next" data-lightbox-step="1" aria-label="Imagen siguiente">›</button>
</dialog>

<footer class="page-footer">
    <div class="footer-logo">Regenera<span>BCS</span></div>
    <p>Participación ambiental · Baja California Sur</p>
</footer>
@endsection

@push('scripts')
    @vite('resources/js/voluntariado-detail.js')
    <script src="{{ asset('js/voluntariado-calendar.js') }}"></script>
@endpush
