@extends('layouts.app')

@section('title', 'Perfil de la comunidad · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/profile.css') }}">
    <link rel="stylesheet" href="{{ asset('css/avatar-editor.css') }}">
@endpush

@section('content')
<main class="profile-page" data-public-profile data-user-id="{{ $id }}">
    <div class="profile-shell">
        <header class="profile-heading">
            <div>
                <span class="profile-eyebrow">REGENERA BCS / COMUNIDAD</span>
                <h1>Perfil de participante</h1>
                <p>Un recorrido de aprendizaje y participación ambiental.</p>
            </div>
            <a class="profile-notifications-link" href="{{ url('/voluntariados') }}">Explorar voluntariados <span aria-hidden="true">→</span></a>
        </header>
        <p class="profile-load-error" data-public-profile-status role="status" aria-live="polite">Cargando perfil…</p>
        <section class="profile-identity" data-public-profile-content hidden>
            <div class="profile-avatar" data-public-avatar aria-hidden="true"></div>
            <div class="profile-identity-main">
                <span class="profile-eyebrow">MIEMBRO DE LA COMUNIDAD</span>
                <h2 data-public-name></h2>
                <p>Participante de Regenera BCS</p>
            </div>
        </section>
        <section class="profile-panel public-profile-medals" data-public-profile-content hidden aria-labelledby="publicMedalsTitle">
            <div class="profile-panel-heading"><h3 id="publicMedalsTitle">Medallas obtenidas</h3></div>
            <div class="profile-medal-grid" data-public-medals></div>
        </section>
    </div>
</main>
@endsection