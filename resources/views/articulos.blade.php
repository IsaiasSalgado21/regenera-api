@extends('layouts.app')

@section('title', 'Artículos · Regenera BCS')

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
             style="background-image: url('https://images.unsplash.com/photo-1469474968028-56623f02e42e?auto=format&fit=crop&w=1800&q=85');">
        </div>
        <div class="page-hero-overlay"></div>

        <div class="page-hero-content">
            <span class="page-label">03 / COMUNIDAD</span>
            <h1>Artículos</h1>
            <p>
                Noticias, iniciativas, proyectos y contenidos relacionados con el turismo
                responsable, la conservación y las comunidades de Baja California Sur.
            </p>
        </div>
    </section>

    {{-- CONTENIDO --}}
    <section class="page-content">

        <div class="page-intro">
            <h2>Información para conocer y participar.</h2>
            <p>
                Explora contenidos sobre naturaleza, turismo, conservación e iniciativas
                que buscan generar impactos positivos en Baja California Sur.
            </p>
        </div>

        {{-- BÚSQUEDA --}}
        <form method="GET" action="{{ url('/articulos') }}" class="flora-filter-row">
            <label for="articuloSearch">Buscar artículo</label>
            <input id="articuloSearch"
                   type="search"
                   name="q"
                   value="{{ $q }}"
                   placeholder="Título o contenido">
            <button type="submit" class="volunteer-filter-submit">Buscar</button>
        </form>

        {{-- LISTADO --}}
        <div class="page-grid article-list-grid">
            @forelse ($articulos as $articulo)
                <article class="article-page-card">
                    @if ($articulo['imagen'])
                        <div class="article-page-image" style="background-image: url('{{ $articulo['imagen'] }}')"></div>
                    @else
                        <div class="article-page-image article-page-image--placeholder"></div>
                    @endif

                    <div class="article-page-content">
                        <span>{{ strtoupper($articulo['categoria'] ?? 'ARTÍCULO') }}</span>

                        <h2>{{ $articulo['titulo'] }}</h2>

                        <p>{{ $articulo['resumen'] }}</p>

                        @if ($articulo['fecha'])
                            <span class="article-page-date">{{ $articulo['fecha'] }}</span>
                        @endif

                        <a href="{{ url('/articulos/' . $articulo['id']) }}" class="article-link">
                            Leer artículo →
                        </a>
                    </div>
                </article>
            @empty
                <p class="empty-state">
                    No se encontraron artículos.
                    <a href="{{ url('/articulos') }}">Limpiar búsqueda</a>
                </p>
            @endforelse}
        </div>

    </section>
</main>

<footer class="page-footer">
    <div class="footer-logo">Regenera<span>BCS</span></div>
    <p>Turismo responsable · Baja California Sur</p>
</footer>

@endsection