@extends('layouts.app')

@section('title', $articulo->titulo . ' · Regenera BCS')

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
        @if ($articulo->imagen)
            <div class="page-hero-background" style="background-image: url('{{ $articulo->imagen }})"></div>
        @else
            <div class="page-hero-background"
                 style="background-image: url('https://images.unsplash.com/photo-1469474968028-56623f02e42e?auto=format&fit=crop&w=1800&q=85');">
            </div>
        @endif
        <div class="page-hero-overlay"></div>

        <div class="page-hero-content">
            <span class="page-label">{{ strtoupper($articulo->categoria ?? 'ARTÍCULO') }}</span>
            <h1>{{ $articulo->titulo }}</h1>
            @if ($articulo->fechaPublicacion)
                <p>Publicado el {{ $articulo->fechaPublicacion->format('d/m/Y') }}</p>
            @endif
        </div>
    </section>

    <section class="page-content">

        <article class="articulo-detail">
            <div class="articulo-body">
                {!! nl2br(e($articulo->contenido)) !!}
            </div>
        </article>

        {{-- OTROS ARTÍCULOS --}}
        @if ($otros->count())
            <div class="page-intro" style="margin-top:90px">
                <h2>Te puede interesar.</h2>
            </div>

            <div class="page-grid article-list-grid">
                @foreach ($otros as $otro)
                    <article class="article-page-card">
                        @if ($otro->imagen)
                            <div class="article-page-image" style="background-image: url('{{ $otro->imagen }})"></div>
                        @else
                            <div class="article-page-image article-page-image--placeholder"></div>
                        @endif

                        <div class="article-page-content">
                            <span>{{ strtoupper($otro->categoria ?? 'ARTÍCULO') }}</span>
                            <h2>{{ $otro->titulo }}</h2>
                            <p>{{ \Illuminate\Support\Str::limit(strip_tags($otro->contenido), 120) }}</p>
                            <a href="{{ url('/articulos/' . (string) $otro->_id) }}" class="article-link">
                                Leer artículo →
                            </a>
                        </div>
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