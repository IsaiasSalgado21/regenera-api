import '../../css/dashboard.css';
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
    const cache = { articles: [], places: [], flora: [], voluntariados: [], pending: [], notifications: [] };
    const byId = id => document.getElementById(id);
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

    function card({ title, description, meta, id, actions = '', media = '' }) {
        return `<article class="dashboard-card" data-record-id="${escapeHtml(id)}">
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
            const meta = `<span class="status-pill status-${escapeHtml(voluntariado.estado)}">${escapeHtml(voluntariado.estado)}</span><span>${escapeHtml(voluntariado.categoria)}</span><span>${escapeHtml(voluntariado.fechaInicio || '')}</span>`;
            let actions = '';
            if (user.rol === 'admin') {
                actions += action('Editar', 'edit-voluntariado', voluntariado.id);
                if (voluntariado.estado === 'pendiente') {
                    actions += action('Aprobar', 'approve-voluntariado', voluntariado.id, 'primary');
                    actions += action('Rechazar', 'reject-voluntariado', voluntariado.id, 'danger');
                }
                actions += action('Eliminar', 'delete-voluntariado', voluntariado.id, 'danger');
            } else if (['pendiente', 'rechazado'].includes(voluntariado.estado)) {
                actions += action('Editar y reenviar', 'edit-voluntariado', voluntariado.id);
                actions += action('Eliminar', 'delete-voluntariado', voluntariado.id, 'danger');
            }
            return card({
                title: voluntariado.titulo,
                description: voluntariado.descripcion,
                meta,
                id: voluntariado.id,
                actions,
            });
        }).join('');
        showResourceState('voluntariados', list.length ? 'ready' : 'empty');
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
        if (type === 'place') {
            geocodeController?.abort();
            geocodeRequest += 1;
        }
        form.hidden = true;
    }

    function openForm(type, record = null) {
        const form = byId(formNames[type]);
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
                        : 'Nuevo voluntariado';

        if (!record) {
            locationSelector?.setCoordinates(null, null);
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        for (const [name, value] of Object.entries(record)) {
            const field = form.elements.namedItem(name);
            if (field && field.type !== 'file') field.value = value ?? '';
        }
        if (type === 'flora') {
            form.elements.latitud.value = record.ubicacion?.latitud ?? '';
            form.elements.longitud.value = record.ubicacion?.longitud ?? '';
            form.elements.lugaresRelacionados.value = (record.lugaresRelacionados || []).join(', ');
        }
        if (type === 'place' || type === 'voluntariado') {
            locationSelector?.setCoordinates(record.latitud, record.longitud);
        }
        if (type === 'flora') {
            locationSelector?.setCoordinates(
                record.ubicacion?.latitud,
                record.ubicacion?.longitud,
            );
        }
        if (type === 'voluntariado') {
            form.elements.requisitos.value = (record.requisitos || []).join(', ');
            form.elements.materiales.value = (record.materiales || []).join(', ');
        }
        if (type === 'place') initializePlaceMap(record);
        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function formPayload(type, form) {
        const values = Object.fromEntries(new FormData(form).entries());
        const isExistingRecord = Boolean(values.id);
        delete values.id;

        if (type === 'article' || type === 'place' || type === 'flora' || type === 'voluntariado') {
            const payload = new FormData();
            for (const [name, value] of new FormData(form).entries()) {
                if (name === 'id' || name === 'zonaAproximada') continue;
                if (name === 'imagenes[]') {
                    if (value instanceof File && value.size > 0) payload.append(name, value);
                    continue;
                }
                if (name === 'lugaresRelacionados') {
                    value.split(',').map(item => item.trim()).filter(Boolean)
                        .forEach(item => payload.append('lugaresRelacionados[]', item));
                    continue;
                }
                if (type === 'voluntariado' && ['requisitos', 'materiales'].includes(name)) {
                    value.split(',').map(item => item.trim()).filter(Boolean)
                        .forEach(item => payload.append(`${name}[]`, item));
                    continue;
                }
                if (name === 'imagenes') continue;
                payload.append(name, value);
            }
            return payload;
        }

        if (type === 'flora') {
            const lat = values.latitud;
            const lng = values.longitud;
            const locationIsApproximate = values.zonaAproximada === 'true';
            delete values.zonaAproximada;
            delete values.latitud;
            delete values.longitud;
            if (!(isExistingRecord && locationIsApproximate)) {
                values.ubicacion = lat === '' && lng === ''
                    ? null
                    : { latitud: Number(lat), longitud: Number(lng) };
            } else {
                delete values.ubicacion;
            }
            values.lugaresRelacionados = values.lugaresRelacionados
                .split(',')
                .map(value => value.trim())
                .filter(Boolean);
            values.imagenes = values.imagenes.split(',').map(value => value.trim()).filter(Boolean);
            if (!values.estadoConservacion) values.estadoConservacion = null;
            if (!values.nombreCientifico) values.nombreCientifico = null;
            if (!values.habitat) values.habitat = null;
            if (!values.fuenteInformacion) values.fuenteInformacion = null;
        } else if (!values.imagen) {
            values.imagen = null;
        }

        return values;
    }

    async function handleAction(button) {
        const id = button.dataset.id;
        const actionName = button.dataset.action;

        const record = [...cache.articles, ...cache.places, ...cache.flora, ...cache.voluntariados]
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

    root.addEventListener('click', async event => {
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
        if (actionButton) await handleAction(actionButton);
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

            const id = form.elements.id.value;
            const submit = form.querySelector('[type="submit"]');
            submit.disabled = true;
            try {
                const payload = formPayload(type, form);
                if (type === 'article') await dashboardApi.saveArticle(id, payload);
                if (type === 'place') await dashboardApi.savePlace(id, payload);
                if (type === 'flora') await dashboardApi.saveFlora(id, payload);
                if (type === 'voluntariado') await dashboardApi.saveVoluntariado(id, payload);
                closeForm(type);
                showNotice('Los cambios se guardaron correctamente.');
                await loadResource(
                    type === 'article' ? 'articles'
                        : type === 'place' ? 'places'
                            : type === 'flora' ? 'flora'
                                : 'voluntariados',
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
            root.querySelectorAll('[data-new="article"], [data-new="place"], [data-new="flora"]').forEach(button => {
                button.hidden = !['admin', 'organizador'].includes(user.rol);
            });
            await loadResource('articles');
            await Promise.all([loadResource('places'), loadResource('flora')]);
            await loadResource('voluntariados');
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
