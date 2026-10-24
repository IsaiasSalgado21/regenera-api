@extends('layouts.app')

@section('title', 'Regenera BCS · Voluntariados y conservación ambiental')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contenido.css') }}">
@endpush

@section('content')
<main id="inicio">

    <section class="intro">
        <div class="section-label">REGENERA BCS</div>

        <h2>
            Pequeñas acciones,<br>
            grandes cambios.
        </h2>

        <p>
            Una plataforma para conectar a las personas con iniciativas ambientales
            en Baja California Sur. Encuentra voluntariados, participa en jornadas
            de limpieza y conservación, y colabora con organizaciones y comunidades
            que buscan cuidar nuestro entorno. Cada acción cuenta cuando trabajamos
            juntos por un estado más limpio, consciente y comprometido con su riqueza
            natural.
        </p>

        <div class="home-actions">
            <a class="home-primary-link" href="{{ url('/voluntariados') }}">
                Encuentra un voluntariado <span>→</span>
            </a>

            <a class="home-secondary-link" href="{{ url('/flora') }}">
                Conoce nuestro entorno
            </a>
        </div>
    </section>

    <section class="volunteer-section" id="voluntariados">
        <div class="section-heading volunteer-heading">
            <div>
                <span>01 / PARTICIPA</span>
                <h2>Voluntariados</h2>
            </div>

            <div class="volunteer-heading-right">
                <p>
                    Pasa de la intención a la acción. Descubre jornadas e iniciativas
                    donde puedes aportar tu tiempo y ayudar a cuidar Baja California Sur.
                </p>

                <a href="{{ url('/voluntariados') }}" class="section-link">
                    Explorar voluntariados <span>→</span>
                </a>
            </div>
        </div>

        <div class="volunteer-grid">
            @forelse ($voluntariados as $voluntariado)
                <article class="volunteer-card">
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

                        <a
                            class="volunteer-link"
                            href="{{ url('/voluntariados/' . $voluntariado['id']) }}"
                        >
                            Conocer iniciativa <span>→</span>
                        </a>
                    </div>
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
                    Conoce. <span>Protege.</span> Regenera.
                </h2>

                <p class="hero-description">
                    Conocer la biodiversidad de nuestro estado es el primer paso
                    para comprender por qué vale la pena protegerla. Descubre las
                    especies y los ecosistemas que hacen único a Baja California Sur.
                </p>

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

    <section class="articles-section" id="articulos">
        <div class="section-heading">
            <div>
                <span>03 / COMUNIDAD</span>
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

                        <a href="{{ url('/articulos/' . $articulo['id']) }}">
                            Leer artículo <span>→</span>
                        </a>
                    </div>
                </article>
            @empty
                <p class="empty-state">
                    Aún no hay artículos publicados.
                </p>
            @endforelse
        </div>
    </section>


</main>

<footer>
    <div class="footer-logo">
        Regenera<span>BCS</span>
    </div>

    <p>Participación ambiental · Baja California Sur</p>
</footer>
@endsection

@push('scripts')
    <script type="application/json" id="floraData">@json($especies)</script>
    <script src="{{ asset('js/home.js') }}"></script>
@endpush