@extends('layouts.app')

@section('title', $articulo->titulo . ' · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contenido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages-detail.css') }}">
@endpush

@section('content')
<main class="article-reading">
    <div class="article-reading-shell">
        <a href="{{ url('/articulos') }}" class="article-reading-back">← Todos los artículos</a>
        <header class="article-reading-header">
            <span class="article-reading-category">{{ $articulo->categoria ?: 'Artículo' }}</span>
            <h1>{{ $articulo->titulo }}</h1>
            <div class="article-reading-byline">
                <span>Por {{ $autorNombre }}</span>
                @if ($articulo->fechaPublicacion)
                    <time datetime="{{ $articulo->fechaPublicacion->toIso8601String() }}">{{ $articulo->fechaPublicacion->timezone('America/Mazatlan')->format('d M Y') }}</time>
                @endif
                <span>{{ $minutosLectura }} min de lectura</span>
            </div>
        </header>

        @if ($articulo->imagen)
            <figure class="article-reading-cover">
                <img src="{{ $articulo->imagen }}" alt="Imagen de {{ $articulo->titulo }}" fetchpriority="high">
            </figure>
        @endif

        <article class="article-reading-content">
            <div class="article-reading-body">
                @foreach ($parrafos as $parrafo)
                    <p class="{{ $loop->first ? 'article-reading-lead' : '' }}">{!! nl2br(e($parrafo)) !!}</p>
                @endforeach
            </div>
            <aside class="article-reading-note">
                <span>LECTURA</span>
                <strong>{{ $articulo->categoria ?: 'Comunidad' }}</strong>
                <small>Una historia de Baja California Sur</small>
            </aside>
        </article>

        @if (count($articulo->imagenes ?? []) > 1)
            <section class="detail-image-gallery" aria-label="Imágenes del artículo">
                @foreach (array_slice($articulo->imagenes, 1) as $index => $image)
                    <img src="{{ $image }}" alt="Imagen {{ $index + 2 }} de {{ $articulo->titulo }}" loading="lazy">
                @endforeach
            </section>
        @endif

        {{-- OTROS ARTÍCULOS --}}
        @if ($otros->count())
            <div class="article-related-heading">
                <h2>Te puede interesar.</h2>
            </div>

            <div class="page-grid article-list-grid">
                @foreach ($otros as $otro)
                    <article class="article-page-card">
                        <a href="{{ url('/articulos/' . (string) $otro->_id) }}" class="article-page-card-link" aria-label="Leer artículo: {{ $otro->titulo }}">
                            @if ($otro->imagen)
                                <div class="article-page-image" style="background-image: url('{{ $otro->imagen }}')"></div>
                            @else
                                <div class="article-page-image article-page-image--placeholder"></div>
                            @endif

                            <div class="article-page-content">
                                <span>{{ strtoupper($otro->categoria ?? 'ARTÍCULO') }}</span>
                                <h2>{{ $otro->titulo }}</h2>
                                <p>{{ \Illuminate\Support\Str::limit(strip_tags($otro->contenido), 120) }}</p>
                                <span class="article-link-visual">Leer artículo <span>→</span></span>
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>
        @endif

        <div class="detail-back">
            <a href="{{ url('/articulos') }}" class="section-link">← Volver a artículos</a>
        </div>

    </section>
</main>

<footer class="page-footer">
    <div class="footer-logo">Regenera<span>BCS</span></div>
    <p>Turismo responsable · Baja California Sur</p>
</footer>

@endsection