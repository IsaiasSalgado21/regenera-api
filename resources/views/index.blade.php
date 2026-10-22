@extends('layouts.app')

@section('title', 'Regenera BCS · Turismo responsable en Baja California Sur')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contenido.css') }}">
@endpush

@section('content')
<main id="inicio">

    {{-- =========================
         INTRODUCCIÓN
    ========================== --}}
    <section class="intro">
        <div class="section-label">REGENERA BCS</div>

        <h2>
            Turismo que conoce,<br>
            disfruta y cuida.
        </h2>

        <p>
            Una plataforma para descubrir la riqueza natural de Baja California Sur,
            conocer sus especies, ecosistemas y paisajes, y encontrar nuevas formas de
            recorrerlos de manera responsable. Explora voluntariados, descubre lugares y
            conoce iniciativas que buscan conservar nuestro entorno y fortalecer a las
            comunidades locales. Porque disfrutar de Baja California Sur también significa
            aprender a cuidarlo y contribuir a que siga siendo un lugar vivo para las
            próximas generaciones.
        </p>

        <div class="home-actions">
            <a class="home-primary-link" href="{{ url('/voluntariados') }}">Explorar voluntariados <span>→</span></a>
            <a class="home-secondary-link" href="{{ url('/flora') }}">Descubrir flora y fauna</a>
        </div>
    </section>

    {{-- =========================
         VOLUNTARIADOS
    ========================== --}}
    <section class="volunteer-section" id="voluntariados">
        <div class="section-heading volunteer-heading">
            <div>
                <span>02 / PARTICIPA</span>
                <h2>Voluntariados</h2>
            </div>
            <div class="volunteer-heading-right">
                <p>
                    Encuentra iniciativas próximas y participa en acciones que contribuyen
                    al cuidado de Baja California Sur.
                </p>
                <a href="{{ url('/voluntariados') }}" class="section-link">
                    Ver todos los voluntariados <span>→</span>
                </a>
            </div>
        </div>

        <div class="volunteer-grid">
            @forelse ($voluntariados as $voluntariado)
                <article class="volunteer-card">
                    <div class="volunteer-image" style="background-image:url('{{ $voluntariado['imagen'] }}')">
                        @if ($voluntariado['fecha'])
                            <span class="volunteer-date">{{ $voluntariado['fecha'] }}</span>
                        @endif
                    </div>
                    <div class="volunteer-card-content">
                        <span class="volunteer-category">{{ ucfirst($voluntariado['categoria']) }}</span>
                        <h3>{{ $voluntariado['titulo'] }}</h3>
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
                        <p>{{ \Illuminate\Support\Str::limit($voluntariado['descripcion'], 110) }}</p>
                        <a class="volunteer-link" href="{{ url('/voluntariados/' . $voluntariado['id']) }}">Ver voluntariado <span>→</span></a>
                    </div>
                </article>
            @empty
                <p class="empty-state">Aún no hay voluntariados publicados.</p>
            @endforelse
        </div>
    </section>

    {{-- =========================
         HERO / FLORA Y FAUNA
    ========================== --}}
    <section class="hero" aria-label="Especies de Baja California Sur">
        <div class="hero-panel" id="floraPanel" data-base-url="{{ url('/') }}">
            @if (count($especies))
                <div class="panel-background" style="background-image:url('{{ $especies[0]['imagen'] }}')"></div>
            @else
                <div class="panel-background"></div>
            @endif
            <div class="panel-overlay"></div>

            <div class="hero-content">
                <p class="hero-small">BAJA CALIFORNIA SUR</p>
                <h2>Conoce. <span>Explora.</span> Regenera.</h2>
                <p class="hero-description">
                    Descubre la riqueza natural de Baja California Sur y conoce formas más
                    responsables de recorrer, disfrutar y proteger nuestro entorno.
                </p>
            </div>

            @if (count($especies))
                <div class="panel-content">
                    <span class="panel-category">{{ ucfirst($especies[0]['tipo']) }}</span>
                    <h3 id="floraTitle">{{ $especies[0]['nombre'] }}</h3>
                    <p id="floraDescription">{{ \Illuminate\Support\Str::limit($especies[0]['descripcion'], 130) }}</p>
                    <a href="{{ url('/flora/' . $especies[0]['id']) }}" class="panel-button" id="floraDetailLink">Explorar <span>→</span></a>
                </div>
                <div class="panel-controls">
                    <button id="floraPrev" aria-label="Especie anterior">←</button>
                    <div class="panel-counter">
                        <span id="floraCurrent">01</span> / <span id="floraTotal">{{ str_pad(count($especies), 2, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <button id="floraNext" aria-label="Siguiente especie">→</button>
                </div>
            @endif
        </div>
    </section>

    {{-- =========================
         ARTÍCULOS
    ========================== --}}
    <section class="articles-section" id="articulos">
        <div class="section-heading">
            <div>
                <span>03 / COMUNIDAD</span>
                <h2><a href="{{ url('/articulos') }}">Artículos</a></h2>
            </div>
            <p>
                Noticias, iniciativas y proyectos relacionados con el turismo responsable en BCS.
            </p>
        </div>

        <div class="articles-grid">
            @forelse ($articulos as $articulo)
                <article class="article-card">
                    <div class="article-image" style="background-image:url('{{ $articulo['imagen'] }}')"></div>
                    <div class="article-content">
                        <span>{{ ucfirst($articulo['categoria']) }}</span>
                        <h3>{{ $articulo['titulo'] }}</h3>
                        <p>{{ $articulo['contenido'] }}</p>
                        <a href="{{ url('/articulos/' . $articulo['id']) }}">Leer más</a>
                    </div>
                </article>
            @empty
                <p class="empty-state">Aún no hay artículos publicados.</p>
            @endforelse
        </div>
    </section>

    {{-- =========================
         REPORTE
    ========================== --}}
    <section class="report-section" id="reporte">
        <div>
            <span>PARTICIPA</span>
            <h2>¿Viste algo que debería conocerse?</h2>
            <p>
                Comparte un problema ambiental que hayas observado y ayuda a mantener
                informada la comunidad.
            </p>
        </div>
        <p class="report-unavailable">La recepción de reportes todavía no está disponible en esta versión.</p>
    </section>

</main>

{{-- =========================
     FOOTER
========================== --}}
<footer>
    <div class="footer-logo">Regenera<span>BCS</span></div>
    <p>Turismo responsable · Baja California Sur</p>
</footer>

@endsection

@push('scripts')
    <script type="application/json" id="floraData">@json($especies)</script>
    <script src="{{ asset('js/home.js') }}"></script>
@endpush