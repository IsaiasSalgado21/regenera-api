@extends('layouts.app')

@section('title', 'Voluntariados · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contenido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/voluntariados.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages-detail.css') }}">
@endpush

@section('content')
<main class="volunteers-board" data-volunteers-board>
    <header class="volunteers-board-header">
        <div>
            <span class="volunteers-board-eyebrow">REGIÓN · BAJA CALIFORNIA SUR</span>
            <h1>Voluntariados</h1>
            <p>Encuentra una actividad, organiza la tuya o revisa tus próximas participaciones.</p>
        </div>
        <a href="{{ route('voluntariados.create') }}" class="button button-primary volunteers-create-button">
            Proponer voluntariado <span aria-hidden="true">↗</span>
        </a>
    </header>

    @if (request()->query('propuesta') === 'enviada')
        <p class="form-message is-success" role="status">Tu propuesta fue enviada a revisión. Podrás consultar aquí su estado.</p>
    @endif

    <section class="volunteer-filter-bar" aria-label="Buscar y filtrar voluntariados">
        <label class="volunteer-search-field">
            <span>Buscar actividad</span>
            <input type="search" data-volunteer-search placeholder="Nombre, lugar, categoría u organizador" autocomplete="off">
        </label>
        <label>
            <span>Categoría</span>
            <select data-volunteer-category>
                <option value="">Todas</option>
                @foreach ($voluntariados->pluck('categoria')->filter()->unique()->sort() as $categoria)
                    <option value="{{ $categoria }}">{{ ucfirst(str_replace('-', ' ', $categoria)) }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span>Lugar</span>
            <select data-volunteer-place>
                <option value="">Todos</option>
                @foreach ($lugares as $lugar)
                    <option value="{{ (string) $lugar->_id }}">{{ $lugar->nombre }}</option>
                @endforeach
            </select>
        </label>
        <button class="volunteer-filter-reset" type="button" data-volunteer-reset disabled>Limpiar filtros</button>
        <p class="volunteer-filter-status" data-volunteer-filter-status role="status" aria-live="polite"></p>
    </section>

    <div class="volunteers-columns" data-personal-volunteer-sections>
        <section class="volunteer-board-column" data-volunteer-column="owned" data-owned-volunteers hidden aria-labelledby="ownedVolunteersHeading">
            <header class="volunteer-column-heading">
                <div><span>MI ORGANIZACIÓN</span><h2 id="ownedVolunteersHeading">Mis voluntariados</h2></div>
                <strong data-column-count>0</strong>
            </header>
            <div class="volunteer-grid" data-owned-volunteer-list></div>
            <div class="volunteer-empty-state" data-column-empty hidden>Aún no has creado voluntariados.</div>
            <nav class="volunteer-pagination" data-volunteer-pagination aria-label="Páginas de mis voluntariados">
                <button type="button" data-page-step="-1" aria-label="Página anterior">←</button>
                <span data-page-label></span>
                <button type="button" data-page-step="1" aria-label="Página siguiente">→</button>
            </nav>
        </section>

        <section class="volunteer-board-column" data-volunteer-column="available" data-public-column aria-labelledby="availableVolunteersHeading">
            <header class="volunteer-column-heading">
                <div><span>ABIERTOS A LA COMUNIDAD</span><h2 id="availableVolunteersHeading">Disponibles</h2></div>
                <strong data-column-count>{{ $voluntariados->count() }}</strong>
            </header>
            <div class="volunteer-grid" data-public-volunteer-list>
                @foreach ($voluntariados as $voluntariado)
                    <article class="volunteer-card volunteer-board-card"
                        data-volunteer-item
                        data-public-volunteer="{{ $voluntariado['id'] }}"
                        data-sort-id="{{ $voluntariado['id'] }}"
                        data-start-at="{{ $voluntariado['inicio_iso'] }}"
                        data-end-at="{{ $voluntariado['fin_iso'] }}"
                        data-category="{{ $voluntariado['categoria'] }}"
                        data-place-id="{{ $voluntariado['lugar_id'] }}"
                        data-search="{{ $voluntariado['titulo'] }} {{ $voluntariado['descripcion'] }} {{ $voluntariado['organizadorNombre'] }} {{ $voluntariado['lugar'] }} {{ $voluntariado['categoria'] }}">
                        <a class="volunteer-card-link" href="{{ url('/voluntariados/' . $voluntariado['id']) }}" aria-label="Ver voluntariado: {{ $voluntariado['titulo'] }}">
                            <div class="volunteer-board-image">
                                @if ($voluntariado['imagen'])
                                    <img src="{{ $voluntariado['imagen'] }}" alt="Imagen de {{ $voluntariado['titulo'] }}" loading="lazy" decoding="async">
                                @else
                                    <span aria-hidden="true">✦</span>
                                @endif
                            </div>
                            <div class="volunteer-card-content">
                                <span class="volunteer-category">{{ ucfirst(str_replace('-', ' ', $voluntariado['categoria'])) }}</span>
                                <h3>{{ $voluntariado['titulo'] }}</h3>
                                <p class="volunteer-author">Organiza: {{ $voluntariado['organizadorNombre'] ?: 'Organizador' }}</p>
                                <div class="volunteer-meta">
                                    <span>{{ $voluntariado['fecha'] }} · {{ $voluntariado['hora_inicio'] }}</span>
                                    <span>{{ $voluntariado['lugar'] ?: 'Lugar por confirmar' }}</span>
                                    <span>{{ $voluntariado['cupo'] }} lugares</span>
                                </div>
                                <p>{{ $voluntariado['descripcion'] }}</p>
                                <span class="personal-volunteer-timer" data-volunteer-timer data-start-at="{{ $voluntariado['inicio_iso'] }}" data-end-at="{{ $voluntariado['fin_iso'] }}"></span>
                                <span class="volunteer-link">Ver detalle <span aria-hidden="true">→</span></span>
                            </div>
                        </a>
                        <a class="volunteer-map-edge-link"
                            href="{{ url('/mapa?voluntariado=' . urlencode($voluntariado['id'])) }}"
                            aria-label="Ver ubicación en el mapa: {{ $voluntariado['titulo'] }}"
                            title="Ver ubicación en el mapa">
                            <img class="ui-symbol" src="{{ asset('img/simbolos/ubicacion.png') }}" alt="" aria-hidden="true">
                        </a>
                    </article>
                @endforeach
            </div>
            <div class="volunteer-empty-state" data-column-empty @if ($voluntariados->count()) hidden @endif>No hay actividades próximas con esos filtros.</div>
            <nav class="volunteer-pagination" data-volunteer-pagination aria-label="Páginas de voluntariados disponibles">
                <button type="button" data-page-step="-1" aria-label="Página anterior">←</button>
                <span data-page-label></span>
                <button type="button" data-page-step="1" aria-label="Página siguiente">→</button>
            </nav>
        </section>

        <section class="volunteer-board-column" data-volunteer-column="enrolled" data-enrolled-volunteers hidden aria-labelledby="enrolledVolunteersHeading">
            <header class="volunteer-column-heading">
                <div><span>MI PARTICIPACIÓN</span><h2 id="enrolledVolunteersHeading">Mis inscripciones</h2></div>
                <strong data-column-count>0</strong>
            </header>
            <div class="volunteer-grid" data-enrolled-volunteer-list></div>
            <div class="volunteer-empty-state" data-column-empty hidden>Aún no tienes inscripciones activas.</div>
            <nav class="volunteer-pagination" data-volunteer-pagination aria-label="Páginas de mis inscripciones">
                <button type="button" data-page-step="-1" aria-label="Página anterior">←</button>
                <span data-page-label></span>
                <button type="button" data-page-step="1" aria-label="Página siguiente">→</button>
            </nav>
        </section>
    </div>
</main>

<footer class="page-footer">
    <div class="footer-logo">Regenera<span>BCS</span></div>
    <p>Participación ambiental · Baja California Sur</p>
</footer>

@push('scripts')
    @vite('resources/js/personal-volunteers.js')
@endpush

@endsection