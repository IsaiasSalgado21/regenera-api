@extends('layouts.app')

@section('title', $voluntariado->titulo . ' · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contenido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/voluntariados.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages-detail.css') }}">
@endpush

@section('content')
<main class="page">

    {{-- HERO con la imagen del voluntariado --}}
    <section class="page-hero">
        @if ($voluntariado->imagen)
            <div class="page-hero-background" style="background-image: url('{{ $voluntariado->imagen }})"></div>
        @endif
        <div class="page-hero-overlay"></div>

        <div class="page-hero-content">
            <span class="page-label">PARTICIPA</span>
            <h1>{{ $voluntariado->titulo }}</h1>
            <p>{{ \Illuminate\Support\Str::limit($voluntariado->descripcion, 180) }}</p>
        </div>
    </section>

    <section class="page-content">

        <div class="page-intro">
            <h2>Detalles de la actividad.</h2>
        </div>

        <div class="volunteer-detail-grid">

            {{-- INFO PRINCIPAL --}}
            <div>
                <h3>Descripción</h3>
                <p>{{ $voluntariado->descripcion }}</p>

                @if (is_array($voluntariado->requisitos) && count($voluntariado->requisitos))
                    <h3>Requisitos</h3>
                    <ul class="detail-list">
                        @foreach ($voluntariado->requisitos as $requisito)
                            <li>{{ $requisito }}</li>
                        @endforeach
                    </ul>
                @endif

                @if (is_array($voluntariado->materiales) && count($voluntariado->materiales))
                    <h3>Materiales</h3>
                    <ul class="detail-list">
                        @foreach ($voluntariado->materiales as $material)
                            <li>{{ $material }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>

            {{-- PANEL LATERAL --}}
            <aside class="volunteer-detail-aside">
                <div class="volunteer-detail-card">
                    <h3>Datos de la actividad</h3>

                    <div class="volunteer-meta">
                        @if ($voluntariado->fechaInicio)
                            <span>📅 {{ $voluntariado->fechaInicio->format('d/m/Y') }}</span>
                        @endif
                        @if ($voluntariado->horaInicio)
                            <span>🕐 {{ $voluntariado->horaInicio }}@if ($voluntariado->horaFin)–{{ $voluntariado->horaFin }}@endif</span>
                        @endif
                        @if ($lugar)
                            <span>📍 {{ $lugar->nombre }}</span>
                            @if ($lugar->municipio)
                                <span>🏘️ {{ $lugar->municipio }}</span>
                            @endif
                        @endif
                        @if ($voluntariado->cupo)
                            <span>👥 Cupo: {{ $voluntariado->cupo }} personas</span>
                        @endif
                        @if ($voluntariado->estado)
                            <span class="volunteer-status volunteer-status--{{ $voluntariado->estado }}">
                                {{ ucfirst($voluntariado->estado) }}
                            </span>
                        @endif
                    </div>

                    <a href="{{ route('login', ['redirect' => request()->getPathInfo()]) }}"
                       class="volunteer-cta"
                       data-voluntariado-id="{{ (string) $voluntariado->_id }}">Quiero participar</a>
                    <p id="enrollmentMessage" role="status" aria-live="polite"></p>
                </div>
            </aside>

        </div>

        <div class="detail-back">
            <a href="{{ url('/voluntariados') }}" class="section-link">← Volver a voluntariados</a>
        </div>

    </section>
</main>

<footer class="page-footer">
    <div class="footer-logo">Regenera<span>BCS</span></div>
    <p>Participación ambiental · Baja California Sur</p>
</footer>

@endsection