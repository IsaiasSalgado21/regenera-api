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
        <label>Identificador del cuestionario
            <input name="slug" list="quiz-identifiers" required maxlength="80" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" placeholder="ej. seguridad-limpieza" aria-describedby="quiz-identifier-help quiz-identifier-status" autocomplete="off">
        </label>
        <datalist id="quiz-identifiers" data-quiz-identifiers></datalist>
        <p class="quiz-admin-help" id="quiz-identifier-help">Selecciona un identificador existente o escribe uno nuevo con letras minúsculas, números y guiones.</p>
        <p class="quiz-admin-identifier-status" id="quiz-identifier-status" data-quiz-identifier-status hidden role="status"></p>
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
            <legend>Medalla del cuestionario</legend>
            <p class="quiz-admin-medal-intro">Reconoce a quienes aprueben este cuestionario. Completa los datos que verán en su perfil.</p>
            <div class="quiz-admin-medal-requirements">
                <strong>Cómo se obtiene</strong>
                <p>Se otorga al aprobar el cuestionario con el porcentaje mínimo configurado arriba.</p>
                <p data-quiz-medal-attendance hidden>Este cuestionario también requiere asistencia confirmada en una actividad finalizada con inscripción válida.</p>
                <p data-quiz-medal-verification hidden>También requiere una cuenta de organizador verificada.</p>
            </div>
            <div class="dashboard-form-row">
                <label>Identificador de la medalla<input name="medallaSlug" required maxlength="80" pattern="[a-z0-9]+(?:-[a-z0-9]+)*" placeholder="ej. voluntario-seguro"><small>Clave estable, sin espacios. Si se usa en otro cuestionario, ambos comparten la misma medalla.</small></label>
                <label>Nombre visible<input name="medallaNombre" required maxlength="100" placeholder="ej. Voluntario Seguro"><small>Así aparecerá en el perfil.</small></label>
            </div>
            <label>Descripción de la medalla<textarea name="medallaDescripcion" rows="2" required maxlength="300" placeholder="Describe qué reconoce este logro"></textarea></label>
            <label>Icono
                <input name="medallaIcono" list="quiz-medal-icons" required maxlength="40" pattern="[a-z0-9\-]+" placeholder="Selecciona o escribe un icono">
                <small>Elige un icono compatible; otros identificadores pueden mostrarse con el símbolo predeterminado.</small>
            </label>
            <datalist id="quiz-medal-icons">
                <option value="shield-check">Protección</option>
                <option value="mangrove">Manglar</option>
                <option value="recycle">Reciclaje</option>
                <option value="leader">Liderazgo</option>
            </datalist>
        </fieldset>
        <div class="quiz-admin-questions-heading">
            <div><span class="dashboard-eyebrow">EVALUACIÓN</span><h3>Preguntas</h3></div>
            <button class="button button-secondary" type="button" data-quiz-add-question>Añadir pregunta</button>
        </div>
        <div class="quiz-admin-questions" data-quiz-questions></div>
        <p class="dashboard-error quiz-admin-form-error" data-quiz-form-error hidden role="alert"></p>
        <div class="dashboard-form-actions">
            <button class="button button-primary" type="submit" data-quiz-submit>Crear cuestionario</button>
            <button class="button button-secondary" type="button" data-quiz-cancel>Cancelar</button>
        </div>
    </form>
</main>
@endsection

@push('scripts')
    @vite('resources/js/dashboard/cuestionarios-admin.js')
@endpush