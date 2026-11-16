@extends('layouts.app')

@section('title', 'Mi perfil · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/profile.css') }}">
    <link rel="stylesheet" href="{{ asset('css/avatar-editor.css') }}">
@endpush

@section('content')
<main class="profile-page" data-personal-profile data-notification-profile>
    <div class="profile-shell">
        <header class="profile-heading">
            <div>
                <span class="profile-eyebrow">REGENERA BCS / MI ESPACIO</span>
                <h1>Mi perfil</h1>
                <p>Tu actividad y participación en la comunidad.</p>
            </div>
            <a class="profile-notifications-link" href="{{ route('notifications.index') }}">Centro de notificaciones <span aria-hidden="true">→</span></a>
        </header>

        <div class="profile-access-message" data-profile-access hidden role="status" aria-live="polite"></div>
        <div class="profile-load-error" data-profile-error hidden role="alert"></div>

        <section class="profile-identity" data-profile-identity aria-live="polite">
            <div class="profile-avatar-column">
                <div class="profile-avatar" data-profile-avatar aria-hidden="true">R</div>
                <button class="profile-avatar-edit-button" type="button" data-avatar-open>Editar avatar</button>
            </div>
            <div class="profile-identity-main">
                <span class="profile-eyebrow">PERFIL PERSONAL</span>
                <h2 data-profile-name>Cargando perfil…</h2>
                <p data-profile-email></p>
                <div class="profile-badges">
                    <span class="profile-badge" data-profile-role></span>
                    <span class="profile-badge" data-profile-state></span>
                    <span class="profile-badge profile-badge-verification" data-profile-verification hidden></span>
                </div>
            </div>
            <p class="profile-joined"><span>Miembro desde</span><strong data-profile-joined>—</strong><a data-profile-public-link hidden>Ver perfil público <span aria-hidden="true">↗</span></a></p>
        </section>

        @include('profile.avatar-editor')

        <section class="profile-gamification" data-gamification-profile hidden aria-labelledby="gamificationHeading">
            <div class="profile-section-heading">
                <div><span class="profile-eyebrow">TUS LOGROS</span><h2 id="gamificationHeading">Medallas</h2><p>Un reconocimiento a tu aprendizaje y compromiso.</p></div>
            </div>
            <p class="profile-inline-error" data-gamification-error hidden role="alert"></p>
            <section class="profile-panel profile-medals-panel" data-medals-content hidden>
                <div class="profile-panel-heading"><h3>Obtenidas recientemente</h3></div>
                <div class="profile-medal-grid" data-recent-medals></div>
                <details class="profile-all-medals">
                    <summary>Ver todas las medallas obtenidas</summary>
                    <h3>Medallas obtenidas</h3>
                    <div class="profile-medal-grid" data-earned-medals></div>
                </details>
                <div class="profile-pending-medals">
                    <h3>Por conseguir</h3>
                    <div class="profile-medal-grid" data-locked-medals></div>
                </div>
            </section>
            <section class="profile-progress" data-progress-content hidden aria-labelledby="progressHeading">
                <div><span class="profile-eyebrow">APRENDIZAJE</span><h2 id="progressHeading">Mi progreso</h2><p data-progress-summary></p><p data-organizer-certification hidden></p></div>
                <a href="{{ route('learning.index') }}">Continuar aprendizaje <span aria-hidden="true">→</span></a>
            </section>
        </section>

        <section class="profile-role-section" data-participation-section hidden>
            <div class="profile-section-heading">
                <div><span class="profile-eyebrow">MI PARTICIPACIÓN</span><h2>Historial de voluntariados</h2><p>Tu participación en las actividades de la comunidad.</p></div>
                <a href="{{ route('voluntariados.index') }}">Explorar voluntariados <span aria-hidden="true">→</span></a>
            </div>
            <section class="profile-panel">
                <div class="profile-panel-heading"><h3>Próximos y en curso</h3></div>
                <div class="profile-list" data-upcoming-list><p class="profile-muted">Cargando tus actividades…</p></div>
            </section>
            <section class="profile-panel profile-history-panel">
                <div class="profile-panel-heading"><h3>Participaciones</h3></div>
                <div class="profile-list" data-history-list><p class="profile-muted">Cargando tu historial…</p></div>
            </section>
        </section>

        <section class="profile-role-section" data-profile-section="usuario" hidden>
            <section class="profile-panel profile-articles-panel">
                <div class="profile-panel-heading"><h3>Mis artículos</h3><a href="{{ route('articulos.index') }}">Ver artículos <span aria-hidden="true">→</span></a></div>
                <div class="profile-article-list" data-profile-articles><p class="profile-muted">Cargando tus artículos…</p></div>
            </section>
        </section>

        <section class="profile-role-section" data-profile-section="organizador" hidden>
            <div class="profile-section-heading">
                <div><span class="profile-eyebrow">GESTIÓN COMUNITARIA</span><h2>Mis voluntariados</h2></div>
                <a href="{{ route('dashboard.organizador') }}">Abrir panel de organizador <span aria-hidden="true">→</span></a>
            </div>
            <div class="profile-stat-grid profile-stat-grid-four">
                <article class="profile-stat-card"><span>Voluntariados creados</span><strong data-organizer-total>—</strong></article>
                <article class="profile-stat-card"><span>Participantes inscritos</span><strong data-organizer-participants>—</strong></article>
                <article class="profile-stat-card"><span>Asistencias registradas</span><strong data-organizer-attendances>—</strong></article>
                <article class="profile-stat-card"><span>Publicados · Pendientes · Finalizados</span><strong class="profile-stat-triple"><span data-organizer-published>—</span><span data-organizer-pending>—</span><span data-organizer-finished>—</span></strong></article>
            </div>
            <section class="profile-panel">
                <div class="profile-panel-heading"><h3>Actividad que organizo</h3></div>
                <div class="profile-list" data-organizer-list><p class="profile-muted">Cargando tus voluntariados…</p></div>
            </section>
        </section>

        <section class="profile-role-section" data-profile-section="admin" hidden>
            <div class="profile-section-heading">
                <div><span class="profile-eyebrow">ADMINISTRACIÓN</span><h2>Cuenta administradora</h2></div>
                <a href="{{ route('dashboard.admin') }}">Abrir panel de administración <span aria-hidden="true">→</span></a>
            </div>
            <p class="profile-info-note">No hay estadísticas generales disponibles en un endpoint de perfil; para consultar la gestión, abre el panel administrativo.</p>
        </section>

        <section class="profile-notification-section">
            <div class="profile-section-heading">
                <div><span class="profile-eyebrow">CONFIGURACIÓN</span><h2>Preferencias y notificaciones</h2></div>
                <a href="{{ route('notifications.index') }}">Centro de notificaciones <span aria-hidden="true">→</span></a>
            </div>
            <div class="profile-notification-grid">
                <section class="profile-setting-card" data-reminder-settings>
                    <h3>Recordatorios de voluntariados</h3>
                    <p>Se enviarán para los voluntariados en los que tengas una inscripción activa.</p>
                    <form data-reminder-form>
                        <label for="reminderFrequency">Frecuencia</label>
                        <select id="reminderFrequency" name="recordatorioFrecuencia">
                            <option value="none">No recibir recordatorios</option>
                            <option value="milestones">Un día y una hora antes de iniciar</option>
                            <option value="daily">Una vez al día hasta que inicie</option>
                        </select>
                        <button type="submit">Guardar frecuencia</button>
                    </form>
                </section>
                <section class="profile-setting-card">
                    <h3>Notificaciones push del navegador</h3>
                    <p>Actívalas para recibir avisos aunque no tengas abierta la página. El navegador solicitará permiso solo cuando pulses el botón.</p>
                    <button type="button" class="profile-push-button" data-push-toggle>Activar notificaciones push</button>
                    <p class="profile-setting-status" data-profile-status role="status" aria-live="polite"></p>
                </section>
            </div>
        </section>
    </div>
</main>
@endsection
