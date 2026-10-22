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
<main class="page">

    {{-- HERO --}}
    <section class="page-hero">
        <div class="page-hero-background"
             style="background-image: url('https://images.unsplash.com/photo-1531058020387-3be344556be6?auto=format&fit=crop&w=1800&q=85');">
        </div>
        <div class="page-hero-overlay"></div>

        <div class="page-hero-content">
            <span class="page-label">PARTICIPA</span>
            <h1>Haz algo por tu entorno.</h1>
            <p>
                Encuentra voluntariados y actividades ambientales en Baja California Sur
                y forma parte de acciones que buscan generar un impacto positivo.
            </p>
        </div>
    </section>

    {{-- CONTENIDO --}}
    <section class="page-content">

        <div class="page-intro">
            <h2>Próximos voluntariados.</h2>
            <p>
                Participa en actividades de limpieza, restauración, conservación
                y educación ambiental.
            </p>
        </div>

        {{-- FILTROS (GET, server-side) --}}
        <form method="GET" action="{{ url('/voluntariados') }}" class="volunteer-filters-form">

            <div class="volunteer-filters">
                @php
                    $categorias = [
                        'todos'                => 'Todos',
                        'limpieza'             => 'Limpieza',
                        'restauracion'         => 'Restauración',
                        'conservacion'         => 'Conservación',
                        'reforestacion'        => 'Reforestación',
                        'monitoreo'            => 'Monitoreo',
                        'educacion-ambiental'  => 'Educación ambiental',
                    ];
                    $actual = $categoria ?: 'todos';
                @endphp

                @foreach ($categorias as $valor => $etiqueta)
                    <button type="submit"
                            name="categoria"
                            value="{{ $valor }}"
                            class="filter {{ $actual === $valor ? 'active' : '' }}">
                        {{ $etiqueta }}
                    </button>
                @endforeach
            </div>

            <div class="volunteer-filter-fields" aria-label="Filtros de voluntariados">
                <label>
                    Buscar
                    <input type="search" name="q" value="{{ $q }}" placeholder="Título, descripción o categoría">
                </label>

                <label>
                    Lugar
                    <select name="lugarId">
                        <option value="">Todos los lugares</option>
                        @foreach ($lugares as $lugar)
                            <option value="{{ (string) $lugar->_id }}"
                                {{ (string) $lugar->_id === (string) $lugarId ? 'selected' : '' }}>
                                {{ $lugar->nombre }}
                            </option>
                        @endforeach
                    </select>
                </label>

                {{-- Preserva la categoría al buscar por texto/lugar --}}
                @if ($categoria)
                    <input type="hidden" name="categoria" value="{{ $categoria }}">
                @endif

                <button type="submit" class="volunteer-filter-submit">Aplicar filtros</button>
            </div>
        </form>

        {{-- RESULTADOS --}}
        <div class="volunteer-grid">
            @forelse ($voluntariados as $voluntariado)
                <article class="volunteer-card">
                    <div class="volunteer-image" style="background-image:url('{{ $voluntariado['imagen'] }}')">
                        @if ($voluntariado['fecha'])
                            <span class="volunteer-date">{{ $voluntariado['fecha'] }}</span>
                        @endif
                    </div>

                    <div class="volunteer-card-content">
                        @if ($voluntariado['categoria'])
                            <span class="volunteer-category">{{ ucfirst(str_replace('-', ' ', $voluntariado['categoria'])) }}</span>
                        @endif

                        <h3>{{ $voluntariado['titulo'] }}</h3>

                        <div class="volunteer-meta">
                            @if ($voluntariado['fecha'])
                                <span>📅 {{ $voluntariado['fecha'] }}</span>
                            @endif
                            @if ($voluntariado['hora_inicio'])
                                <span>🕐 {{ $voluntariado['hora_inicio'] }}@if ($voluntariado['hora_fin'])–{{ $voluntariado['hora_fin'] }}@endif</span>
                            @endif
                            @if ($voluntariado['lugar'])
                                <span>📍 {{ $voluntariado['lugar'] }}</span>
                            @endif
                            @if ($voluntariado['cupo'])
                                <span>👥 Cupo: {{ $voluntariado['cupo'] }}</span>
                            @endif
                        </div>

                        <p>{{ $voluntariado['descripcion'] }}</p>

                        <a class="volunteer-link" href="{{ url('/voluntariados/' . $voluntariado['id']) }}">
                            Ver voluntariado <span>→</span>
                        </a>
                    </div>
                </article>
            @empty
                <p class="empty-state">
                    No se encontraron voluntariados con esos filtros.
                    <a href="{{ url('/voluntariados') }}">Limpiar filtros</a>
                </p>
            @endforelse}
        </div>

    </section>
</main>

<footer class="page-footer">
    <div class="footer-logo">Regenera<span>BCS</span></div>
    <p>Participación ambiental · Baja California Sur</p>
</footer>

@endsection