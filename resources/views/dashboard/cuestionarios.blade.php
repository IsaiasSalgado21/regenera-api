@extends('layouts.app')

@section('title', 'Gestionar cuestionarios · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
@endpush

@section('content')
<main class="dashboard-shell quiz-admin-page" data-quiz-admin>
    <header class="dashboard-heading">
        <div>
            <span class="dashboard-eyebrow">PANEL DE ADMINISTRACIÓN / APRENDIZAJE</span>
            <h1>Gestionar cuestionarios</h1>
            <p>Las ediciones crean una versión nueva y conservan los intentos anteriores.</p>
        </div>
        <a class="button button-secondary" href="{{ route('dashboard.admin') }}">Volver al panel</a>
    </header>

    <div class="dashboard-access-error" data-quiz-admin-access hidden role="alert"></div>
    <div class="dashboard-notice" data-quiz-admin-notice hidden role="status" aria-live="polite"></div>
    <div class="dashboard-error" data-quiz-admin-error hidden role="alert"></div>

    <section class="dashboard-section-heading quiz-admin-list-heading">
        <div><span class="dashboard-eyebrow">CATÁLOGO VERSIONADO</span><h2>Cuestionarios</h2></div>
        <button class="button button-primary" type="button" data-quiz-create>Crear cuestionario</button>
    </section>
    <div class="dashboard-loading" data-quiz-admin-loading hidden>Cargando cuestionarios…</div>
    <div class="dashboard-card-grid" data-quiz-admin-list></div>
    <p class="dashboard-empty" data-quiz-admin-empty hidden>No hay cuestionarios registrados.</p>

    <form class="dashboard-form quiz-admin-form" data-quiz-admin-form hidden>
        <div class="dashboard-form-heading">
            <div><span class="dashboard-eyebrow">EDITOR DE CONTENIDO</span><h2 data-quiz-form-heading>Nuevo cuestionario</h2></div>
            <button class="icon-button" type="button" data-quiz-cancel aria-label="Cerrar formulario">×</button>
        </div>
        <input type="hidden" name="slugOriginal">
        <label>Identificador
            <input name="slug" required maxlength="80" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" placeholder="ej. seguridad-limpieza">
        </label>
        <label>Título<input name="titulo" required maxlength="150"></label>
        <label>Descripción<textarea name="descripcion" rows="2" required maxlength="500"></textarea></label>
        <label>Guía de estudio<textarea name="guia" rows="4" required maxlength="2000"></textarea></label>
        <fieldset class="quiz-admin-audience">
            <legend>Audiencia</legend>
            <label><input type="checkbox" name="audiencia" value="usuario"> Usuarios</label>
            <label><input type="checkbox" name="audiencia" value="organizador"> Organizadores</label>
            <label><input type="checkbox" name="audiencia" value="admin"> Administradores</label>
        </fieldset>
        <label class="dashboard-checkbox-label"><input type="checkbox" name="requiereOrganizadorVerificado"> Requiere organizador verificado</label>
        <div class="dashboard-form-row">
            <label>Porcentaje mínimo de aprobación<input name="minimoAprobacion" type="number" min="0" max="100" step="0.01" required></label>
            <label>Puntos por aprobar<input name="puntosAprobacion" type="number" min="0" max="10000" step="1" required></label>
        </div>
        <fieldset class="quiz-admin-medal">
            <legend>Medalla</legend>
            <div class="dashboard-form-row">
                <label>Identificador<input name="medallaSlug" required maxlength="80" pattern="[a-z0-9]+(?:-[a-z0-9]+)*"></label>
                <label>Nombre<input name="medallaNombre" required maxlength="100"></label>
            </div>
            <label>Descripción<input name="medallaDescripcion" required maxlength="300"></label>
            <label>Icono disponible<input name="medallaIcono" required maxlength="40" pattern="[a-z0-9-]+" placeholder="shield-check"></label>
        </fieldset>
        <div class="quiz-admin-questions-heading">
            <div><span class="dashboard-eyebrow">EVALUACIÓN</span><h3>Preguntas</h3></div>
            <button class="button button-secondary" type="button" data-quiz-add-question>Añadir pregunta</button>
        </div>
        <div class="quiz-admin-questions" data-quiz-questions></div>
        <div class="dashboard-form-actions">
            <button class="button button-primary" type="submit">Guardar versión</button>
            <button class="button button-secondary" type="button" data-quiz-cancel>Cancelar</button>
        </div>
    </form>
</main>
@endsection

@push('scripts')
    @vite('resources/js/dashboard/cuestionarios-admin.js')
@endpush