@extends('layouts.app')

@section('title', 'Flora y fauna · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contenido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages-detail.css') }}">
@endpush

@section('content')
<main class="page">

    {{-- HERO --}}
    <section class="page-hero">
        <div class="page-hero-background"
             style="background-image: url('https://images.unsplash.com/photo-1568430462989-44163eb1752f?auto=format&fit=crop&w=1800&q=85');">
        </div>
        <div class="page-hero-overlay"></div>

        <div class="page-hero-content">
            <span class="page-label">FLORA Y FAUNA</span>
            <h1>La vida que habita BCS.</h1>
            <p>
                Conoce las especies, ecosistemas y paisajes que forman parte de
                Baja California Sur y descubre algunas formas de visitarlos respetando su entorno.
            </p>
        </div>
    </section>

    {{-- CONTENIDO --}}
    <section class="page-content">

        <div class="page-intro">
            <h2>Conoce nuestro entorno.</h2>
            <p>
                Baja California Sur cuenta con una gran diversidad de ambientes terrestres,
                costeros y marinos. Conocerlos permite comprender mejor su importancia y las
                acciones necesarias para conservarlos.
            </p>
        </div>

        {{-- FILTROS (GET, server-side) --}}
        <form method="GET" action="{{ url('/flora') }}" class="flora-filter-row">
            <label for="floraFilter">Mostrar</label>
            <select id="floraFilter" name="tipo" onchange="this.form.submit()">
                <option value="">Flora y fauna</option>
                <option value="flora" {{ $tipo === 'flora' ? 'selected' : '' }}>Flora</option>
                <option value="fauna" {{ $tipo === 'fauna' ? 'selected' : '' }}>Fauna</option>
            </select>

            <label for="floraSearch">Buscar especie o hábitat</label>
            <input id="floraSearch"
                   type="search"
                   name="q"
                   value="{{ $q }}"
                   placeholder="Nombre común, científico o hábitat">

            <button type="submit" class="volunteer-filter-submit">Buscar</button>
        </form>

        {{-- LISTADO --}}
        <div class="page-grid">
            @forelse ($especies as $especie)
                <article class="info-card">
                    @if ($especie['imagen'])
                        <div class="info-card-image" style="background-image: url('{{ $especie['imagen'] }}')"></div>
                    @else
                        <div class="info-card-image info-card-image--placeholder"></div>
                    @endif

                    <div class="info-card-content">
                        <span class="info-card-category">
                            {{ $especie['tipo'] === 'flora' ? 'FLORA' : 'FAUNA' }}
                            @if ($especie['conservacion'])
                                · {{ strtoupper($especie['conservacion']) }}
                            @endif
                        </span>

                        <h3>{{ $especie['nombre'] }}</h3>

                        @if ($especie['cientifico'])
                            <p class="info-card-scientific"><em>{{ $especie['cientifico'] }}</em></p>
                        @endif

                        <p>{{ \Illuminate\Support\Str::limit($especie['descripcion'], 150) }}</p>

                        <a href="{{ url('/flora/' . $especie['id']) }}" class="info-card-link">
                            Conocer más <span>→</span>
                        </a>
                    </div>
                </article>
            @empty
                <p class="empty-state">
                    No se encontraron especies con esos filtros.
                    <a href="{{ url('/flora') }}">Limpiar filtros</a>
                </p>
            @endforelse}
        </div>

        {{-- SECCIÓN DESTACADA --}}
        <section class="feature-section">
            <h2>Visitar también implica cuidar.</h2>
            <p>
                Observar fauna silvestre, recorrer ecosistemas naturales y disfrutar de las
                costas puede realizarse procurando reducir nuestra huella y respetar los
                espacios donde viven otras especies.
            </p>
        </section>

    </section>
</main>

<footer class="page-footer">
    <div class="footer-logo">Regenera<span>BCS</span></div>
    <p>Turismo responsable · Baja California Sur</p>
</footer>

@endsection