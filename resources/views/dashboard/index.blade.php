@extends('layouts.app')

@section('title', 'Panel de gestión · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
@endpush

@section('content')
<section class="dashboard-shell" id="dashboardRoot" data-dashboard-role="{{ $dashboardRole }}">
    <header class="dashboard-heading">
        <div>
            <span class="dashboard-eyebrow">REGENERA BCS / GESTIÓN</span>
            <h1 id="dashboardTitle">Panel de gestión</h1>
            <p id="dashboardWelcome">Verificando tu sesión…</p>
        </div>
        <div class="dashboard-notification-wrap">
            <button class="dashboard-notification-button" id="notificationToggle" type="button" aria-expanded="false">
                Notificaciones <span class="notification-badge" id="notificationBadge" hidden>0</span>
            </button>
            <div class="notification-popover" id="notificationPopover" hidden>
                <div class="notification-popover-heading">
                    <h2>Notificaciones</h2>
                    <button type="button" class="text-button" id="markAllRead">Marcar todas como leídas</button>
                </div>
                <div id="notificationList" class="notification-list" aria-live="polite"></div>
                <div class="dashboard-error" id="notificationError" hidden>
                    <p>No se pudieron cargar las notificaciones.</p>
                    <button type="button" class="button button-secondary" data-retry="notifications">Reintentar</button>
                </div>
            </div>
        </div>
    </header>

    <div class="dashboard-notice" id="dashboardNotice" role="status" aria-live="polite" hidden></div>
    <section class="dashboard-access-error" id="dashboardAccessError" hidden>
        <h2>No se pudo abrir el panel</h2>
        <p id="dashboardAccessMessage"></p>
        <a class="button button-primary" href="{{ route('login', ['redirect' => request()->getRequestUri()]) }}">Iniciar sesión</a>
    </section>

    <div id="dashboardContent" hidden>
        <nav class="dashboard-tabs" aria-label="Secciones del panel">
            <button type="button" class="dashboard-tab is-active" data-section="articles">Artículos</button>
            <button type="button" class="dashboard-tab" data-section="places">Lugares</button>
            <button type="button" class="dashboard-tab" data-section="flora">Flora y fauna</button>
        </nav>

        <section class="dashboard-section is-active" id="section-articles" data-resource="articles">
            <div class="dashboard-section-heading">
                <div>
                    <span class="dashboard-eyebrow" data-article-kicker>CONTENIDO</span>
                    <h2 data-article-heading>Artículos</h2>
                </div>
                <button type="button" class="button button-primary" data-new="article">Crear artículo</button>
            </div>
            <div class="dashboard-error" data-error="articles" hidden>
                <p>No se pudieron cargar los artículos.</p>
                <button type="button" class="button button-secondary" data-retry="articles">Reintentar</button>
            </div>
            <div class="dashboard-loading" data-loading="articles">Cargando artículos…</div>
            <div class="dashboard-card-grid" id="articleCards"></div>
            <p class="dashboard-empty" data-empty="articles" hidden>No hay artículos para mostrar.</p>
            <form class="dashboard-form" id="articleForm" hidden>
                <div class="dashboard-form-heading">
                    <div><span class="dashboard-eyebrow">EDITOR</span><h3 data-form-title="article">Nuevo artículo</h3></div>
                    <button class="icon-button" type="button" data-cancel="article" aria-label="Cerrar formulario">×</button>
                </div>
                <input type="hidden" name="id">
                <label>Título<input name="titulo" required maxlength="200"></label>
                <label>Categoría<input name="categoria" required maxlength="100"></label>
                <label>Imagen (URL)<input name="imagen" type="url" maxlength="500"></label>
                <label>Contenido<textarea name="contenido" rows="8" required></textarea></label>
                <div class="dashboard-form-actions">
                    <button class="button button-primary" type="submit">Guardar borrador</button>
                    <button class="button button-secondary" type="button" data-cancel="article">Cancelar</button>
                </div>
            </form>
        </section>

        <section class="dashboard-section" id="section-places" data-resource="places" hidden>
            <div class="dashboard-section-heading">
                <div>
                    <span class="dashboard-eyebrow">ESPACIOS DE CONSERVACIÓN</span>
                    <h2 data-place-heading>Lugares</h2>
                </div>
                <button type="button" class="button button-primary" data-new="place">Crear lugar</button>
            </div>
            <div class="dashboard-error" data-error="places" hidden>
                <p>No se pudieron cargar los lugares.</p>
                <button type="button" class="button button-secondary" data-retry="places">Reintentar</button>
            </div>
            <div class="dashboard-loading" data-loading="places">Cargando lugares…</div>
            <div class="dashboard-card-grid" id="placeCards"></div>
            <p class="dashboard-empty" data-empty="places" hidden>No hay lugares para mostrar.</p>
            <form class="dashboard-form" id="placeForm" hidden>
                <div class="dashboard-form-heading">
                    <div><span class="dashboard-eyebrow">EDITOR</span><h3 data-form-title="place">Nuevo lugar</h3></div>
                    <button class="icon-button" type="button" data-cancel="place" aria-label="Cerrar formulario">×</button>
                </div>
                <input type="hidden" name="id">
                <label>Nombre<input name="nombre" required maxlength="150"></label>
                <label>Municipio<input name="municipio" required maxlength="100"></label>
                <label>Tipo<select name="tipo" required><option value="playa">Playa</option><option value="parque">Parque</option><option value="reserva">Reserva</option><option value="zona_urbana">Zona urbana</option><option value="otro">Otro</option></select></label>
                <label>Dirección<input name="direccion" maxlength="255"></label>
                <div class="dashboard-form-row">
                    <label>Latitud<input name="latitud" type="number" step="any" min="-90" max="90"></label>
                    <label>Longitud<input name="longitud" type="number" step="any" min="-180" max="180"></label>
                </div>
                <label>Descripción<textarea name="descripcion" rows="4" maxlength="2000"></textarea></label>
                <div class="dashboard-form-actions">
                    <button class="button button-primary" type="submit">Guardar lugar</button>
                    <button class="button button-secondary" type="button" data-cancel="place">Cancelar</button>
                </div>
            </form>
        </section>

        <section class="dashboard-section" id="section-flora" data-resource="flora" hidden>
            <div class="dashboard-section-heading">
                <div>
                    <span class="dashboard-eyebrow">BIODIVERSIDAD</span>
                    <h2 data-flora-heading>Registros de flora y fauna</h2>
                </div>
                <button type="button" class="button button-primary" data-new="flora">Crear registro</button>
            </div>
            <div class="dashboard-error" data-error="flora" hidden>
                <p>No se pudieron cargar los registros de flora y fauna.</p>
                <button type="button" class="button button-secondary" data-retry="flora">Reintentar</button>
            </div>
            <div class="dashboard-loading" data-loading="flora">Cargando registros…</div>
            <div class="dashboard-card-grid" id="floraCards"></div>
            <p class="dashboard-empty" data-empty="flora" hidden>No hay registros para mostrar.</p>
            <form class="dashboard-form" id="floraForm" hidden>
                <div class="dashboard-form-heading">
                    <div><span class="dashboard-eyebrow">EDITOR</span><h3 data-form-title="flora">Nuevo registro</h3></div>
                    <button class="icon-button" type="button" data-cancel="flora" aria-label="Cerrar formulario">×</button>
                </div>
                <input type="hidden" name="id">
                <input type="hidden" name="zonaAproximada">
                <label>Nombre<input name="nombre" required maxlength="150"></label>
                <label>Nombre científico<input name="nombreCientifico" maxlength="150"></label>
                <label>Tipo<select name="tipo" required><option value="flora">Flora</option><option value="fauna">Fauna</option></select></label>
                <label>Estado de conservación<input name="estadoConservacion" maxlength="100"></label>
                <label>Hábitat<input name="habitat" maxlength="500"></label>
                <div class="dashboard-form-row">
                    <label>Latitud<input name="latitud" type="number" step="any" min="-90" max="90"></label>
                    <label>Longitud<input name="longitud" type="number" step="any" min="-180" max="180"></label>
                </div>
                <p class="dashboard-location-warning" id="approximateLocationNotice" hidden>
                    Esta especie requiere una ubicación aproximada. Al guardar otros cambios se conservará su ubicación original sin exponerla.
                </p>
                <label>Lugares relacionados (IDs separados por coma)<input name="lugaresRelacionados" placeholder="ID de lugar, ID de lugar"></label>
                <label>Imágenes (URLs separadas por coma)<input name="imagenes" placeholder="https://…"></label>
                <label>Fuente de información<input name="fuenteInformacion" maxlength="255"></label>
                <label>Descripción<textarea name="descripcion" rows="4" required maxlength="2000"></textarea></label>
                <div class="dashboard-form-actions">
                    <button class="button button-primary" type="submit">Guardar registro</button>
                    <button class="button button-secondary" type="button" data-cancel="flora">Cancelar</button>
                </div>
            </form>
        </section>
    </div>
</section>

<dialog class="dashboard-dialog" id="rejectDialog">
    <form id="rejectForm">
        <h2>Rechazar artículo</h2>
        <p>El motivo será enviado al autor en una notificación.</p>
        <label for="rejectReason">Motivo obligatorio</label>
        <textarea id="rejectReason" name="motivo" rows="5" minlength="5" maxlength="1000" required></textarea>
        <div class="dashboard-form-actions">
            <button class="button button-danger" type="submit">Rechazar y notificar</button>
            <button class="button button-secondary" type="button" id="cancelReject">Cancelar</button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
    @vite('resources/js/dashboard/dashboard.js')
@endpush
