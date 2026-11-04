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
            <button type="button" class="dashboard-tab" data-section="voluntariados">Voluntariados</button>
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
                <div class="dashboard-image-field" data-image-field data-max-images="10">
                    <label>Imágenes del artículo (hasta 10)<input name="imagenes[]" type="file" accept="image/*" multiple data-image-input></label>
                    <p class="dashboard-image-help">Puedes conservar las imágenes actuales, quitar alguna o agregar más.</p>
                    <div class="dashboard-image-previews" data-image-previews aria-live="polite"></div>
                    <div data-image-removals></div>
                </div>
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
                <div class="dashboard-place-map-section">
                    <div>
                        <h4>Selecciona la ubicación</h4>
                        <p>Haz clic en el mapa para completar las coordenadas, el nombre, el municipio, la dirección y el tipo del lugar. La imagen y la descripción se agregan manualmente.</p>
                    </div>
                    <div id="placeMap" class="dashboard-place-map" role="application" aria-label="Mapa para seleccionar la ubicación del lugar"></div>
                    <p class="dashboard-place-map-status" id="placeMapStatus" role="status" aria-live="polite">Selecciona un punto dentro de Baja California Sur, México.</p>
                </div>
                <label>Nombre<input name="nombre" required maxlength="150"></label>
                <label>Municipio<input name="municipio" required maxlength="100"></label>
                <label>Tipo<select name="tipo" required><option value="playa">Playa</option><option value="parque">Parque</option><option value="reserva">Reserva</option><option value="zona_urbana">Zona urbana</option><option value="otro">Otro</option></select></label>
                <label>Dirección<input name="direccion" maxlength="255"></label>
                <div class="dashboard-form-row">
                    <label>Latitud<input name="latitud" type="number" step="any" min="-90" max="90" required></label>
                    <label>Longitud<input name="longitud" type="number" step="any" min="-180" max="180" required></label>
                </div>
                <div class="location-selector">
                    <p class="location-selector-help">Arrastra el mapa para ubicar el pin exactamente donde se encuentra el lugar.</p>
                    <div class="location-selector-map" data-location-map>
                        <div class="location-selector-pin" aria-hidden="true"></div>
                    </div>
                    <div class="location-selector-coordinates" aria-hidden="true"></div>
                </div>
                <div class="dashboard-image-field" data-image-field data-max-images="1">
                    <label>Imagen del lugar<input name="imagen" type="file" accept="image/*" data-image-input></label>
                    <p class="dashboard-image-help">Puedes conservar la imagen actual o reemplazarla.</p>
                    <div class="dashboard-image-previews" data-image-previews aria-live="polite"></div>
                    <div data-image-removals></div>
                </div>
                <label>Descripción<textarea name="descripcion" rows="4" maxlength="2000"></textarea></label>
                <div class="dashboard-form-actions">
                    <button class="button button-primary" type="submit">Guardar lugar</button>
                    <button class="button button-secondary" type="button" data-cancel="place">Cancelar</button>
                </div>
            </form>
        </section>

        <section class="dashboard-section" id="section-voluntariados" data-resource="voluntariados" hidden>
            <div class="dashboard-section-heading">
                <div>
                    <span class="dashboard-eyebrow">PARTICIPACIÓN AMBIENTAL</span>
                    <h2>Voluntariados</h2>
                </div>
                <button type="button" class="button button-primary" data-new="voluntariado">Crear voluntariado</button>
            </div>
            <div class="dashboard-error" data-error="voluntariados" hidden>
                <p>No se pudieron cargar los voluntariados.</p>
                <button type="button" class="button button-secondary" data-retry="voluntariados">Reintentar</button>
            </div>
            <div class="dashboard-loading" data-loading="voluntariados">Cargando voluntariados…</div>
            <div class="dashboard-card-grid" id="voluntariadoCards"></div>
            <p class="dashboard-empty" data-empty="voluntariados" hidden>No hay voluntariados para mostrar.</p>
            <form class="dashboard-form" id="voluntariadoForm" hidden>
                <div class="dashboard-form-heading">
                    <div><span class="dashboard-eyebrow">EDITOR</span><h3 data-form-title="voluntariado">Nuevo voluntariado</h3></div>
                    <button class="icon-button" type="button" data-cancel="voluntariado" aria-label="Cerrar formulario">×</button>
                </div>
                <input type="hidden" name="id">
                <label>Título<input name="titulo" required maxlength="150"></label>
                <label>Categoría<input name="categoria" required maxlength="100"></label>
                <div class="dashboard-location-picker" data-location-picker data-mode="single">
                    <label>Ubicación
                        <input type="search" data-location-query placeholder="Buscar lugar por nombre o municipio" autocomplete="off" required>
                    </label>
                    <div class="dashboard-location-suggestions" data-location-suggestions role="listbox" hidden></div>
                    <div class="dashboard-location-selected" data-location-selected></div>
                    <div data-location-values></div>
                </div>
                <label>Organizador (ID, solo admin)<input name="organizadorId" maxlength="100"></label>
                <div class="dashboard-form-row">
                    <label>Fecha de inicio<input name="fechaInicio" type="date" required></label>
                    <label>Fecha de fin<input name="fechaFin" type="date" required></label>
                </div>
                <div class="dashboard-form-row">
                    <label>Hora de inicio<input name="horaInicio" type="time" required></label>
                    <label>Hora de fin<input name="horaFin" type="time" required></label>
                </div>
                <input type="hidden" name="latitud">
                <input type="hidden" name="longitud">
                <p class="dashboard-location-summary" data-location-summary role="status">Selecciona un lugar registrado para usar su ubicación.</p>
                <div class="dashboard-capacity-field">
                    <span class="dashboard-choice-legend">Cupo de participantes</span>
                    <div class="dashboard-capacity-control">
                        <span class="dashboard-capacity-icon" aria-hidden="true">👤</span>
                        <button type="button" class="dashboard-capacity-step" data-capacity-step="-1" aria-label="Disminuir cupo">−</button>
                        <input name="cupo" type="number" min="1" max="10000" value="10" inputmode="numeric" required aria-label="Número de participantes">
                        <button type="button" class="dashboard-capacity-step" data-capacity-step="1" aria-label="Aumentar cupo">+</button>
                        <span class="dashboard-capacity-unit">personas</span>
                    </div>
                </div>
                <label>Estado<select name="estado" required><option value="pendiente">Pendiente</option><option value="publicado">Publicado</option><option value="cancelado">Cancelado</option><option value="finalizado">Finalizado</option></select></label>
                <div class="dashboard-image-field" data-image-field data-max-images="10">
                    <label>Imágenes del voluntariado (hasta 10)<input name="imagenes[]" type="file" accept="image/*" multiple data-image-input></label>
                    <p class="dashboard-image-help">Puedes conservar las imágenes actuales, quitar alguna o agregar más.</p>
                    <div class="dashboard-image-previews" data-image-previews aria-live="polite"></div>
                    <div data-image-removals></div>
                </div>
                <div class="dashboard-token-field" data-token-field="requisitos">
                    <label for="volunteerRequirementsQuery">Requisitos</label>
                    <div class="dashboard-token-selected" data-token-selected></div>
                    <input id="volunteerRequirementsQuery" type="search" data-token-query placeholder="Buscar o agregar requisito…" autocomplete="off">
                    <div class="dashboard-token-suggestions" data-token-suggestions hidden></div>
                    <div data-token-values></div>
                </div>
                <div class="dashboard-token-field" data-token-field="materiales">
                    <label for="volunteerMaterialsQuery">Materiales necesarios</label>
                    <div class="dashboard-token-selected" data-token-selected></div>
                    <input id="volunteerMaterialsQuery" type="search" data-token-query placeholder="Buscar o agregar material…" autocomplete="off">
                    <div class="dashboard-token-suggestions" data-token-suggestions hidden></div>
                    <div data-token-values></div>
                </div>
                <label>Descripción<textarea name="descripcion" rows="5" required maxlength="3000"></textarea></label>
                <div class="dashboard-form-actions">
                    <button class="button button-primary" type="submit">Guardar voluntariado</button>
                    <button class="button button-secondary" type="button" data-cancel="voluntariado">Cancelar</button>
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
                <fieldset class="dashboard-choice-field">
                    <legend>Tipo de registro</legend>
                    <div class="dashboard-choice-grid">
                        <label class="dashboard-choice-card"><input type="radio" name="tipo" value="flora" required><span aria-hidden="true">🌿</span><strong>Flora</strong><small>Plantas y vegetación</small></label>
                        <label class="dashboard-choice-card"><input type="radio" name="tipo" value="fauna" required><span aria-hidden="true">🐾</span><strong>Fauna</strong><small>Animales y vida silvestre</small></label>
                    </div>
                </fieldset>
                <div class="dashboard-choice-field">
                    <span class="dashboard-choice-legend">Estado de conservación</span>
                    <input type="hidden" name="estadoConservacion">
                    <div class="dashboard-chip-options" data-single-choice="estadoConservacion">
                        <button type="button" data-option-value="Protección especial">🛡️ Protección especial</button>
                        <button type="button" data-option-value="en_peligro">🚨 En peligro</button>
                        <button type="button" data-option-value="en_peligro_critico">🔴 En peligro crítico</button>
                        <button type="button" data-option-value="vulnerable">⚠️ Vulnerable</button>
                        <button type="button" data-option-value="Casi amenazada">🟠 Casi amenazada</button>
                        <button type="button" data-option-value="Preocupación menor">🟢 Preocupación menor</button>
                        <button type="button" data-option-value="No evaluada">❔ No evaluada</button>
                    </div>
                    <label>Otro estado (opcional)<input type="text" data-choice-custom="estadoConservacion" maxlength="100" placeholder="Escribe otro estado"></label>
                </div>
                <div class="dashboard-choice-field">
                    <span class="dashboard-choice-legend">Hábitat (puedes elegir varios)</span>
                    <input type="hidden" name="habitat">
                    <div class="dashboard-chip-options" data-multiple-choice="habitat">
                        <label><input type="checkbox" value="Matorral xerófilo"><span>🌵 Matorral xerófilo</span></label>
                        <label><input type="checkbox" value="Desierto"><span>🏜️ Desierto</span></label>
                        <label><input type="checkbox" value="Manglar"><span>🌳 Manglar</span></label>
                        <label><input type="checkbox" value="Estero y humedal"><span>🪷 Estero y humedal</span></label>
                        <label><input type="checkbox" value="Dunas costeras"><span>🏖️ Dunas costeras</span></label>
                        <label><input type="checkbox" value="Playas y costas"><span>🌊 Playas y costas</span></label>
                        <label><input type="checkbox" value="Aguas costeras y bahías"><span>🐠 Aguas costeras y bahías</span></label>
                        <label><input type="checkbox" value="Arrecife"><span>🪸 Arrecife</span></label>
                        <label><input type="checkbox" value="Bosque de pino-encino"><span>🌲 Bosque de pino-encino</span></label>
                        <label><input type="checkbox" value="Zona intermareal"><span>🦀 Zona intermareal</span></label>
                    </div>
                    <label>Otro hábitat (opcional)<input type="text" data-choice-custom="habitat" maxlength="500" placeholder="Escribe otro hábitat"></label>
                </div>
                <p class="dashboard-location-warning">Las ubicaciones deben estar dentro de Baja California Sur, México.</p>
                <p class="dashboard-location-warning" id="approximateLocationNotice" hidden>
                    Esta especie requiere una ubicación aproximada. Al guardar otros cambios se conservará su ubicación original sin exponerla.
                </p>
                <div class="dashboard-location-picker" data-location-picker data-mode="multiple">
                    <label>Lugares relacionados
                        <input type="search" data-location-query placeholder="Buscar lugares por nombre o municipio" autocomplete="off">
                    </label>
                    <div class="dashboard-location-suggestions" data-location-suggestions role="listbox" hidden></div>
                    <div class="dashboard-location-selected" data-location-selected></div>
                    <div data-location-values></div>
                </div>
                <input type="hidden" name="latitud">
                <input type="hidden" name="longitud">
                <p class="dashboard-location-summary" data-location-summary role="status">La ubicación del registro se tomará del primer lugar relacionado que tenga coordenadas.</p>
                <div class="dashboard-image-field" data-image-field data-max-images="5">
                    <label>Imágenes (hasta 5)<input name="imagenes[]" type="file" accept="image/*" multiple data-image-input></label>
                    <p class="dashboard-image-help">Puedes conservar las imágenes actuales, quitar alguna o agregar más.</p>
                    <div class="dashboard-image-previews" data-image-previews aria-live="polite"></div>
                    <div data-image-removals></div>
                </div>
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

<dialog class="dashboard-dialog dashboard-confirm-dialog" id="actionConfirmDialog">
    <form id="actionConfirmForm">
        <div class="dashboard-confirm-icon" id="actionConfirmIcon" aria-hidden="true">
            !
        </div>

        <h2 id="actionConfirmTitle">Confirmar acción</h2>

        <p id="actionConfirmMessage">
            ¿Estás seguro de que deseas continuar?
        </p>

        <div class="dashboard-form-actions">
            <button
                class="button button-primary"
                type="submit"
                id="actionConfirmSubmit"
            >
                Confirmar
            </button>

            <button
                class="button button-secondary"
                type="button"
                id="cancelActionConfirm"
            >
                Cancelar
            </button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
    @vite('resources/js/dashboard/dashboard.js')
@endpush
