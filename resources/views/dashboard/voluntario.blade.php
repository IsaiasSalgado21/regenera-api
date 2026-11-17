@extends('layouts.app')

@section('title', 'Mi panel · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
@endpush

@section('content')
<section class="dashboard-shell" data-volunteer-panel>
    <header class="dashboard-heading">
        <div>
            <span class="dashboard-eyebrow">REGENERA BCS / MI PARTICIPACIÓN</span>
            <h1>Mi panel</h1>
            <p data-volunteer-welcome>Consultando tu sesión…</p>
        </div>
        <a class="button button-secondary" href="{{ route('profile.show') }}">Ver mi perfil</a>
    </header>
    <div class="dashboard-loading" data-volunteer-loading role="status">Cargando tus actividades…</div>
    <div class="dashboard-access-error" data-volunteer-error hidden role="alert">
        <p data-volunteer-error-message></p>
        <a class="button button-secondary" href="{{ route('login', ['redirect' => request()->getRequestUri()]) }}">Iniciar sesión</a>
        <button class="button button-secondary" type="button" data-volunteer-retry>Reintentar</button>
    </div>
    <div data-volunteer-content hidden>
        <nav class="dashboard-tabs" aria-label="Secciones de mi panel">
            <button type="button" class="dashboard-tab is-active" data-volunteer-tab="activities" aria-current="page">Mis actividades</button>
            <button type="button" class="dashboard-tab" data-volunteer-tab="proposals">Mis propuestas</button>
        </nav>
        <section class="dashboard-section" data-volunteer-section="activities">
            <div class="dashboard-section-heading">
                <div><span class="dashboard-eyebrow">TU PARTICIPACIÓN</span><h2>Próximas actividades</h2></div>
                <a class="button button-primary" href="{{ route('voluntariados.index') }}">Explorar voluntariados</a>
            </div>
            <p data-volunteer-summary></p>
            <div class="dashboard-card-grid" data-volunteer-activities></div>
            <p class="dashboard-empty" data-volunteer-activities-empty hidden>No tienes actividades próximas. Explora los voluntariados disponibles.</p>
            <a href="{{ route('profile.show') }}">Ver historial completo en mi perfil</a>
        </section>
        <section class="dashboard-section" data-volunteer-section="proposals" hidden>
            <div class="dashboard-section-heading">
                <div><span class="dashboard-eyebrow">TUS PROPUESTAS</span><h2>Mis voluntariados</h2></div>
                <a class="button button-primary" href="{{ route('voluntariados.create') }}">Proponer voluntariado</a>
            </div>
            <div class="dashboard-card-grid" data-volunteer-proposals></div>
            <p class="dashboard-empty" data-volunteer-proposals-empty hidden>Aún no has propuesto voluntariados.</p>
        </section>
    </div>
</section>
@endsection

@push('scripts')
    @vite('resources/js/dashboard/voluntario.js')
@endpush
