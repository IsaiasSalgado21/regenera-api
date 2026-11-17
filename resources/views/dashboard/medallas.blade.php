@extends('layouts.app')

@section('title', 'Gestionar medallas · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
@endpush

@section('content')
<main class="dashboard-shell quiz-admin-page" data-medal-admin>
    <header class="dashboard-heading">
        <div>
            <span class="dashboard-eyebrow">PANEL DE ADMINISTRACIÓN / APRENDIZAJE</span>
            <h1>Gestionar medallas</h1>
            <p>Las medallas vinculadas u obtenidas se desactivan sin borrar su historial.</p>
        </div>
        <a class="button button-secondary" href="{{ route('dashboard.quizzes') }}">Ir a cuestionarios</a>
    </header>
    <div class="dashboard-error" data-medal-error hidden role="alert"></div>
    <div class="dashboard-notice" data-medal-notice hidden role="status" aria-live="polite"></div>
    <div class="dashboard-section-heading">
        <h2>Catálogo de medallas</h2>
        <button class="button button-primary" type="button" data-medal-create hidden>Crear medalla</button>
    </div>
    <div class="dashboard-loading" data-medal-loading hidden>Cargando medallas…</div>
    <button class="button button-secondary" type="button" data-medal-retry hidden>Reintentar</button>
    <div class="dashboard-card-grid" data-medal-list></div>
    <p class="dashboard-empty" data-medal-empty hidden>No hay medallas registradas.</p>
    <form class="dashboard-form" data-medal-form hidden>
        <div class="dashboard-form-heading">
            <h2 data-medal-form-heading>Nueva medalla</h2>
            <button type="button" class="icon-button" data-medal-cancel aria-label="Cerrar formulario">×</button>
        </div>
        <label>Identificador<input name="slug" required maxlength="80" pattern="[a-zA-Z0-9_-]+" autocomplete="off"><small>Es permanente para conservar los logros.</small></label>
        <label>Nombre<input name="nombre" required maxlength="100"></label>
        <label>Descripción<textarea name="descripcion" required maxlength="300" rows="3"></textarea></label>
        <label>Icono<input name="icono" required maxlength="40" pattern="[a-zA-Z0-9_-]+"></label>
        <div class="quiz-medal-icons" data-medal-icons aria-label="Iconos disponibles"></div>
        <label class="dashboard-checkbox-label" data-medal-active-label hidden><input type="checkbox" name="activo"> Medalla activa</label>
        <p>La condición de obtención se configura en el cuestionario vinculado.</p>
        <div class="dashboard-form-actions">
            <button class="button button-primary" type="submit">Guardar medalla</button>
            <button class="button button-secondary" type="button" data-medal-cancel>Cancelar</button>
        </div>
    </form>
</main>
@endsection

@push('scripts')
    @vite('resources/js/dashboard/medallas-admin.js')
@endpush
