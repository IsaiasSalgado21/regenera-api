import '../../css/dashboard.css';
import QRCode from 'qrcode';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import {
    bajaCaliforniaSurBounds,
    isInsideBajaCaliforniaSur,
} from '../geo/baja-california-sur';
import { dashboardApi } from './dashboard-api';
import { initializeLocationSelector } from '../map-selector';

const root = document.getElementById('dashboardRoot');
if (root) initializeDashboard();

function initializeDashboard() {
    const routeRole = root.dataset.dashboardRole;
    const cache = { articles: [], places: [], flora: [], voluntariados: [], users: [], organizers: [], pending: [], notifications: [] };
    const byId = id => document.getElementById(id);
    const volunteerTokenOptions = {
        requisitos: [
            ['🧴', 'Usar bloqueador biodegradable'],
            ['👟', 'Usar calzado cómodo'],
            ['💧', 'Llevar agua suficiente'],
            ['🧢', 'Llevar gorra o sombrero'],
            ['🧤', 'Seguir indicaciones del equipo'],
            ['♻️', 'Separar y recoger residuos'],
            ['🪪', 'Ser mayor de edad'],
            ['🤝', 'Participar en equipo'],
        ],
        materiales: [
            ['🧤', 'Guantes de trabajo'],
            ['🧴', 'Bloqueador biodegradable'],
            ['💧', 'Agua potable'],
            ['🧢', 'Gorra o sombrero'],
            ['🧹', 'Bolsas para residuos'],
            ['🛠️', 'Herramientas de trabajo'],
            ['🦺', 'Chaleco de seguridad'],
            ['🩹', 'Botiquín de primeros auxilios'],
        ],
    };
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    }[character]));

    function showNotice(message, kind = 'success') {
        const notice = byId('dashboardNotice');
        notice.textContent = message;
        notice.className = `dashboard-notice is-${kind}`;
        notice.hidden = false;
        window.setTimeout(() => { notice.hidden = true; }, 5000);
    }

    function showResourceState(resource, state) {
        root.querySelector(`[data-loading="${resource}"]`).hidden = state !== 'loading';
        root.querySelector(`[data-error="${resource}"]`).hidden = state !== 'error';
        root.querySelector(`[data-empty="${resource}"]`).hidden = state !== 'empty';
    }

    function renderOrganizerOptions(selectedId = '') {
        const select = root.querySelector('[data-organizer-select]');
        if (!select) return;
        select.replaceChildren(new Option('Selecciona un organizador', ''));
        if (!cache.organizers.length) {
            select.add(new Option('No hay organizadores registrados', ''));
            return;
        }
        cache.organizers.forEach(organizer => {
            const label = organizer.estado === 'inactivo'
                ? `${organizer.nombre} · inactivo`
                : `${organizer.nombre}${organizer.correo ? ` · ${organizer.correo}` : ''}`;
            select.add(new Option(label, organizer.id));
        });
        select.value = selectedId;
    }

    async function loadOrganizers() {
        const response = await dashboardApi.listOrganizadores();
        cache.organizers = response.data || [];
        renderOrganizerOptions(root.querySelector('[data-organizer-select]')?.value || '');
    }

    function formatDateLabel(value) {
        if (!value) return 'dd/mm/aaaa';
        const [year, month, day] = value.split('-').map(Number);
        return `${String(day).padStart(2, '0')}/${String(month).padStart(2, '0')}/${year}`;
    }

    function dateFromValue(value) {
        if (!value) return null;
        const [year, month, day] = value.split('-').map(Number);
        return new Date(year, month - 1, day);
    }

    function dateValue(date) {
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    }

    function renderDatePicker(picker) {
        const input = picker.querySelector('input[type="hidden"]');
        const label = picker.querySelector('[data-date-label]');
        const monthLabel = picker.querySelector('[data-date-month]');
        const days = picker.querySelector('[data-date-days]');
        const date = dateFromValue(input.value);
        const view = picker.dateView || date || new Date();
        const year = view.getFullYear();
        const month = view.getMonth();
        const firstWeekday = (new Date(year, month, 1).getDay() + 6) % 7;
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const startValue = picker.closest('form').elements.fechaInicio.value;
        const endValue = picker.closest('form').elements.fechaFin.value;
        const isEnd = input.name === 'fechaFin';

        label.textContent = formatDateLabel(input.value);
        monthLabel.textContent = new Intl.DateTimeFormat('es-MX', {
            month: 'long',
            year: 'numeric',
        }).format(view);
        days.replaceChildren();

        for (let blank = 0; blank < firstWeekday; blank += 1) {
            const spacer = document.createElement('span');
            spacer.className = 'dashboard-date-empty';
            days.append(spacer);
        }
        for (let day = 1; day <= daysInMonth; day += 1) {
            const current = new Date(year, month, day);
            const value = dateValue(current);
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.dateValue = value;
            button.textContent = String(day);
            button.setAttribute('aria-label', formatDateLabel(value));
            if (value === input.value) button.classList.add('is-selected');
            if (value === dateValue(new Date())) button.classList.add('is-today');
            if ((isEnd && startValue && value < startValue) || (!isEnd && endValue && value > endValue)) {
                button.disabled = true;
            }
            days.append(button);
        }
    }

    function initializeDatePickers(form) {
        form.querySelectorAll('[data-date-picker]').forEach(picker => {
            const value = picker.querySelector('input[type="hidden"]').value;
            picker.dateView = dateFromValue(value) || new Date();
            renderDatePicker(picker);
            picker.querySelector('[data-date-popover]').hidden = true;
        });
    }

    function setVolunteerStatus(form, status) {
        form.elements.estado.value = status;
        form.querySelectorAll('[data-status-value]').forEach(button => {
            const selected = button.dataset.statusValue === status;
            button.classList.toggle('is-selected', selected);
            button.setAttribute('aria-pressed', String(selected));
        });
    }

    function card({ title, description, meta, id, actions = '', media = '', interactive = false }) {
        return `<article class="dashboard-card${interactive ? ' dashboard-card-interactive' : ''}" data-record-id="${escapeHtml(id)}"${interactive ? ` data-open-volunteer="${escapeHtml(id)}" tabindex="0" role="button" aria-label="Abrir dashboard de ${escapeHtml(title)}"` : ''}>
            ${media}
            <div class="dashboard-card-body">
                <div class="dashboard-card-meta">${meta}</div>
                <h3>${escapeHtml(title)}</h3>
                <p>${escapeHtml(description)}</p>
            </div>
            <div class="dashboard-card-actions">${actions}</div>
        </article>`;
    }

    function action(label, actionName, id, style = 'secondary') {
        return `<button class="button button-${style}" type="button" data-action="${actionName}" data-id="${escapeHtml(id)}">${label}</button>`;
    }

    function renderArticles(user) {
        const isAdmin = user.rol === 'admin';
        const ownArticles = isAdmin
            ? cache.articles
            : cache.articles.filter(article => String(article.autorId) === String(user.id));
        const list = isAdmin ? ownArticles : ownArticles;
        const element = byId('articleCards');

        element.innerHTML = list.map(article => {
            const status = `<span class="status-pill status-${escapeHtml(article.estado)}">${escapeHtml(article.estado)}</span>`;
            let actions = '';

            if (isAdmin) {
                actions += action('Editar', 'edit-article', article.id);
                if (article.estado === 'pendiente') {
                    actions += action('Aprobar', 'approve-article', article.id, 'primary');
                    actions += action('Rechazar', 'reject-article', article.id, 'danger');
                }
                if (article.estado === 'publicado') {
                    actions += action('Despublicar', 'unpublish-article', article.id, 'danger');
                }

                if (article.estado === 'borrador') {
                    actions += action('Publicar', 'publish-article', article.id, 'primary');
                    actions += action('Eliminar borrador', 'delete-article', article.id, 'danger');
                }
            } else {
                if (['borrador', 'rechazado'].includes(article.estado)) {
                    actions += action('Editar', 'edit-article', article.id);
                    actions += action('Enviar a revisión', 'submit-article', article.id, 'primary');
                }
            }

            return card({
                title: article.titulo,
                description: article.motivoRechazo
                    ? `${article.contenido} Motivo de rechazo: ${article.motivoRechazo}`
                    : article.contenido,
                meta: `${status}<span>${escapeHtml(article.categoria)}</span>`,
                id: article.id,
                actions,
            });
        }).join('');

        showResourceState('articles', list.length ? 'ready' : 'empty');
        root.querySelector('[data-article-heading]').textContent = isAdmin
            ? 'Revisión y gestión de artículos'
            : 'Mis artículos';
        root.querySelector('[data-article-kicker]').textContent = isAdmin
            ? 'BANDEJA DE REVISIÓN'
            : 'BORRADORES Y PUBLICACIONES';
    }

    function renderPlaces(user) {
        const list = user.rol === 'admin'
            ? cache.places
            : cache.places.filter(place => String(place.creadoPor) === String(user.id));
        const cards = byId('placeCards');

        placePreviewMaps.forEach(map => map.remove());
        placePreviewMaps.clear();

        cards.innerHTML = list.map(place => {
            const meta = `<span class="status-pill status-${escapeHtml(place.estado)}">${escapeHtml(place.estado)}</span><span>${escapeHtml(place.tipo)}</span><span>${escapeHtml(place.municipio)}</span>`;
            let actions = action('Editar', 'edit-place', place.id);
            if (user.rol === 'admin') {
                if (place.estado === 'activo') {
                    actions += action('Archivar', 'archive-place', place.id, 'danger');
                }

                if (place.estado === 'archivado') {
                    actions += action('Publicar', 'publish-place', place.id, 'primary');
                }
            }
            const hasCoordinates = place.latitud !== null && place.latitud !== ''
                && place.longitud !== null && place.longitud !== ''
                && isInsideBajaCaliforniaSur(Number(place.latitud), Number(place.longitud));
            const hasImage = user.rol === 'admin' && typeof place.imagen === 'string' && place.imagen !== '';
            const media = user.rol !== 'admin'
                ? ''
                : `<div class="dashboard-place-carousel" data-place-carousel role="group" aria-roledescription="carrusel" aria-label="Mapa e imagen de ${escapeHtml(place.nombre)}">
                    <div class="dashboard-place-slides">
                        <div class="dashboard-place-slide is-active" data-place-slide="0" aria-hidden="false">
                            ${hasCoordinates
                                ? `<div class="dashboard-place-preview" data-place-preview data-lat="${escapeHtml(place.latitud)}" data-lng="${escapeHtml(place.longitud)}" role="img" aria-label="Mapa de ${escapeHtml(place.nombre)}"></div>
                                    <a class="dashboard-place-map-link" href="https://www.openstreetmap.org/?mlat=${encodeURIComponent(place.latitud)}&mlon=${encodeURIComponent(place.longitud)}#map=15/${encodeURIComponent(place.latitud)}/${encodeURIComponent(place.longitud)}" target="_blank" rel="noopener noreferrer">Ver punto en el mapa</a>`
                                : '<p class="dashboard-place-no-coordinates">Este lugar no tiene una ubicación registrada dentro de Baja California Sur.</p>'}
                        </div>
                        ${hasImage
                            ? `<div class="dashboard-place-slide" data-place-slide="1" aria-hidden="true">
                                <img class="dashboard-place-image" src="${escapeHtml(place.imagen)}" alt="Imagen de ${escapeHtml(place.nombre)}" loading="lazy">
                            </div>`
                            : ''}
                    </div>
                    ${hasImage
                        ? `<div class="dashboard-place-carousel-controls">
                            <button class="dashboard-place-carousel-button" type="button" data-carousel-step="-1" aria-label="Ver elemento anterior">‹</button>
                            <span data-carousel-position aria-live="polite">1 / 2</span>
                            <button class="dashboard-place-carousel-button" type="button" data-carousel-step="1" aria-label="Ver elemento siguiente">›</button>
                        </div>`
                        : ''}
                </div>`;

            return card({
                title: place.nombre,
                description: place.descripcion || place.direccion || 'Sin descripción registrada.',
                meta,
                id: place.id,
                actions,
                media,
            });
        }).join('');
        showResourceState('places', list.length ? 'ready' : 'empty');
        root.querySelector('[data-place-heading]').textContent = user.rol === 'admin'
            ? 'Todos los lugares'
            : 'Mis lugares';
        if (user.rol === 'admin' && !byId('section-places').hidden) initializePlacePreviews();
    }

    function renderFlora(user) {
        const list = user.rol === 'admin'
            ? cache.flora
            : cache.flora.filter(record => String(record.creadoPor) === String(user.id));

        byId('floraCards').innerHTML = list.map(record => {
            const meta = `<span class="status-pill status-${escapeHtml(record.estado)}">${escapeHtml(record.estado)}</span><span>${escapeHtml(record.tipo)}</span><span>${escapeHtml(record.estadoConservacion || 'Conservación sin especificar')}</span>`;
            let actions = action('Editar', 'edit-flora', record.id);
            if (user.rol === 'admin') {
                if (record.estado === 'publicado') {
                    actions += action('Archivar', 'archive-flora', record.id, 'danger');
                }

                if (record.estado === 'archivado') {
                    actions += action('Publicar', 'publish-flora', record.id, 'primary');
                }
            }

            return card({
                title: record.nombre,
                description: `${record.nombreCientifico || ''} ${record.descripcion || ''}`,
                meta,
                id: record.id,
                actions,
            });
        }).join('');
        showResourceState('flora', list.length ? 'ready' : 'empty');
        root.querySelector('[data-flora-heading]').textContent = user.rol === 'admin'
            ? 'Todos los registros de flora y fauna'
            : 'Mis registros de flora y fauna';
    }

    function renderVoluntariados() {
        const list = cache.voluntariados;

        byId('voluntariadoCards').innerHTML = list.map(voluntariado => {
            const meta = `<span class="status-pill status-${escapeHtml(voluntariado.estado)}">${escapeHtml(voluntariado.estado)}</span><span>${escapeHtml(voluntariado.categoria)}</span><span>Organiza: ${escapeHtml(voluntariado.organizadorNombre || 'No disponible')}</span><span>${escapeHtml(voluntariado.fechaInicio || '')}</span>`;
            let actions = '';
            if (user.rol === 'admin') {
                actions += action('Editar', 'edit-voluntariado', voluntariado.id);
                if (voluntariado.estado === 'pendiente') {
                    actions += action('Aprobar', 'approve-voluntariado', voluntariado.id, 'primary');
                    actions += action('Rechazar', 'reject-voluntariado', voluntariado.id, 'danger');
                }
                actions += action('Eliminar', 'delete-voluntariado', voluntariado.id, 'danger');
            } else if (!voluntariado.iniciadoPorFecha
                && (user.organizadorVerificado || ['pendiente', 'rechazado'].includes(voluntariado.estado))) {
                actions += action('Editar y reenviar', 'edit-voluntariado', voluntariado.id);
                actions += action('Eliminar', 'delete-voluntariado', voluntariado.id, 'danger');
            }

            const attendanceQr = voluntariado.tokenAsistencia
                ? `<div class="dashboard-attendance-qr">
                    <canvas data-attendance-qr="${escapeHtml(voluntariado.tokenAsistencia)}" aria-label="Código QR de asistencia para ${escapeHtml(voluntariado.titulo)}"></canvas>
                    <div><strong>Asistencia por QR</strong>
                    <p>Código para asistentes: <code>${escapeHtml(voluntariado.codigoCorto || '')}</code></p></div>
                </div>`
                : '<p class="dashboard-attendance-qr-error">No se pudo cargar el token de asistencia. Actualiza la página o contacta al administrador.</p>';

            return card({
                title: voluntariado.titulo,
                description: voluntariado.descripcion,
                meta,
                id: voluntariado.id,
                actions,
                media: attendanceQr,
                interactive: true,
            });
        }).join('');

        byId('voluntariadoCards').querySelectorAll('[data-attendance-qr]').forEach(canvas => {
            const scanUrl = `${root.dataset.attendanceUrl}#${encodeURIComponent(canvas.dataset.attendanceQr)}`;
            QRCode.toCanvas(canvas, scanUrl, {
                width: 176,
                margin: 1,
                color: { dark: '#164f3d', light: '#ffffff' },
            }).catch(error => {
                console.error('No se pudo generar el código QR de asistencia.', error);
                const message = document.createElement('p');
                message.className = 'dashboard-attendance-qr-error';
                message.textContent = 'No se pudo generar este código QR.';
                canvas.replaceWith(message);
            });
        });

        showResourceState('voluntariados', list.length ? 'ready' : 'empty');
    }

    function formatDateTime(value) {
        if (!value) return 'Sin registro';
        const date = new Date(value);
        return Number.isNaN(date.getTime())
            ? String(value)
            : new Intl.DateTimeFormat('es-MX', { dateStyle: 'medium', timeStyle: 'short' }).format(date);
    }

    function formatEventDate(value) {
        if (!value) return 'Sin fecha';
        const [year, month, day] = String(value).slice(0, 10).split('-').map(Number);
        return year && month && day ? `${day}/${month}/${year}` : String(value);
    }

    function detailList(values) {
        const items = (Array.isArray(values) ? values : values ? [values] : []).filter(Boolean);
        return items.length
            ? `<ul>${items.map(item => `<li>${escapeHtml(item)}</li>`).join('')}</ul>`
            : '<p class="volunteer-detail-empty">No especificado</p>';
    }

    function renderVolunteerDashboard(data) {
        const { voluntariado, estadisticas, participantes } = data;
        const ownsVolunteer = String(voluntariado.organizadorId) === String(user.id);
        const canEdit = user.rol === 'admin'
            || (ownsVolunteer && !voluntariado.iniciadoPorFecha
                && (user.organizadorVerificado || ['pendiente', 'rechazado'].includes(voluntariado.estado)));
        const image = voluntariado.imagen
            ? `<img class="volunteer-detail-cover" src="${escapeHtml(voluntariado.imagen)}" alt="" loading="lazy">`
            : '';
        const lugar = voluntariado.lugar
            ? [voluntariado.lugar.nombre, voluntariado.lugar.municipio, voluntariado.lugar.direccion].filter(Boolean).join(' · ')
            : 'Lugar no disponible';
        const actionButtons = `${canEdit ? action('Editar voluntariado', 'edit-voluntariado', voluntariado.id) : ''}${user.rol === 'admin' && voluntariado.estado === 'pendiente' ? `${action('Aprobar', 'approve-voluntariado', voluntariado.id, 'primary')}${action('Rechazar', 'reject-voluntariado', voluntariado.id, 'danger')}` : ''}`;
        const totalRegistros = Math.max(0, Number(estadisticas.totalInscripciones ?? participantes.length) || 0);
        const outcomes = [
            { key: 'attended', label: 'Asistieron', value: Number(estadisticas.asistencias) || 0 },
            { key: 'absent', label: 'Faltaron', value: Number(estadisticas.faltas) || 0 },
            { key: 'cancelled', label: 'Cancelaron', value: Number(estadisticas.canceladas) || 0 },
            { key: 'pending', label: 'Pendientes', value: Number(estadisticas.pendientes) || 0 },
        ];
        const outcomeChart = `<section class="volunteer-outcomes" aria-label="Resultado de ${escapeHtml(voluntariado.titulo)}">
            <div class="volunteer-outcomes-heading"><h3>Participación</h3><strong>${totalRegistros} inscripciones totales</strong></div>
            <div class="volunteer-outcomes-bar" role="img" aria-label="${outcomes.map(item => `${item.label}: ${item.value}`).join(', ')}">
                ${outcomes.map(item => {
                    const width = totalRegistros ? (item.value / totalRegistros) * 100 : 0;
                    return width > 0 ? `<span class="outcome-${item.key}" style="width:${width}%"></span>` : '';
                }).join('')}
            </div>
            <ul class="volunteer-outcomes-legend">${outcomes.map(item => `<li><span class="outcome-dot outcome-${item.key}" aria-hidden="true"></span><span>${item.label}</span><strong>${item.value}</strong></li>`).join('')}</ul>
        </section>`;
        const people = participantes.map(participante => {
            const estadoInscripcion = participante.estadoInscripcion || 'desconocido';
            const cancelado = estadoInscripcion === 'cancelada';
            const confirmada = Boolean(participante.asistencia?.confirmada);
            const eventoCancelado = voluntariado.cancelado || voluntariado.estado === 'cancelado';
            const estadoAsistencia = cancelado
                ? 'cancelada'
                : confirmada
                    ? 'confirmada'
                    : participante.falto
                        ? 'falta'
                        : eventoCancelado ? 'voluntariado-cancelado' : 'pendiente';
            const etiquetaAsistencia = cancelado
                ? 'Canceló inscripción'
                : confirmada
                    ? 'Asistió'
                    : participante.falto
                        ? 'No asistió'
                        : eventoCancelado ? 'Voluntariado cancelado' : 'Pendiente';
            const nombre = participante.nombre || 'Usuario sin nombre';
            const inicial = escapeHtml(nombre.trim().charAt(0).toLocaleUpperCase() || '?');
            const avatar = typeof participante.foto === 'string' && participante.foto
                ? `<img src="${escapeHtml(participante.foto)}" alt="" loading="lazy">`
                : inicial;
            const fechaAsistencia = participante.asistencia
                ? (participante.asistencia.fecha
                    ? formatDateTime(participante.asistencia.fecha)
                    : participante.asistencia.hora || 'Sin fecha')
                : 'Pendiente';
            const solicitud = participante.solicitudAsistencia;
            const accionesSolicitud = solicitud?.estado === 'pendiente'
                ? `<div class="volunteer-attendance-request-actions"><button class="button button-primary" type="button" data-attendance-request-action="aceptar" data-request-id="${escapeHtml(solicitud.id)}">Aceptar solicitud</button><button class="button button-secondary" type="button" data-attendance-request-action="rechazar" data-request-id="${escapeHtml(solicitud.id)}">Rechazar</button></div>`
                : solicitud?.estado === 'aceptando'
                    ? '<span>Confirmando solicitud…</span>'
                    : solicitud?.estado === 'rechazada'
                        ? `<span>Solicitud rechazada${solicitud.motivoRespuesta ? `: ${escapeHtml(solicitud.motivoRespuesta)}` : ''}</span>`
                        : '';
            return `<tr data-participant-row data-name="${escapeHtml(nombre.toLocaleLowerCase())}" data-email="${escapeHtml(participante.correo || '').toLocaleLowerCase()}" data-inscription="${escapeHtml(estadoInscripcion)}" data-attendance="${estadoAsistencia}">
                <td><div class="volunteer-person"><span class="volunteer-avatar">${avatar}</span><span><strong>${escapeHtml(nombre)}</strong><small>${escapeHtml(participante.correo || 'Sin correo')}</small></span></div></td>
                <td>${formatDateTime(participante.fechaInscripcion)}</td>
                <td><span class="volunteer-state volunteer-state-${escapeHtml(estadoInscripcion)}">${escapeHtml(estadoInscripcion)}</span></td>
                <td><span class="volunteer-state volunteer-state-${estadoAsistencia}">${etiquetaAsistencia}</span></td>
                <td>${escapeHtml(fechaAsistencia)}</td>
                <td>${escapeHtml(participante.asistencia?.metodo || '—')}</td>
                <td><details class="volunteer-person-details"><summary>Ficha</summary><div><span>Identificador</span><code>${escapeHtml(participante.usuarioId || '—')}</code>${participante.observaciones ? `<span>Observaciones</span><p>${escapeHtml(participante.observaciones)}</p>` : ''}${accionesSolicitud}</div></details></td>
            </tr>`;
        }).join('');
        const qrToken = voluntariado.tokenAsistencia || '';
        const qrNotice = qrToken
            ? `<div class="volunteer-qr-layout">
                <div class="volunteer-qr-frame"><canvas id="volunteerDetailQr" aria-label="Código QR de asistencia"></canvas></div>
                <div class="volunteer-qr-info">
                    <p>Muestra este QR o el código corto a los asistentes para registrar su asistencia.</p>
                    <label for="volunteerShortCode">Código corto</label>
                    <div class="volunteer-token-row"><code id="volunteerShortCode">${escapeHtml(voluntariado.codigoCorto || '')}</code><button class="button button-secondary" type="button" data-copy-volunteer-token data-copy-target="volunteerShortCode">Copiar</button></div>
                    <p class="volunteer-qr-usage">${Number(estadisticas.registrosQr) || 0} registros confirmados con método QR</p>
                    <div class="volunteer-detail-actions">
                        <button class="button button-secondary" type="button" data-download-volunteer-qr disabled>Descargar QR</button>
                    </div>
                </div>
            </div>`
            : '<p class="volunteer-detail-empty">Este voluntariado no tiene un token de asistencia disponible.</p>';

        byId('voluntariadoDetailContent').innerHTML = `
            <div class="volunteer-detail-toolbar">
                <button class="volunteer-back-button" type="button" data-volunteer-back><span aria-hidden="true">←</span> Voluntariados</button>
                <div class="volunteer-detail-actions">${actionButtons}</div>
            </div>
            <header class="volunteer-detail-hero">
                ${image}
                <div class="volunteer-detail-hero-content">
                    <div class="volunteer-detail-kicker"><span class="volunteer-status ${voluntariado.estado === 'publicado' ? 'is-active' : 'is-inactive'}"><i></i>${escapeHtml(voluntariado.estado || 'Sin estado')}</span><span>${escapeHtml(voluntariado.categoria || 'Voluntariado')}</span></div>
                    <h2>${escapeHtml(voluntariado.titulo)}</h2>
                    <p class="volunteer-author">Organiza: ${escapeHtml(voluntariado.organizador?.nombre || voluntariado.organizadorNombre || 'No disponible')}</p>
                    <p>${escapeHtml(voluntariado.descripcion || 'Sin descripción disponible.')}</p>
                    <div class="volunteer-hero-meta"><span>◷ ${escapeHtml(formatEventDate(voluntariado.fechaInicio))}${voluntariado.fechaFin && voluntariado.fechaFin !== voluntariado.fechaInicio ? ` — ${escapeHtml(formatEventDate(voluntariado.fechaFin))}` : ''} · ${escapeHtml(voluntariado.horaInicio || '—')}–${escapeHtml(voluntariado.horaFin || '—')}</span><span>⌖ ${escapeHtml(lugar)}</span><span>Organiza: ${escapeHtml(voluntariado.organizador?.nombre || 'No disponible')}</span><span>ID ${escapeHtml(voluntariado.id)}</span></div>
                </div>
            </header>
            ${outcomeChart}
            <div class="volunteer-stat-grid">
                <article class="volunteer-stat-card"><span>Inscritos</span><strong>${Number(estadisticas.inscritos) || 0}<small> / ${Number(estadisticas.cupo) || 0}</small></strong><div class="volunteer-stat-track"><i style="width:${Math.min(100, Math.max(0, Number(estadisticas.ocupacion) || 0))}%"></i></div><small>${Number(estadisticas.disponibles) || 0} lugares disponibles</small></article>
                <article class="volunteer-stat-card"><span>Ocupación</span><strong>${Number(estadisticas.ocupacion) || 0}<small>%</small></strong><div class="volunteer-stat-track"><i style="width:${Math.min(100, Math.max(0, Number(estadisticas.ocupacion) || 0))}%"></i></div><small>Del cupo total</small></article>
                <article class="volunteer-stat-card"><span>Asistencias confirmadas</span><strong>${Number(estadisticas.asistencias) || 0}<small> / ${Number(estadisticas.inscritos) || 0}</small></strong><div class="volunteer-stat-track"><i style="width:${Math.min(100, Math.max(0, Number(estadisticas.porcentajeAsistencia) || 0))}%"></i></div><small>${Number(estadisticas.porcentajeAsistencia) || 0}% de inscritos</small></article>
                <article class="volunteer-stat-card"><span>Pendientes de asistencia</span><strong>${Number(estadisticas.pendientes) || 0}</strong><small>${Number(estadisticas.faltas) || 0} faltas · ${Number(estadisticas.canceladas) || 0} inscripciones canceladas</small></article>
            </div>
            <div class="volunteer-dashboard-columns">
                <section class="volunteer-panel volunteer-participants-panel">
                    <div class="volunteer-panel-heading"><div><span class="volunteer-panel-eyebrow">COMUNIDAD</span><h3>Participantes</h3></div><span class="volunteer-result-count" id="volunteerParticipantCount">${participantes.length} resultados</span></div>
                    <div class="volunteer-participant-filters">
                        <label class="volunteer-search"><span aria-hidden="true">⌕</span><input type="search" data-participant-search placeholder="Buscar nombre o correo" aria-label="Buscar participantes"></label>
                        <select data-participant-inscription aria-label="Filtrar por estado de inscripción"><option value="">Todas las inscripciones</option><option value="inscrita">Inscrita</option><option value="activa">Activa</option><option value="completada">Completada</option><option value="cancelada">Cancelada</option></select>
                        <select data-participant-attendance aria-label="Filtrar por asistencia"><option value="">Todas las asistencias</option><option value="confirmada">Asistió</option><option value="falta">No asistió</option><option value="pendiente">Pendiente</option><option value="cancelada">Canceló inscripción</option><option value="voluntariado-cancelado">Voluntariado cancelado</option></select>
                    </div>
                    <div class="volunteer-participant-scroll"><table class="volunteer-participant-table"><thead><tr><th>Participante</th><th>Fecha de inscripción</th><th>Estado de inscripción</th><th>Asistencia</th><th>Registro de asistencia</th><th>Método</th><th></th></tr></thead><tbody>${people || '<tr><td class="volunteer-table-empty" colspan="7">Todavía no hay inscripciones para este voluntariado.</td></tr>'}</tbody></table></div>
                </section>
                <section class="volunteer-panel volunteer-qr-panel"><div class="volunteer-panel-heading"><div><span class="volunteer-panel-eyebrow">CONTROL DE ACCESO</span><h3>Asistencia por QR</h3></div></div>${qrNotice}</section>
            </div>
            <details class="volunteer-panel volunteer-full-details"><summary><span><span class="volunteer-panel-eyebrow">INFORMACIÓN DEL EVENTO</span><strong>Detalles completos</strong></span><span aria-hidden="true">＋</span></summary>
                <div class="volunteer-information-grid">
                    <section><h4>Actividad</h4><dl><dt>Título</dt><dd>${escapeHtml(voluntariado.titulo)}</dd><dt>Descripción</dt><dd>${escapeHtml(voluntariado.descripcion || 'Sin descripción')}</dd><dt>Categoría</dt><dd>${escapeHtml(voluntariado.categoria || '—')}</dd><dt>Identificador</dt><dd><code>${escapeHtml(voluntariado.id)}</code></dd></dl></section>
                    <section><h4>Fecha y lugar</h4><dl><dt>Inicio</dt><dd>${escapeHtml(formatEventDate(voluntariado.fechaInicio))} · ${escapeHtml(voluntariado.horaInicio || '—')}</dd><dt>Finalización</dt><dd>${escapeHtml(formatEventDate(voluntariado.fechaFin))} · ${escapeHtml(voluntariado.horaFin || '—')}</dd><dt>Lugar</dt><dd>${escapeHtml(lugar)}</dd><dt>Id del lugar</dt><dd><code>${escapeHtml(voluntariado.lugarId || '—')}</code></dd><dt>Coordenadas</dt><dd>${voluntariado.latitud != null && voluntariado.longitud != null ? `${escapeHtml(voluntariado.latitud)}, ${escapeHtml(voluntariado.longitud)}` : 'No disponibles'}</dd><dt>Cupo</dt><dd>${Number(estadisticas.cupo) || 0} personas</dd></dl></section>
                    <section><h4>Organización</h4><dl><dt>Responsable</dt><dd>${escapeHtml(voluntariado.organizador?.nombre || 'No disponible')}</dd><dt>Correo</dt><dd>${escapeHtml(voluntariado.organizador?.correo || '—')}</dd><dt>Estado</dt><dd>${escapeHtml(voluntariado.estado || '—')}</dd><dt>Creado</dt><dd>${escapeHtml(formatDateTime(voluntariado.creadoEn))}</dd><dt>Actualizado</dt><dd>${escapeHtml(formatDateTime(voluntariado.actualizadoEn))}</dd></dl></section>
                    <section><h4>Preparación</h4><div class="volunteer-detail-list"><strong>Requisitos</strong>${detailList(voluntariado.requisitos)}<strong>Materiales necesarios</strong>${detailList(voluntariado.materiales)}</div></section>
                </div>
            </details>`;

        applyParticipantFilters();
        byId('voluntariadoDetailContent').querySelectorAll('[data-attendance-request-action]').forEach(button => {
            button.addEventListener('click', async () => {
                let motivo = '';
                if (button.dataset.attendanceRequestAction === 'rechazar') {
                    motivo = window.prompt('Motivo del rechazo (opcional):') || '';
                }
                button.disabled = true;
                try {
                    await dashboardApi.respondAttendanceRequest(
                        button.dataset.requestId,
                        button.dataset.attendanceRequestAction,
                        motivo,
                    );
                    showNotice(button.dataset.attendanceRequestAction === 'aceptar'
                        ? 'La asistencia fue confirmada.'
                        : 'La solicitud fue rechazada.');
                    await showVolunteerDetail(voluntariado.id, false);
                } catch (error) {
                    showNotice(error.message, 'error');
                    button.disabled = false;
                }
            });
        });
        if (qrToken) {
            const canvas = byId('volunteerDetailQr');
            const scanUrl = `${root.dataset.attendanceUrl}#${encodeURIComponent(qrToken)}`;
            QRCode.toCanvas(canvas, scanUrl, {
                width: 256,
                margin: 2,
                color: { dark: '#164f3d', light: '#ffffff' },
            }).then(() => {
                const downloadButton = byId('voluntariadoDetailContent').querySelector('[data-download-volunteer-qr]');
                if (downloadButton) downloadButton.disabled = false;
            }).catch(error => {
                console.error('No se pudo generar el código QR de asistencia.', error);
                canvas.replaceWith(Object.assign(document.createElement('p'), {
                    className: 'volunteer-detail-empty',
                    textContent: 'No se pudo mostrar el código QR.',
                }));
            });
        }
    }

    function applyParticipantFilters() {
        const content = byId('voluntariadoDetailContent');
        const search = content.querySelector('[data-participant-search]')?.value.trim().toLocaleLowerCase() || '';
        const inscription = content.querySelector('[data-participant-inscription]')?.value || '';
        const attendance = content.querySelector('[data-participant-attendance]')?.value || '';
        let visible = 0;
        content.querySelectorAll('[data-participant-row]').forEach(row => {
            const matches = (!search || row.dataset.name.includes(search) || row.dataset.email.includes(search))
                && (!inscription || row.dataset.inscription === inscription)
                && (!attendance || row.dataset.attendance === attendance);
            row.hidden = !matches;
            if (matches) visible += 1;
        });
        const counter = byId('volunteerParticipantCount');
        if (counter) counter.textContent = `${visible} ${visible === 1 ? 'resultado' : 'resultados'}`;
    }

    function setVolunteerListVisible(visible, clearUrl = true) {
        const list = byId('voluntariadoListView');
        const detail = byId('voluntariadoDetailView');
        list.hidden = !visible;
        detail.hidden = visible;
        openVolunteerId = visible ? null : openVolunteerId;
        if (visible) {
            byId('voluntariadoDetailContent').replaceChildren();
            byId('voluntariadoDetailLoading').hidden = true;
            byId('voluntariadoDetailError').hidden = true;
            if (clearUrl) {
                const url = new URL(window.location.href);
                url.searchParams.delete('voluntariado');
                window.history.replaceState(null, '', `${url.pathname}${url.search}${url.hash}`);
            }
        }
    }

    async function showVolunteerDetail(id, updateHistory = true) {
        openVolunteerId = String(id);
        setVolunteerListVisible(false, false);
        byId('voluntariadoDetailError').hidden = true;
        byId('voluntariadoDetailContent').replaceChildren();
        byId('voluntariadoDetailLoading').hidden = false;
        if (updateHistory) {
            const url = new URL(window.location.href);
            url.searchParams.set('voluntariado', openVolunteerId);
            window.history.pushState({ volunteerDashboard: true }, '', `${url.pathname}${url.search}${url.hash}`);
        }
        try {
            const response = await dashboardApi.voluntariadoDashboard(openVolunteerId);
            if (openVolunteerId !== String(id)) return;
            byId('voluntariadoDetailLoading').hidden = true;
            renderVolunteerDashboard(response.data);
        } catch (error) {
            byId('voluntariadoDetailLoading').hidden = true;
            byId('voluntariadoDetailErrorMessage').textContent = error.message;
            byId('voluntariadoDetailError').hidden = false;
        }
    }

    function renderUsers() {
        const users = cache.users;
        byId('userCards').innerHTML = users.map(usuario => {
            const meta = `<span class="status-pill status-${escapeHtml(usuario.estado)}">${escapeHtml(usuario.estado)}</span><span>${escapeHtml(usuario.rol)}</span>`;
            const verification = usuario.rol === 'organizador' && usuario.organizadorVerificado
                ? '<span>Verificado</span>'
                : '';
            return card({
                title: usuario.nombre,
                description: `${usuario.correo}${usuario.fechaRegistro ? ` · Registro: ${new Date(usuario.fechaRegistro).toLocaleDateString('es-MX')}` : ''}`,
                meta: `${meta}${verification}`,
                id: usuario.id,
                actions: action('Editar', 'edit-user', usuario.id)
                    + action('Eliminar', 'delete-user', usuario.id, 'danger'),
            });
        }).join('');
        showResourceState('users', users.length ? 'ready' : 'empty');
    }

    async function loadResource(resource) {
        showResourceState(resource, 'loading');
        try {
            if (resource === 'articles') {
                const response = await dashboardApi.listArticles();
                cache.articles = response.data;
                if (user.rol === 'admin') {
                    const pending = await dashboardApi.listPendingArticles();
                    cache.pending = pending.data;
                    const pendingIds = new Set(cache.pending.map(article => article.id));
                    cache.articles = cache.articles.filter(article => !pendingIds.has(article.id)).concat(cache.pending);
                }
                renderArticles(user);
            } else if (resource === 'places') {
                cache.places = (await dashboardApi.listPlaces(user.rol)).data;
                renderPlaces(user);
            } else if (resource === 'flora') {
                cache.flora = (await dashboardApi.listFlora(user.rol)).data;
                renderFlora(user);
            } else if (resource === 'voluntariados') {
                cache.voluntariados = (await dashboardApi.listVoluntariados()).data;
                renderVoluntariados();
            } else if (resource === 'users') {
                cache.users = (await dashboardApi.listUsers()).data;
                renderUsers();
            }
        } catch (error) {
            showResourceState(resource, 'error');
            console.error(`No se pudo cargar ${resource}.`, error);
        }
    }

    async function loadNotifications(showList = false) {
        try {
            const response = showList
                ? await dashboardApi.allNotifications()
                : await dashboardApi.unreadNotifications();
            const unread = showList ? response.data.filter(item => !item.leida) : response.data;
            byId('notificationBadge').textContent = String(unread.length);
            byId('notificationBadge').hidden = unread.length === 0;
            byId('notificationError').hidden = true;
            cache.notifications = showList ? response.data : unread;

            if (showList) {
                byId('notificationList').innerHTML = response.data.length
                    ? response.data.map(notification => `<article class="notification-item ${notification.leida ? '' : 'is-unread'}">
                        <div><strong>${escapeHtml(notification.titulo)}</strong><p>${escapeHtml(notification.mensaje)}</p></div>
                        ${notification.leida ? '' : action('Marcar leída', 'read-notification', notification._id || notification.id)}
                    </article>`).join('')
                    : '<p class="dashboard-empty">No tienes notificaciones.</p>';
            }
        } catch (error) {
            byId('notificationError').hidden = false;
            console.error('No se pudieron cargar las notificaciones.', error);
        }
    }

    const formNames = {
        article: 'articleForm',
        place: 'placeForm',
        flora: 'floraForm',
        voluntariado: 'voluntariadoForm',
        user: 'userForm',
    };
    let placeMap = null;
    let placePreviewMaps = new Map();
    let placeMarker = null;
    let geocodeController = null;
    let geocodeRequest = 0;
    let lastGeocodeRequestAt = 0;

    function initializePlacePreviews() {
        root.querySelectorAll('[data-place-preview]').forEach(element => {
            const latitude = Number(element.dataset.lat);
            const longitude = Number(element.dataset.lng);
            const map = L.map(element, {
                minZoom: 7,
                maxBounds: L.latLngBounds(bajaCaliforniaSurBounds).pad(0.08),
                maxBoundsViscosity: 1,
                zoomControl: false,
                attributionControl: true,
                dragging: false,
                scrollWheelZoom: false,
                doubleClickZoom: false,
                boxZoom: false,
                keyboard: false,
                touchZoom: false,
            }).setView([latitude, longitude], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }).addTo(map);
            L.circleMarker([latitude, longitude], {
                radius: 7,
                color: '#fff',
                weight: 2,
                fillColor: '#164f3d',
                fillOpacity: 1,
            }).addTo(map);
            placePreviewMaps.set(element, map);
        });
    }

    function waitForGeocodeSlot(signal, delay) {
        if (delay <= 0) return Promise.resolve();

        return new Promise((resolve, reject) => {
            const timeout = window.setTimeout(() => {
                signal.removeEventListener('abort', onAbort);
                resolve();
            }, delay);
            const onAbort = () => {
                window.clearTimeout(timeout);
                reject(new DOMException('La consulta fue cancelada.', 'AbortError'));
            };
            signal.addEventListener('abort', onAbort, { once: true });
        });
    }

    function setPlaceMarker(latitude, longitude) {
        const point = [latitude, longitude];
        if (placeMarker) {
            placeMarker.setLatLng(point);
            return;
        }

        placeMarker = L.circleMarker(point, {
            radius: 8,
            color: '#fff',
            weight: 3,
            fillColor: '#164f3d',
            fillOpacity: 1,
        }).addTo(placeMap);
    }

    function placeTypeFromGeocode(result) {
        const category = result.category || result.class || '';
        const type = result.type || '';
        const tags = result.extratags || {};
        const normalized = `${category} ${type} ${tags.natural || ''} ${tags.leisure || ''} ${tags.boundary || ''}`.toLowerCase();

        if (/\bbeach\b|beach_resort/.test(normalized)) return 'playa';
        if (/nature_reserve|national_park|protected_area|biosphere_reserve/.test(normalized)) return 'reserva';
        if (/\bpark\b/.test(normalized)) return 'parque';
        if (/\b(place|building)\b|city|town|village|suburb|residential/.test(normalized)) return 'zona_urbana';
        return 'otro';
    }

    async function populatePlaceFromMap(latitude, longitude) {
        const form = byId('placeForm');
        const status = byId('placeMapStatus');
        form.elements.latitud.value = latitude.toFixed(6);
        form.elements.longitud.value = longitude.toFixed(6);
        form.elements.nombre.value = '';
        form.elements.municipio.value = '';
        form.elements.direccion.value = '';
        form.elements.tipo.value = 'otro';
        status.classList.remove('is-error');
        status.textContent = 'Buscando el nombre y los datos de esta ubicación…';

        geocodeController?.abort();
        geocodeController = new AbortController();
        const requestId = ++geocodeRequest;
        const params = new URLSearchParams({
            lat: String(latitude),
            lon: String(longitude),
            format: 'jsonv2',
            addressdetails: '1',
            namedetails: '1',
            extratags: '1',
            'accept-language': 'es',
        });

        try {
            const signal = geocodeController.signal;
            await waitForGeocodeSlot(signal, 1000 - (Date.now() - lastGeocodeRequestAt));
            if (requestId !== geocodeRequest) return;
            lastGeocodeRequestAt = Date.now();

            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?${params}`, {
                headers: { Accept: 'application/json' },
                signal,
            });
            if (!response.ok) throw new Error(`El servicio de ubicación respondió con ${response.status}.`);

            const result = await response.json();
            if (requestId !== geocodeRequest) return;
            if (!result.address) throw new Error('No se encontraron datos para este punto.');

            const address = result.address;
            const municipality = address.municipality
                || address.city
                || address.town
                || address.village
                || address.county
                || address.district
                || address.state_district
                || '';
            const name = result.name
                || result.namedetails?.name
                || address.amenity
                || address.road
                || address.neighbourhood
                || address.suburb
                || (municipality ? `Lugar en ${municipality}` : '');
            const streetAddress = [
                address.house_number,
                address.road,
                address.neighbourhood || address.suburb,
            ].filter(Boolean).join(', ');

            form.elements.nombre.value = name.slice(0, 150);
            form.elements.municipio.value = municipality.slice(0, 100);
            form.elements.direccion.value = (streetAddress || result.display_name || '').slice(0, 255);
            form.elements.tipo.value = placeTypeFromGeocode(result);

            if (municipality && name) {
                status.textContent = 'Datos completados. Puedes revisar o corregir los campos antes de guardar.';
            } else {
                status.classList.add('is-error');
                status.textContent = 'No se pudo identificar el nombre o municipio de este punto. Completa los campos marcados antes de guardar.';
            }
        } catch (error) {
            if (error.name === 'AbortError' || requestId !== geocodeRequest) return;
            console.error('No se pudieron consultar los datos de la ubicación seleccionada.', error);
            status.classList.add('is-error');
            status.textContent = 'No se pudieron obtener los datos del punto. Las coordenadas se conservaron; completa el nombre y municipio manualmente.';
        }
    }

    function initializePlaceMap(record = null) {
        const center = [24.2, -110.3];
        const recordHasCoordinates = record
            && record.latitud !== null && record.latitud !== ''
            && record.longitud !== null && record.longitud !== ''
            && isInsideBajaCaliforniaSur(Number(record.latitud), Number(record.longitud));
        const coordinates = recordHasCoordinates
            ? [Number(record.latitud), Number(record.longitud)]
            : null;

        if (!placeMap) {
            placeMap = L.map('placeMap', {
                minZoom: 7,
                maxBounds: L.latLngBounds(bajaCaliforniaSurBounds).pad(0.08),
                maxBoundsViscosity: 1,
            }).setView(center, 7);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }).addTo(placeMap);
            placeMap.on('click', event => {
                if (!isInsideBajaCaliforniaSur(event.latlng.lat, event.latlng.lng)) {
                    byId('placeMapStatus').classList.add('is-error');
                    byId('placeMapStatus').textContent = 'Selecciona un punto dentro de Baja California Sur, México.';
                    return;
                }
                setPlaceMarker(event.latlng.lat, event.latlng.lng);
                populatePlaceFromMap(event.latlng.lat, event.latlng.lng);
            });
        }

        if (coordinates) {
            setPlaceMarker(...coordinates);
            placeMap.setView(coordinates, Math.max(placeMap.getZoom(), 14));
            byId('placeMapStatus').textContent = 'Ubicación actual. Selecciona otro punto para actualizar sus datos.';
        } else {
            placeMarker?.remove();
            placeMarker = null;
            placeMap.setView(center, 7);
            const outsideBcs = record && !recordHasCoordinates && record.latitud && record.longitud;
            byId('placeMapStatus').classList.toggle('is-error', Boolean(outsideBcs));
            byId('placeMapStatus').textContent = outsideBcs
                ? 'La ubicación guardada está fuera de Baja California Sur. Selecciona un punto dentro del estado.'
                : 'Selecciona un punto dentro de Baja California Sur, México.';
        }

        window.requestAnimationFrame(() => placeMap.invalidateSize());
    }

    function closeForm(type) {
        const form = byId(formNames[type]);
        form.reset();
        form.elements.id.value = '';
        form.querySelectorAll('[data-location-picker]').forEach(picker => {
            picker.locationSelection = [];
            renderLocationPicker(picker);
        });
        form.querySelectorAll('[data-image-field]').forEach(field => {
            field.imageRecord = [];
            field.stagedFiles = [];
            field.removedImageIndices = new Set();
            field.querySelector('[data-image-input]').value = '';
            renderImagePreviews(field);
        });
        if (type === 'place') {
            geocodeController?.abort();
            geocodeRequest += 1;
        }
        form.hidden = true;
    }

    function renderLocationPicker(picker) {
        const selected = picker.locationSelection || [];
        const isMultiple = picker.dataset.mode === 'multiple';
        const selectedContainer = picker.querySelector('[data-location-selected]');
        const suggestions = picker.querySelector('[data-location-suggestions]');
        const values = picker.querySelector('[data-location-values]');
        const query = picker.querySelector('[data-location-query]');

        selectedContainer.replaceChildren();
        values.replaceChildren();
        selected.forEach(place => {
            const tag = document.createElement('span');
            tag.className = 'dashboard-location-tag';
            const name = document.createElement('span');
            name.textContent = `${place.nombre}${place.municipio ? ` · ${place.municipio}` : ''}`;
            tag.append(name);

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = isMultiple ? 'lugaresRelacionados[]' : 'lugarId';
            hidden.value = place.id;
            values.append(hidden);

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.dataset.removeLocation = place.id;
            remove.setAttribute('aria-label', `Quitar ${place.nombre}`);
            remove.textContent = '×';
            tag.append(remove);
            selectedContainer.append(tag);
        });
        if (!isMultiple) query.value = selected[0]?.nombre || '';
        suggestions.hidden = true;
        suggestions.replaceChildren();
        syncLocationSummary(picker);
    }

    function syncLocationSummary(picker) {
        const form = picker.closest('form');
        const selected = picker.locationSelection || [];
        const place = picker.dataset.mode === 'multiple'
            ? selected.find(item => item.latitud != null && item.longitud != null)
            : selected[0];
        const latitude = form.elements.namedItem('latitud');
        const longitude = form.elements.namedItem('longitud');
        const summary = picker.parentElement.querySelector('[data-location-summary]');

        if (latitude && longitude) {
            latitude.value = place?.latitud ?? '';
            longitude.value = place?.longitud ?? '';
        }
        if (!summary) return;
        if (!place) {
            summary.textContent = picker.dataset.mode === 'multiple'
                ? 'La ubicación del registro se tomará del primer lugar relacionado que tenga coordenadas.'
                : 'Selecciona un lugar registrado para usar su ubicación.';
        } else if (place.latitud == null || place.longitud == null) {
            summary.textContent = `${place.nombre} no tiene coordenadas registradas. Elige otro lugar para asociar una ubicación.`;
        } else {
            summary.textContent = `Ubicación seleccionada: ${place.nombre}${place.municipio ? ` · ${place.municipio}` : ''} (${Number(place.latitud).toFixed(5)}, ${Number(place.longitud).toFixed(5)}).`;
        }
    }

    function renderTokenField(field, showSuggestions = false) {
        const type = field.dataset.tokenField;
        const selected = field.tokenSelection || [];
        const query = field.querySelector('[data-token-query]');
        const selectedContainer = field.querySelector('[data-token-selected]');
        const values = field.querySelector('[data-token-values]');
        const suggestions = field.querySelector('[data-token-suggestions]');
        const normalizedQuery = query.value.trim().toLocaleLowerCase();
        const options = volunteerTokenOptions[type];

        selectedContainer.replaceChildren();
        values.replaceChildren();
        selected.forEach(value => {
            const option = options.find(item => item[1] === value);
            const chip = document.createElement('span');
            chip.className = 'dashboard-token-chip';
            const label = document.createElement('span');
            label.textContent = `${option?.[0] || '✦'} ${value}`;
            chip.append(label);
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = `${type}[]`;
            hidden.value = value;
            values.append(hidden);
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.dataset.removeToken = value;
            remove.setAttribute('aria-label', `Quitar ${value}`);
            remove.textContent = '×';
            chip.append(remove);
            selectedContainer.append(chip);
        });

        suggestions.replaceChildren();
        if (!showSuggestions) {
            suggestions.hidden = true;
            return;
        }

        const matches = options.filter(([, label]) =>
            !selected.includes(label)
            && (!normalizedQuery || label.toLocaleLowerCase().includes(normalizedQuery)),
        );
        matches.forEach(([icon, label]) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.addToken = label;
            button.innerHTML = `<span aria-hidden="true">${icon}</span><span>${escapeHtml(label)}</span>`;
            suggestions.append(button);
        });

        const exactMatch = options.some(([, label]) => label.toLocaleLowerCase() === normalizedQuery);
        const customValue = query.value.trim();
        if (customValue && !exactMatch && !selected.some(value => value.toLocaleLowerCase() === normalizedQuery)) {
            const addCustom = document.createElement('button');
            addCustom.type = 'button';
            addCustom.dataset.addToken = customValue;
            addCustom.innerHTML = `<span aria-hidden="true">＋</span><span>Agregar “${escapeHtml(customValue)}”</span>`;
            suggestions.append(addCustom);
        }
        if (!suggestions.childElementCount) {
            const empty = document.createElement('p');
            empty.textContent = 'No hay más opciones para agregar.';
            suggestions.append(empty);
        }
        suggestions.hidden = false;
    }

    function initializeTokenFields(form, record = null) {
        form.querySelectorAll('[data-token-field]').forEach(field => {
            const type = field.dataset.tokenField;
            const values = Array.isArray(record?.[type])
                ? record[type]
                : String(record?.[type] || '').split(',').map(value => value.trim()).filter(Boolean);
            field.tokenSelection = [...new Set(values)];
            field.querySelector('[data-token-query]').value = '';
            renderTokenField(field);
        });
    }

    function initializeLocationPicker(form, record) {
        form.querySelectorAll('[data-location-picker]').forEach(picker => {
            const multiple = picker.dataset.mode === 'multiple';
            const selectedIds = multiple
                ? (record?.lugaresRelacionados || []).map(String)
                : (record?.lugarId ? [String(record.lugarId)] : []);
            picker.locationSelection = selectedIds
                .map(id => cache.places.find(place => String(place.id) === id)
                    || { id, nombre: `Ubicación guardada (${id.slice(-6)})`, municipio: '' });
            renderLocationPicker(picker);
        });
    }

    function renderLocationSuggestions(picker) {
        const query = picker.querySelector('[data-location-query]').value.trim().toLocaleLowerCase();
        const suggestions = picker.querySelector('[data-location-suggestions]');
        const selectedIds = new Set((picker.locationSelection || []).map(place => String(place.id)));
        const matches = cache.places
            .filter(place => !selectedIds.has(String(place.id)))
            .filter(place => !query || `${place.nombre} ${place.municipio || ''} ${place.direccion || ''}`.toLocaleLowerCase().includes(query))
            .slice(0, 7);

        suggestions.replaceChildren();
        if (!matches.length) {
            const empty = document.createElement('p');
            empty.className = 'dashboard-location-no-results';
            empty.textContent = query ? 'No se encontraron lugares. Selecciona uno de los lugares registrados.' : 'No hay más lugares disponibles.';
            suggestions.append(empty);
        } else {
            matches.forEach(place => {
                const option = document.createElement('button');
                option.type = 'button';
                option.setAttribute('role', 'option');
                option.dataset.locationChoice = place.id;
                const name = document.createElement('strong');
                name.textContent = place.nombre;
                const details = document.createElement('small');
                details.textContent = [place.municipio, place.tipo].filter(Boolean).join(' · ');
                option.append(name, details);
                suggestions.append(option);
            });
        }
        suggestions.hidden = false;
    }

    function renderImagePreviews(field) {
        const previews = field.querySelector('[data-image-previews]');
        const removals = field.querySelector('[data-image-removals]');
        const input = field.querySelector('[data-image-input]');
        const existingImages = field.imageRecord || [];
        const removedImages = field.removedImageIndices || new Set();
        const files = field.stagedFiles || [...input.files];
        const maximum = Number(field.dataset.maxImages);

        previews.replaceChildren();
        removals.replaceChildren();
        existingImages.forEach((url, index) => {
            const item = document.createElement('div');
            item.className = 'dashboard-image-preview';
            item.dataset.imageIndex = String(index);
            if (removedImages.has(index)) item.dataset.removed = 'true';
            const image = document.createElement('img');
            image.src = url;
            image.alt = `Imagen guardada ${index + 1}`;
            item.append(image);
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.removeExistingImage = String(index);
            button.textContent = 'Quitar';
            item.append(button);
            previews.append(item);
        });
        files.forEach((file, index) => {
            const item = document.createElement('div');
            item.className = 'dashboard-image-preview is-new';
            item.dataset.fileIndex = String(index);
            const image = document.createElement('img');
            image.src = URL.createObjectURL(file);
            image.alt = `Vista previa de ${file.name}`;
            image.onload = () => URL.revokeObjectURL(image.src);
            item.append(image);
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.removeNewImage = String(index);
            button.textContent = 'Quitar';
            item.append(button);
            previews.append(item);
        });
        existingImages.forEach((_, index) => {
            const item = previews.querySelector(`[data-image-index="${index}"]`);
            if (removedImages.has(index)) {
                const removed = document.createElement('input');
                removed.type = 'hidden';
                removed.name = field.dataset.maxImages === '1' ? 'imagenEliminar' : 'imagenesEliminar[]';
                removed.value = String(index);
                removals.append(removed);
            }
        });
        const activeExistingCount = existingImages.length - removedImages.size;
        field.querySelector('.dashboard-image-help').textContent =
            `${activeExistingCount + files.length} de ${maximum} imágenes. Quita una actual para reemplazarla o agrega nuevas.`;
    }

    function initializeImagePreviews(form, record) {
        form.querySelectorAll('[data-image-field]').forEach(field => {
            const input = field.querySelector('[data-image-input]');
            const recordImages = Array.isArray(record?.imagenes) ? record.imagenes : [];
            const legacyImage = record?.imagen ? [record.imagen] : [];
            field.imageRecord = recordImages.length ? recordImages : legacyImage;
            field.stagedFiles = [];
            field.removedImageIndices = new Set();
            input.value = '';
            renderImagePreviews(field);
        });
    }

    function syncChoiceFields(form, record) {
        const typeOptions = [...form.querySelectorAll('input[name="tipo"][type="radio"]')];
        typeOptions.forEach(option => {
            option.checked = option.value === (record?.tipo || 'flora');
            option.closest('.dashboard-choice-card').classList.toggle('is-selected', option.checked);
        });

        const conservation = record?.estadoConservacion || '';
        const conservationGroup = form.querySelector('[data-single-choice="estadoConservacion"]');
        const conservationOptions = [...conservationGroup.querySelectorAll('[data-option-value]')];
        const matchedConservation = conservationOptions.some(option => option.dataset.optionValue === conservation);
        conservationOptions.forEach(option => {
            option.classList.toggle('is-selected', option.dataset.optionValue === conservation);
        });
        form.querySelector('[data-choice-custom="estadoConservacion"]').value = matchedConservation ? '' : conservation;
        form.elements.estadoConservacion.value = conservation;

        const habitats = String(record?.habitat || '').split(/[,;]/).map(value => value.trim()).filter(Boolean);
        const habitatOptions = [...form.querySelectorAll('[data-multiple-choice="habitat"] input[type="checkbox"]')];
        habitatOptions.forEach(option => {
            option.checked = habitats.includes(option.value);
            option.closest('label').classList.toggle('is-selected', option.checked);
        });
        const knownHabitats = new Set(habitatOptions.map(option => option.value));
        form.querySelector('[data-choice-custom="habitat"]').value =
            habitats.filter(value => !knownHabitats.has(value)).join(', ');
        form.elements.habitat.value = habitats.join(', ');
    }

    function updateChoiceValue(form, name) {
        if (name === 'estadoConservacion') {
            const custom = form.querySelector('[data-choice-custom="estadoConservacion"]').value.trim();
            const selected = form.querySelector('[data-single-choice="estadoConservacion"] .is-selected');
            form.elements.estadoConservacion.value = custom || selected?.dataset.optionValue || '';
            return;
        }
        const selected = [...form.querySelectorAll('[data-multiple-choice="habitat"] input:checked')]
            .map(option => option.value);
        const custom = form.querySelector('[data-choice-custom="habitat"]').value
            .split(',')
            .map(value => value.trim())
            .filter(Boolean);
        form.elements.habitat.value = [...selected, ...custom].join(', ');
    }

    function openForm(type, record = null) {
        const form = byId(formNames[type]);
        form.reset();
        form.elements.id.value = '';
        form.hidden = false;
        const locationSelector = initializeLocationSelector(form);
        locationSelector?.refresh();
        form.elements.id.value = record?.id || '';
        if (type === 'flora') {
            form.elements.zonaAproximada.value = record?.zonaAproximada ? 'true' : '';
            byId('approximateLocationNotice').hidden = !record?.zonaAproximada || user.rol === 'admin';
        }
        root.querySelector(`[data-form-title="${type}"]`).textContent = record
            ? 'Editar registro'
            : type === 'article' ? 'Nuevo artículo'
                : type === 'place' ? 'Nuevo lugar'
                    : type === 'flora' ? 'Nuevo registro'
                        : type === 'voluntariado' ? 'Nuevo voluntariado'
                            : 'Nuevo usuario';
        if (type === 'user') {
            form.elements.password.required = !record;
            byId('userForm').querySelector('[data-user-password-help]').textContent = record
                ? '(déjala vacía para conservarla)'
                : '(mínimo 8 caracteres)';
        }

        if (!record) {
            initializeLocationPicker(form, null);
            initializeImagePreviews(form, null);
            if (type === 'voluntariado') initializeTokenFields(form);
            if (type === 'voluntariado') {
                renderOrganizerOptions('');
                initializeDatePickers(form);
                setVolunteerStatus(form, 'pendiente');
            }
            if (type === 'flora') syncChoiceFields(form, null);
            locationSelector?.setCoordinates(null, null);
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        for (const [name, value] of Object.entries(record)) {
            const field = form.elements.namedItem(name);
            const isFloraChoice = type === 'flora'
                && ['tipo', 'estadoConservacion', 'habitat', 'lugaresRelacionados'].includes(name);
            if (field && field.type !== 'file' && !isFloraChoice) {
                if (field.type === 'checkbox') {
                    field.checked = Boolean(value);
                } else {
                    field.value = value ?? '';
                }
            }
        }
        if (type === 'flora') {
            form.elements.latitud.value = record.ubicacion?.latitud ?? '';
            form.elements.longitud.value = record.ubicacion?.longitud ?? '';
            syncChoiceFields(form, record);
        }
        initializeLocationPicker(form, record);
        initializeImagePreviews(form, record);
        if (type === 'voluntariado') {
            initializeTokenFields(form, record);
            renderOrganizerOptions(record.organizadorId || '');
            initializeDatePickers(form);
            setVolunteerStatus(form, record.estado || 'pendiente');
        }
        if (type === 'place') {
            locationSelector?.setCoordinates(record.latitud, record.longitud);
        }
        if (type === 'flora') {
            locationSelector?.setCoordinates(
                record.ubicacion?.latitud,
                record.ubicacion?.longitud,
            );
        }
        if (type === 'place') initializePlaceMap(record);
        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function formPayload(type, form) {
        const payload = new FormData();
        for (const [name, value] of new FormData(form).entries()) {
            if (['id', 'zonaAproximada', 'requisitos', 'materiales'].includes(name)) continue;
            if (value instanceof File && !value.name) continue;
            payload.append(name, value);
        }
        if (type === 'user' && !form.elements.password.value) {
            payload.delete('password');
        }
        if (type === 'user') {
            payload.set('organizadorVerificado', form.elements.organizadorVerificado.checked ? '1' : '0');
        }
        if (type === 'place') {
            payload.delete('imagenEliminar');
            if (form.querySelector('[data-image-removals] input[name="imagenEliminar"]')) {
                payload.append('imagenEliminar', '1');
            }
        }
        if (type === 'flora') {
            const locationPicker = form.querySelector('[data-location-picker][data-mode="multiple"]');
            if (!locationPicker.locationSelection.length) {
                payload.append('lugaresRelacionados[]', '');
            }
            const lat = form.elements.latitud.value;
            const lng = form.elements.longitud.value;
            const isExistingRecord = Boolean(form.elements.id.value);
            const locationIsApproximate = form.elements.zonaAproximada.value === 'true';
            payload.delete('latitud');
            payload.delete('longitud');
            if (!(isExistingRecord && locationIsApproximate)) {
                payload.append('ubicacion[latitud]', lat);
                payload.append('ubicacion[longitud]', lng);
            }
            if (!form.elements.estadoConservacion.value) payload.set('estadoConservacion', '');
            if (!form.elements.nombreCientifico.value) payload.set('nombreCientifico', '');
            if (!form.elements.habitat.value) payload.set('habitat', '');
            if (!form.elements.fuenteInformacion.value) payload.set('fuenteInformacion', '');
        }
        return payload;
    }

    async function handleAction(button) {
        const id = button.dataset.id;
        const actionName = button.dataset.action;

        const record = [...cache.articles, ...cache.places, ...cache.flora, ...cache.voluntariados, ...cache.users]
            .find(item => String(item.id) === String(id));

        if (actionName.startsWith('edit-')) {
            openForm(actionName.slice(5), record);
            return;
        }

        if (['reject-article', 'reject-voluntariado'].includes(actionName)) {
            rejectArticleId = id;
            rejectResource = actionName === 'reject-voluntariado' ? 'voluntariado' : 'article';
            byId('rejectReason').value = '';
            byId('rejectDialog').showModal();
            return;
        }

        const confirmationActions = [
            'publish-article',
            'unpublish-article',
            'publish-place',
            'archive-place',
            'publish-flora',
            'archive-flora',
            'delete-article',
            'delete-voluntariado',
            'delete-user',
        ];

        if (confirmationActions.includes(actionName)) {
            openActionConfirmation(actionName, id);
            return;
        }

        await executeAction(actionName, id, button);
    }
    async function executeAction(actionName, id, button = null) {
        if (button) button.disabled = true;

        try {
            switch (actionName) {
                case 'approve-article':
                    await dashboardApi.approveArticle(id);
                    break;

                case 'publish-article':
                    await dashboardApi.publishArticle(id);
                    break;

                case 'unpublish-article':
                    await dashboardApi.unpublishArticle(id);
                    break;

                case 'delete-article':
                    await dashboardApi.deleteDraft(id);
                    break;

                case 'submit-article':
                    await dashboardApi.submitArticle(id);
                    break;

                case 'archive-place':
                    await dashboardApi.archivePlace(id);
                    break;

                case 'publish-place':
                    await dashboardApi.publishPlace(id);
                    break;

                case 'archive-flora':
                    await dashboardApi.archiveFlora(id);
                    break;

                case 'publish-flora':
                    await dashboardApi.publishFlora(id);
                    break;

                case 'delete-voluntariado':
                    await dashboardApi.deleteVoluntariado(id);
                    break;

                case 'delete-user':
                    await dashboardApi.deleteUser(id);
                    break;

                case 'approve-voluntariado':
                    await dashboardApi.approveVoluntariado(id);
                    break;

                case 'read-notification':
                    await dashboardApi.markNotificationRead(id);
                    break;

                default:
                    return false;
            }

            showNotice('La acción se completó correctamente.');

            await refreshCurrentSection();

            if (actionName === 'read-notification') {
                await loadNotifications(true);
            }

            return true;

        } catch (error) {
            console.error(`Error al ejecutar ${actionName}:`, error);
            showNotice(error.message, 'error');
            return false;

        } finally {
            if (button) button.disabled = false;
        }
    }
    async function refreshCurrentSection() {
        await loadResource(activeSection);
    }

    let activeSection = 'articles';
    let user;
    let openVolunteerId = null;
    let rejectArticleId = null;
    let rejectResource = 'article';
    let pendingAction = null;
    function openActionConfirmation(actionName, id) {
    const configurations = {
            'publish-article': {
                title: 'Publicar artículo',
                message: 'El artículo estará disponible para el público en el catálogo de Regenera BCS.',
                button: 'Publicar artículo',
                icon: '✓',
                style: 'is-publish',
            },
            'unpublish-article': {
                title: 'Despublicar artículo',
                message: 'El artículo dejará de estar disponible en el catálogo público y volverá al estado de borrador.',
                button: 'Despublicar',
                icon: '!',
                style: 'is-danger',
            },
            'publish-place': {
                title: 'Publicar lugar',
                message: 'Este lugar volverá a estar activo y aparecerá nuevamente en el catálogo público.',
                button: 'Publicar lugar',
                icon: '✓',
                style: 'is-publish',
            },
            'archive-place': {
                title: 'Archivar lugar',
                message: 'El lugar dejará de aparecer en el catálogo público. Podrás publicarlo nuevamente después.',
                button: 'Archivar lugar',
                icon: '!',
                style: 'is-danger',
            },
            'publish-flora': {
                title: 'Publicar registro',
                message: 'Este registro de flora o fauna volverá a estar disponible públicamente.',
                button: 'Publicar registro',
                icon: '✓',
                style: 'is-publish',
            },
            'archive-flora': {
                title: 'Archivar registro',
                message: 'Este registro dejará de aparecer en el catálogo público. Podrás publicarlo nuevamente después.',
                button: 'Archivar registro',
                icon: '!',
                style: 'is-danger',
            },
            'delete-article': {
                title: 'Eliminar borrador',
                message: 'Esta acción eliminará permanentemente el borrador. No podrás recuperarlo.',
                button: 'Eliminar borrador',
                icon: '!',
                style: 'is-danger',
            },
            'delete-voluntariado': {
                title: 'Eliminar voluntariado',
                message: 'Esta acción eliminará permanentemente el voluntariado y no podrás recuperarlo.',
                button: 'Eliminar voluntariado',
                icon: '!',
                style: 'is-danger',
            },
            'delete-user': {
                title: 'Eliminar usuario',
                message: 'Esta acción eliminará permanentemente al usuario y no podrás recuperarlo.',
                button: 'Eliminar usuario',
                icon: '!',
                style: 'is-danger',
            },
        };

        const config = configurations[actionName];

        if (!config) return;

        pendingAction = { actionName, id };

        byId('actionConfirmTitle').textContent = config.title;
        byId('actionConfirmMessage').textContent = config.message;
        byId('actionConfirmSubmit').textContent = config.button;
        byId('actionConfirmIcon').textContent = config.icon;

        const submit = byId('actionConfirmSubmit');
        submit.classList.remove('is-danger', 'is-publish');
        submit.classList.add(config.style);

        byId('actionConfirmDialog').showModal();
    }

    root.querySelectorAll('[data-section]').forEach(tab => {
        tab.addEventListener('click', async () => {
            if (openVolunteerId) setVolunteerListVisible(true);
            activeSection = tab.dataset.section;
            root.querySelectorAll('.dashboard-tab').forEach(item => item.classList.toggle('is-active', item === tab));
            root.querySelectorAll('.dashboard-section').forEach(section => {
                const selected = section.id === `section-${activeSection}`;
                section.hidden = !selected;
                section.classList.toggle('is-active', selected);
            });
            await loadResource(activeSection);
        });
    });

    root.addEventListener('input', event => {
        const query = event.target.closest('[data-location-query]');
        if (query) renderLocationSuggestions(query.closest('[data-location-picker]'));

        const tokenQuery = event.target.closest('[data-token-query]');
        if (tokenQuery) renderTokenField(tokenQuery.closest('[data-token-field]'), true);

        const customChoice = event.target.closest('[data-choice-custom]');
        if (customChoice) {
            const form = customChoice.closest('form');
            updateChoiceValue(form, customChoice.dataset.choiceCustom);
            if (customChoice.dataset.choiceCustom === 'estadoConservacion') {
                form.querySelectorAll('[data-single-choice="estadoConservacion"] [data-option-value]')
                    .forEach(option => option.classList.remove('is-selected'));
            }
        }

        if (event.target.closest('[data-participant-search]')) applyParticipantFilters();
    });

    root.addEventListener('focusin', event => {
        const query = event.target.closest('[data-location-query]');
        if (query) renderLocationSuggestions(query.closest('[data-location-picker]'));

        const tokenQuery = event.target.closest('[data-token-query]');
        if (tokenQuery) renderTokenField(tokenQuery.closest('[data-token-field]'), true);
    });

    document.addEventListener('pointerdown', event => {
        root.querySelectorAll('[data-location-suggestions], [data-token-suggestions]').forEach(suggestions => {
            const picker = suggestions.closest('[data-location-picker], [data-token-field]');
            if (!picker?.contains(event.target)) suggestions.hidden = true;
        });
        root.querySelectorAll('[data-date-popover]').forEach(popover => {
            if (!popover.closest('[data-date-picker]')?.contains(event.target)) popover.hidden = true;
        });
    });

    root.addEventListener('keydown', event => {
        const query = event.target.closest('[data-token-query]');
        if (!query) return;
        const field = query.closest('[data-token-field]');
        const options = field.querySelectorAll('[data-token-suggestions] button');
        if (event.key === 'Escape') {
            field.querySelector('[data-token-suggestions]').hidden = true;
        } else if (event.key === 'Enter' && query.value.trim()) {
            event.preventDefault();
            if (options.length) options[0].click();
        } else if (event.key === 'Backspace' && !query.value && field.tokenSelection.length) {
            field.tokenSelection.pop();
            renderTokenField(field);
        }
    });

    root.addEventListener('change', event => {
        if (event.target.closest('[data-participant-inscription], [data-participant-attendance]')) {
            applyParticipantFilters();
            return;
        }
        const imageInput = event.target.closest('[data-image-input]');
        if (imageInput) {
            const field = imageInput.closest('[data-image-field]');
            const existingCount = field.imageRecord.length - field.removedImageIndices.size;
            const isSingleImage = field.dataset.maxImages === '1';
            const selectedFiles = isSingleImage
                ? [...imageInput.files]
                : [...field.stagedFiles, ...imageInput.files];
            let activeExistingCount = existingCount;
            if (isSingleImage && imageInput.files.length && existingCount) {
                field.removedImageIndices.add(0);
                activeExistingCount -= 1;
            }
            if (activeExistingCount + selectedFiles.length > Number(field.dataset.maxImages)) {
                showNotice(`Puedes conservar hasta ${field.dataset.maxImages} imágenes en este registro. Quita alguna imagen actual para agregar otras.`, 'error');
            } else {
                field.stagedFiles = selectedFiles;
            }
            const transfer = new DataTransfer();
            field.stagedFiles.forEach(file => transfer.items.add(file));
            imageInput.files = transfer.files;
            renderImagePreviews(field);
        }

        const habitatOption = event.target.closest('[data-multiple-choice="habitat"] input[type="checkbox"]');
        if (habitatOption) {
            habitatOption.closest('label').classList.toggle('is-selected', habitatOption.checked);
            updateChoiceValue(habitatOption.closest('form'), 'habitat');
        }

        const typeOption = event.target.closest('input[name="tipo"][type="radio"]');
        if (typeOption) {
            typeOption.form.querySelectorAll('.dashboard-choice-card')
                .forEach(card => card.classList.toggle('is-selected', card.contains(typeOption) && typeOption.checked));
        }
    });

    root.addEventListener('click', async event => {
        const dateTrigger = event.target.closest('[data-date-trigger]');
        if (dateTrigger) {
            const picker = dateTrigger.closest('[data-date-picker]');
            const popover = picker.querySelector('[data-date-popover]');
            const willOpen = popover.hidden;
            root.querySelectorAll('[data-date-popover]').forEach(item => { item.hidden = true; });
            if (willOpen) {
                picker.dateView = dateFromValue(picker.querySelector('input[type="hidden"]').value) || new Date();
                renderDatePicker(picker);
                popover.hidden = false;
            }
            return;
        }

        const dateStep = event.target.closest('[data-date-step]');
        if (dateStep) {
            const picker = dateStep.closest('[data-date-picker]');
            picker.dateView.setDate(1);
            picker.dateView.setMonth(picker.dateView.getMonth() + Number(dateStep.dataset.dateStep));
            renderDatePicker(picker);
            return;
        }

        const dateChoice = event.target.closest('[data-date-value]');
        if (dateChoice) {
            const picker = dateChoice.closest('[data-date-picker]');
            const form = picker.closest('form');
            picker.querySelector('input[type="hidden"]').value = dateChoice.dataset.dateValue;
            picker.dateView = dateFromValue(dateChoice.dataset.dateValue);
            picker.querySelector('[data-date-popover]').hidden = true;
            renderDatePicker(picker);
            form.querySelectorAll('[data-date-picker]').forEach(renderDatePicker);
            return;
        }

        const statusChoice = event.target.closest('[data-status-value]');
        if (statusChoice) {
            setVolunteerStatus(statusChoice.closest('form'), statusChoice.dataset.statusValue);
            return;
        }

        const capacityStep = event.target.closest('[data-capacity-step]');
        if (capacityStep) {
            const input = capacityStep.closest('.dashboard-capacity-control').querySelector('[name="cupo"]');
            const current = Number(input.value) || 1;
            input.value = String(Math.min(10000, Math.max(1, current + Number(capacityStep.dataset.capacityStep))));
            input.dispatchEvent(new Event('input', { bubbles: true }));
            return;
        }

        const tokenAdd = event.target.closest('[data-add-token]');
        if (tokenAdd) {
            const field = tokenAdd.closest('[data-token-field]');
            const value = tokenAdd.dataset.addToken.trim();
            if (value && !field.tokenSelection.some(item => item.toLocaleLowerCase() === value.toLocaleLowerCase())) {
                field.tokenSelection.push(value);
            }
            field.querySelector('[data-token-query]').value = '';
            renderTokenField(field, true);
            field.querySelector('[data-token-query]').focus();
            return;
        }

        const tokenRemove = event.target.closest('[data-remove-token]');
        if (tokenRemove) {
            const field = tokenRemove.closest('[data-token-field]');
            field.tokenSelection = field.tokenSelection.filter(value => value !== tokenRemove.dataset.removeToken);
            renderTokenField(field);
            return;
        }

        const locationChoice = event.target.closest('[data-location-choice]');
        if (locationChoice) {
            const picker = locationChoice.closest('[data-location-picker]');
            const place = cache.places.find(item => String(item.id) === String(locationChoice.dataset.locationChoice));
            if (place) {
                picker.locationSelection = picker.dataset.mode === 'multiple'
                    ? [...picker.locationSelection, place]
                    : [place];
                renderLocationPicker(picker);
                picker.querySelector('[data-location-query]').focus();
            }
            return;
        }

        const locationRemoval = event.target.closest('[data-remove-location]');
        if (locationRemoval) {
            const picker = locationRemoval.closest('[data-location-picker]');
            picker.locationSelection = picker.locationSelection
                .filter(place => String(place.id) !== String(locationRemoval.dataset.removeLocation));
            renderLocationPicker(picker);
            return;
        }

        const removeExistingImage = event.target.closest('[data-remove-existing-image]');
        if (removeExistingImage) {
            const field = removeExistingImage.closest('[data-image-field]');
            field.removedImageIndices.add(Number(removeExistingImage.dataset.removeExistingImage));
            renderImagePreviews(field);
            return;
        }

        const removeNewImage = event.target.closest('[data-remove-new-image]');
        if (removeNewImage) {
            const field = removeNewImage.closest('[data-image-field]');
            const input = field.querySelector('[data-image-input]');
            const transfer = new DataTransfer();
            field.stagedFiles.forEach((file, index) => {
                if (index !== Number(removeNewImage.dataset.removeNewImage)) transfer.items.add(file);
            });
            field.stagedFiles = [...transfer.files];
            input.files = transfer.files;
            renderImagePreviews(field);
            return;
        }

        const conservationOption = event.target.closest('[data-single-choice="estadoConservacion"] [data-option-value]');
        if (conservationOption) {
            const form = conservationOption.closest('form');
            form.querySelectorAll('[data-single-choice="estadoConservacion"] [data-option-value]')
                .forEach(option => option.classList.toggle('is-selected', option === conservationOption));
            form.querySelector('[data-choice-custom="estadoConservacion"]').value = '';
            updateChoiceValue(form, 'estadoConservacion');
            return;
        }

        const carouselButton = event.target.closest('[data-carousel-step]');
        if (carouselButton) {
            const carousel = carouselButton.closest('[data-place-carousel]');
            const slides = [...carousel.querySelectorAll('[data-place-slide]')];
            const currentIndex = slides.findIndex(slide => slide.classList.contains('is-active'));
            const nextIndex = (currentIndex + Number(carouselButton.dataset.carouselStep) + slides.length) % slides.length;
            slides.forEach((slide, index) => {
                const active = index === nextIndex;
                slide.classList.toggle('is-active', active);
                slide.setAttribute('aria-hidden', String(!active));
            });
            carousel.querySelector('[data-carousel-position]').textContent = `${nextIndex + 1} / ${slides.length}`;
            const preview = slides[nextIndex].querySelector('[data-place-preview]');
            const map = preview ? placePreviewMaps.get(preview) : null;
            if (map) window.requestAnimationFrame(() => map.invalidateSize());
            return;
        }

        const retry = event.target.closest('[data-retry]');
        if (event.target.closest('[data-volunteer-retry]')) {
            if (openVolunteerId) await showVolunteerDetail(openVolunteerId, false);
            return;
        }
        if (event.target.closest('[data-volunteer-back]')) {
            if (window.history.state?.volunteerDashboard) {
                window.history.back();
            } else {
                setVolunteerListVisible(true);
            }
            return;
        }
        if (event.target.closest('[data-copy-volunteer-token]')) {
            const token = byId(event.target.closest('[data-copy-volunteer-token]').dataset.copyTarget || 'volunteerQrToken')?.textContent || '';
            try {
                await navigator.clipboard.writeText(token);
                showNotice('Token copiado al portapapeles.');
            } catch (error) {
                console.error('No se pudo copiar el token QR.', error);
                showNotice('No se pudo copiar el token. Selecciónalo y cópialo manualmente.', 'error');
            }
            return;
        }
        if (event.target.closest('[data-download-volunteer-qr]')) {
            const canvas = byId('volunteerDetailQr');
            if (!canvas) {
                showNotice('El código QR todavía no está disponible para descargar.', 'error');
                return;
            }
            const download = document.createElement('a');
            download.href = canvas.toDataURL('image/png');
            download.download = `regenera-qr-${openVolunteerId}.png`;
            download.click();
            return;
        }
        if (retry?.dataset.retry === 'notifications') {
            await loadNotifications(true);
            return;
        }
        if (retry) {
            await loadResource(retry.dataset.retry);
            return;
        }
        const newButton = event.target.closest('[data-new]');
        if (newButton) {
            openForm(newButton.dataset.new);
            return;
        }
        const cancel = event.target.closest('[data-cancel]');
        if (cancel) {
            closeForm(cancel.dataset.cancel);
            return;
        }
        const actionButton = event.target.closest('[data-action]');
        if (actionButton) {
            if (actionButton.dataset.action === 'edit-voluntariado' && openVolunteerId) {
                setVolunteerListVisible(true);
            }
            await handleAction(actionButton);
            return;
        }
        const volunteerCard = event.target.closest('[data-open-volunteer]');
        if (volunteerCard && !event.target.closest('button, a, input, select, textarea, [data-action]')) {
            await showVolunteerDetail(volunteerCard.dataset.openVolunteer);
        }
    });

    root.addEventListener('keydown', async event => {
        const volunteerCard = event.target.closest('[data-open-volunteer]');
        if (!volunteerCard || event.target !== volunteerCard || !['Enter', ' '].includes(event.key)) return;
        event.preventDefault();
        await showVolunteerDetail(volunteerCard.dataset.openVolunteer);
    });

    window.addEventListener('popstate', () => {
        const id = new URLSearchParams(window.location.search).get('voluntariado');
        if (id) {
            showVolunteerDetail(id, false);
        } else if (openVolunteerId) {
            setVolunteerListVisible(true, false);
        }
    });

    for (const type of Object.keys(formNames)) {
        byId(formNames[type]).addEventListener('submit', async event => {
            event.preventDefault();
            const form = event.currentTarget;
            if (type === 'place') {
                const latitude = form.elements.latitud.value;
                const longitude = form.elements.longitud.value;
                if (!isInsideBajaCaliforniaSur(Number(latitude), Number(longitude))) {
                    byId('placeMapStatus').classList.add('is-error');
                    byId('placeMapStatus').textContent = 'Selecciona o captura una ubicación dentro de Baja California Sur, México.';
                    showNotice('La ubicación debe estar dentro de Baja California Sur, México.', 'error');
                    return;
                }
            }
            if (type === 'flora') {
                const latitude = form.elements.latitud.value;
                const longitude = form.elements.longitud.value;
                if ((latitude === '') !== (longitude === '')) {
                    showNotice('Captura latitud y longitud juntas.', 'error');
                    return;
                }
                if (latitude !== '' && !isInsideBajaCaliforniaSur(Number(latitude), Number(longitude))) {
                    showNotice('La ubicación debe estar dentro de Baja California Sur, México.', 'error');
                    return;
                }
            }
            if (type === 'voluntariado') {
                const startDate = form.elements.fechaInicio.value;
                const endDate = form.elements.fechaFin.value;
                if (!startDate || !endDate || endDate < startDate) {
                    showNotice('Selecciona fechas de inicio y fin válidas en los calendarios.', 'error');
                    return;
                }
                const picker = form.querySelector('[data-location-picker]');
                const selectedPlace = picker.locationSelection?.[0];
                if (!selectedPlace || picker.querySelector('[data-location-query]').value !== selectedPlace.nombre) {
                    showNotice('Selecciona una ubicación existente de las predicciones.', 'error');
                    return;
                }
                const latitude = form.elements.latitud.value;
                const longitude = form.elements.longitud.value;
                if ((latitude === '') !== (longitude === '')) {
                    showNotice('Captura latitud y longitud juntas.', 'error');
                    return;
                }
                if (latitude !== '' && !isInsideBajaCaliforniaSur(Number(latitude), Number(longitude))) {
                    showNotice('La ubicación debe estar dentro de Baja California Sur, México.', 'error');
                    return;
                }
                if (user.rol === 'admin' && !form.elements.organizadorId.value) {
                    showNotice('Selecciona un organizador de la lista.', 'error');
                    return;
                }
            }

            const id = form.elements.id.value;
            const submit = form.querySelector('[type="submit"]');
            submit.disabled = true;
            try {
                const payload = formPayload(type, form);
                if (type === 'article') await dashboardApi.saveArticle(id, payload);
                if (type === 'place') await dashboardApi.savePlace(id, payload);
                if (type === 'flora') await dashboardApi.saveFlora(id, payload);
                if (type === 'voluntariado') await dashboardApi.saveVoluntariado(id, payload);
                if (type === 'user') await dashboardApi.saveUser(id, Object.fromEntries(payload.entries()));
                closeForm(type);
                showNotice('Los cambios se guardaron correctamente.');
                await loadResource(
                    type === 'article' ? 'articles'
                        : type === 'place' ? 'places'
                            : type === 'flora' ? 'flora'
                                : type === 'voluntariado' ? 'voluntariados'
                                    : 'users',
                );
            } catch (error) {
                showNotice(error.message, 'error');
            } finally {
                submit.disabled = false;
            }
        });
    }

    byId('rejectForm').addEventListener('submit', async event => {
        event.preventDefault();
        const reason = byId('rejectReason').value.trim();
        if (!reason || !rejectArticleId) return;

        const submit = event.currentTarget.querySelector('[type="submit"]');
        submit.disabled = true;
        try {
            if (rejectResource === 'voluntariado') {
                await dashboardApi.rejectVoluntariado(rejectArticleId, reason);
            } else {
                await dashboardApi.rejectArticle(rejectArticleId, reason);
            }
            byId('rejectDialog').close();
            showNotice(rejectResource === 'voluntariado'
                ? 'Voluntariado rechazado; se notificó al organizador.'
                : 'Artículo rechazado; se notificó al autor.');
            await loadResource(rejectResource === 'voluntariado' ? 'voluntariados' : 'articles');
        } catch (error) {
            showNotice(error.message, 'error');
        } finally {
            submit.disabled = false;
        }
    });
    byId('cancelReject').addEventListener('click', () => byId('rejectDialog').close());
    byId('actionConfirmForm').addEventListener('submit', async event => {
        event.preventDefault();

        if (!pendingAction) return;

        const submit = byId('actionConfirmSubmit');
        submit.disabled = true;

        const { actionName, id } = pendingAction;

        try {
            const completed = await executeAction(actionName, id);

            if (completed) {
                byId('actionConfirmDialog').close();
                pendingAction = null;
            }
        } finally {
            submit.disabled = false;
        }
    });

    byId('cancelActionConfirm').addEventListener('click', () => {
        byId('actionConfirmDialog').close();
        pendingAction = null;
    });
    byId('notificationToggle').addEventListener('click', async () => {
        const popover = byId('notificationPopover');
        const open = popover.hidden;
        popover.hidden = !open;
        byId('notificationToggle').setAttribute('aria-expanded', String(open));
        if (open) await loadNotifications(true);
    });
    byId('markAllRead').addEventListener('click', async () => {
        try {
            await dashboardApi.markAllNotificationsRead();
            await loadNotifications(true);
            showNotice('Notificaciones marcadas como leídas.');
        } catch (error) {
            showNotice(error.message, 'error');
        }
    });

    async function start() {
        if (!sessionStorage.getItem('regenera_access_token')) {
            showAccessError('Inicia sesión para acceder al panel.');
            return;
        }

        try {
            const response = await dashboardApi.currentUser();
            user = response.usuario;
            if (user.rol !== routeRole || !['admin', 'organizador'].includes(user.rol)) {
                showAccessError('Tu cuenta no tiene acceso a este panel.');
                return;
            }

            byId('dashboardTitle').textContent = user.rol === 'admin' ? 'Panel de administración' : 'Panel de organizador';
            byId('dashboardWelcome').textContent = `Hola, ${user.nombre}. Administra el contenido de Regenera BCS.`;
            root.querySelectorAll('[data-admin-only]').forEach(element => {
                element.hidden = user.rol !== 'admin';
            });
            byId('dashboardContent').hidden = false;
            if (user.rol === 'admin') {
                try {
                    await loadOrganizers();
                } catch (error) {
                    console.error('No se pudo cargar la lista de organizadores.', error);
                    showNotice('No se pudo cargar la lista de organizadores. Recarga el panel para intentarlo de nuevo.', 'error');
                }
            }
            root.querySelectorAll('[data-new="article"], [data-new="place"], [data-new="flora"]').forEach(button => {
                button.hidden = !['admin', 'organizador'].includes(user.rol);
            });
            await loadResource('articles');
            await Promise.all([loadResource('places'), loadResource('flora')]);
            await loadResource('voluntariados');
            const volunteerId = new URLSearchParams(window.location.search).get('voluntariado');
            if (volunteerId) {
                activeSection = 'voluntariados';
                root.querySelectorAll('.dashboard-tab').forEach(tab => {
                    tab.classList.toggle('is-active', tab.dataset.section === activeSection);
                });
                root.querySelectorAll('.dashboard-section').forEach(section => {
                    const selected = section.id === `section-${activeSection}`;
                    section.hidden = !selected;
                    section.classList.toggle('is-active', selected);
                });
                await showVolunteerDetail(volunteerId, false);
                const record = cache.voluntariados.find(item => String(item.id) === String(volunteerId));
                if (new URLSearchParams(window.location.search).get('editar') === '1'
                    && record
                    && (user.rol === 'admin' || !record.iniciadoPorFecha)) {
                    openForm('voluntariado', record);
                }
            }
            if (user.rol === 'admin') await loadResource('users');
            await loadNotifications();
            window.setInterval(() => loadNotifications(), 60_000);
        } catch (error) {
            showAccessError(error.message || 'No se pudo validar tu sesión.');
        }
    }

    function showAccessError(message) {
        byId('dashboardContent').hidden = true;
        byId('dashboardAccessError').hidden = false;
        byId('dashboardAccessMessage').textContent = message;
    }

    start();
}
