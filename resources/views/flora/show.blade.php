@extends('layouts.app')

@section('title', $especie->nombre . ' · Regenera BCS')

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
        @if (is_array($especie->imagenes) && count($especie->imagenes))
            <div class="page-hero-background" style="background-image: url('{{ $especie->imagenes[0] }}')"></div>
        @else
            <div class="page-hero-background"
                 style="background-image: url('https://images.unsplash.com/photo-1568430462989-44163eb1752f?auto=format&fit=crop&w=1800&q=85');">
            </div>
        @endif
        <div class="page-hero-overlay"></div>

        <div class="page-hero-content">
            <span class="page-label">{{ $especie->tipo === 'flora' ? 'FLORA' : 'FAUNA' }}</span>
            <h1>{{ $especie->nombre }}</h1>
            @if ($especie->nombreCientifico)
                <p><em>{{ $especie->nombreCientifico }}</em></p>
            @endif
        </div>
    </section>

    <section class="page-content">

        <div class="page-intro">
            <h2>Sobre esta especie.</h2>
            <p>{{ $especie->descripcion }}</p>
        </div>

        <div class="especie-datos">

            @if ($especie->habitat)
                <div class="especie-dato">
                    <span>Hábitat</span>
                    <p>{{ $especie->habitat }}</p>
                </div>
            @endif

            @if ($especie->estadoConservacion)
                <div class="especie-dato">
                    <span>Estado de conservación</span>
                    <p>{{ $especie->estadoConservacion }}</p>
                </div>
            @endif

            @if (is_array($especie->ubicacion) && count($especie->ubicacion))
                <div class="especie-dato">
                    <span>Ubicación</span>
                    <p>{{ implode(', ', array_filter($especie->ubicacion)) }}</p>
                </div>
            @endif

            @if ($especie->fuenteInformacion)
                <div class="especie-dato">
                    <span>Fuente</span>
                    <p>{{ $especie->fuenteInformacion }}</p>
                </div>
            @endif

        </div>

        {{-- GALERÍA --}}
        @if (is_array($especie->imagenes) && count($especie->imagenes) > 1)
            <div class="especie-galeria">
                @foreach ($especie->imagenes as $imagen)
                    <div class="especie-galeria-item" style="background-image: url('{{ $imagen }}')"></div>
                @endforeach
            </div>
        @endif

        <section class="feature-section">
            <h2>Cuando visitamos su entorno.</h2>
            <p>
                Observar esta especie puede realizarse procurando reducir nuestra huella,
                respetar los senderos establecidos y no perturbar su hábitat natural.
            </p>
        </section>

        <div class="detail-back">
            <a href="{{ url('/flora') }}" class="section-link">← Volver a flora y fauna</a>
        </div>

    </section>
</main>

<footer class="page-footer">
    <div class="footer-logo">Regenera<span>BCS</span></div>
    <p>Turismo responsable · Baja California Sur</p>
</footer>

@endsection