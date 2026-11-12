@extends('layouts.app')

@section('title', 'Regenera BCS · Voluntariados y conservación ambiental')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contenido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
@endpush

@section('content')
<main id="inicio" class="home-page">

    <section class="volunteer-section" id="voluntariados">
        <div class="section-heading volunteer-heading">
            <div>
                <span>01 / PARTICIPA</span>
                <h2>Voluntariados</h2>
            </div>

            <div class="volunteer-heading-right">
                <a href="{{ url('/voluntariados') }}" class="section-link">
                    Explorar voluntariados <span>→</span>
                </a>
                <a href="{{ route('voluntariados.create') }}" class="button button-primary">
                    Quiero crear un voluntariado
                </a>
            </div>
        </div>

        <div class="personal-volunteer-sections" data-personal-volunteer-sections>
            <section class="personal-volunteer-group" data-enrolled-volunteers hidden>
                <div class="section-heading"><div><span>MI PARTICIPACIÓN</span><h2>Mis próximas inscripciones</h2></div></div>
                <div class="volunteer-grid" data-enrolled-volunteer-list></div>
            </section>
            <section class="personal-volunteer-group" data-owned-volunteers hidden>
                <div class="section-heading"><div><span>MI ACTIVIDAD</span><h2>Mis voluntariados</h2></div></div>
                <div class="volunteer-grid" data-owned-volunteer-list></div>
            </section>
        </div>

        <div class="volunteer-grid" data-public-volunteer-list>
            @forelse ($voluntariados as $voluntariado)
            <article class="volunteer-card volunteer-home-card" data-public-volunteer="{{ $voluntariado['id'] }}" data-start-at="{{ $voluntariado['inicioIso'] }}" data-end-at="{{ $voluntariado['finIso'] }}">
                    <a
                        class="volunteer-card-link"
                        href="{{ url('/voluntariados/' . $voluntariado['id']) }}"
                        aria-label="Conocer iniciativa: {{ $voluntariado['titulo'] }}"
                    >
                        <div
                            class="volunteer-image"
                            style="background-image: url('{{ $voluntariado['imagen'] }}')"
                        >
                            @if ($voluntariado['fecha'])
                                <span class="volunteer-date">
                                    {{ $voluntariado['fecha'] }}
                                </span>
                            @endif
                        </div>

                        <div class="volunteer-card-content">
                            <span class="volunteer-category">
                                {{ ucfirst($voluntariado['categoria']) }}
                            </span>

                            <h3>{{ $voluntariado['titulo'] }}</h3>
                            <p class="volunteer-author">Organiza: {{ $voluntariado['organizadorNombre'] ?: 'Organizador' }}</p>

                            <div class="volunteer-meta">
                                @if ($voluntariado['fecha'])
                                    <span>📅 {{ $voluntariado['fecha'] }}</span>
                                @endif

                                @if ($voluntariado['lugar'])
                                    <span>📍 {{ $voluntariado['lugar'] }}</span>
                                @endif

                                @if ($voluntariado['cupo'])
                                    <span>👥 Cupo: {{ $voluntariado['cupo'] }}</span>
                                @endif
                            </div>

                            <p>
                                {{ \Illuminate\Support\Str::limit($voluntariado['descripcion'], 110) }}
                            </p>
                            <span class="personal-volunteer-timer" data-volunteer-timer></span>

                            <div class="volunteer-card-hover" aria-hidden="true">
                                <span>Explorar voluntariado</span>
                                <span class="hover-arrow">↗</span>
                            </div>
                        </div>
                    </a>
                    <a class="volunteer-map-edge-link"
                        href="{{ url('/mapa?voluntariado=' . urlencode($voluntariado['id'])) }}"
                        aria-label="Ver ubicación en el mapa: {{ $voluntariado['titulo'] }}"
                        title="Ver ubicación en el mapa">
                        <span aria-hidden="true">⌖</span>
                    </a>
                </article>
            @empty
                <p class="empty-state">
                    Aún no hay voluntariados publicados. Pronto podrás encontrar
                    nuevas iniciativas para participar.
                </p>
            @endforelse
        </div>

    </section>
    

    <section class="hero" aria-label="Flora y fauna de Baja California Sur">
        <div class="hero-panel" id="floraPanel" data-base-url="{{ url('/') }}">

            @if (count($especies))
                <div
                    class="panel-background"
                    style="background-image: url('{{ $especies[0]['imagen'] }}')"
                ></div>
            @else
                <div class="panel-background"></div>
            @endif

            <div class="panel-overlay"></div>

            <div class="hero-content">
                <p class="hero-small">NUESTRO PATRIMONIO NATURAL</p>

                <h2>
                    Conoce. <span>Explora.</span> Regenera.
                </h2>

                <a href="{{ url('/flora') }}" class="panel-button">
                    Explorar flora y fauna <span>→</span>
                </a>
            </div>

            @if (count($especies))
                <div class="panel-content">
                    <span class="panel-category">
                        {{ ucfirst($especies[0]['tipo']) }}
                    </span>

                    <h3 id="floraTitle">
                        {{ $especies[0]['nombre'] }}
                    </h3>

                    <p id="floraDescription">
                        {{ \Illuminate\Support\Str::limit($especies[0]['descripcion'], 130) }}
                    </p>

                    <a
                        href="{{ url('/flora/' . $especies[0]['id']) }}"
                        class="panel-button"
                        id="floraDetailLink"
                    >
                        Conocer especie <span>→</span>
                    </a>
                </div>

                <div class="panel-controls">
                    <button id="floraPrev" aria-label="Especie anterior">
                        ←
                    </button>

                    <div class="panel-counter">
                        <span id="floraCurrent">01</span>
                        /
                        <span id="floraTotal">
                            {{ str_pad(count($especies), 2, '0', STR_PAD_LEFT) }}
                        </span>
                    </div>

                    <button id="floraNext" aria-label="Siguiente especie">
                        →
                    </button>
                </div>
            @endif
        </div>
    </section>

    <section class="impact-section" id="impacto">
        <div class="impact-intro">
            <span class="home-section-kicker">02 / IMPACTO CONSCIENTE</span>
            <h2>Participar empieza por saber dónde y cómo.</h2>
            <p>
                Cada iniciativa comparte la información disponible para que puedas
                conocer su fecha, lugar, cupo y descripción antes de sumarte.
                Sin promesas vacías: acciones claras, en comunidad.
            </p>
            <a class="impact-link" href="{{ url('/voluntariados') }}">
                Conoce las iniciativas <span aria-hidden="true">↗</span>
            </a>
        </div>
        <div class="impact-steps" aria-label="Cómo participar">
            <a href="{{ url('/voluntariados') }}" class="impact-step">
                <span>01</span>
                <strong>Encuentra una actividad</strong>
                <small>Explora las iniciativas ambientales publicadas.</small>
                <span class="impact-step-arrow" aria-hidden="true">↗</span>
            </a>
            <a href="{{ route('mapa') }}" class="impact-step">
                <span>02</span>
                <strong>Ubica el lugar</strong>
                <small>Consulta los espacios y puntos de interés del estado.</small>
                <span class="impact-step-arrow" aria-hidden="true">↗</span>
            </a>
            <a href="{{ url('/flora') }}" class="impact-step">
                <span>03</span>
                <strong>Conoce qué protegemos</strong>
                <small>Acércate a la flora, fauna y ecosistemas de BCS.</small>
                <span class="impact-step-arrow" aria-hidden="true">↗</span>
            </a>
        </div>
    </section>

    <section class="articles-section" id="articulos">
        <div class="section-heading">
            <div>
                <span>03 / IDEAS Y COMUNIDAD</span>
                <h2>
                    <a href="{{ url('/articulos') }}">Ideas que generan cambios</a>
                </h2>
            </div>

            <p>
                Conoce proyectos, experiencias e información que promueven
                la participación ciudadana y el cuidado del medio ambiente.
            </p>
        </div>

        <div class="articles-grid">
            @forelse ($articulos as $articulo)
                <article class="article-card">
                    <a href="{{ url('/articulos/' . $articulo['id']) }}" class="article-card-link" aria-label="Leer artículo: {{ $articulo['titulo'] }}">
                        <div
                            class="article-image"
                            style="background-image: url('{{ $articulo['imagen'] }}')"
                        ></div>

                        <div class="article-content">
                            <span>{{ ucfirst($articulo['categoria']) }}</span>

                            <h3>{{ $articulo['titulo'] }}</h3>

                            <p>
                                {{ \Illuminate\Support\Str::limit($articulo['contenido'], 160) }}
                            </p>

                            <span class="article-link-visual">Leer artículo <span>→</span></span>
                        </div>
                    </a>
                </article>
            @empty
                <p class="empty-state">
                    Aún no hay artículos publicados.
                </p>
            @endforelse
        </div>
    </section>

    <section class="home-closing">
        <div class="closing-orbit closing-orbit--one" aria-hidden="true"></div>
        <div class="closing-orbit closing-orbit--two" aria-hidden="true"></div>
        <span class="home-section-kicker">EL SIGUIENTE PASO ES TUYO</span>
        <h2>Conoce el lugar.<br><em>Súmate al cambio.</em></h2>
        <p>Explora Baja California Sur y encuentra una forma de participar.</p>
        <div class="home-actions">
            <a class="home-primary-link" href="{{ url('/voluntariados') }}">
                Ver voluntariados <span aria-hidden="true">↗</span>
            </a>
            <a class="closing-secondary-link" href="{{ route('mapa') }}">
                Explorar el mapa
            </a>
        </div>
    </section>

</main>

<footer class="home-footer">
    <div class="footer-logo">
        Regenera<span>BCS</span>
    </div>

    <p>Participación ambiental · Baja California Sur</p>
</footer>
@endsection

@push('scripts')
    <script type="application/json" id="floraData">@json($especies)</script>
    <script src="{{ asset('js/home.js') }}"></script>
    @vite('resources/js/personal-volunteers.js')
@endpush