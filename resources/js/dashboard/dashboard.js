import '../../css/dashboard.css';
import { dashboardApi } from './dashboard-api';

const root = document.getElementById('dashboardRoot');
if (root) initializeDashboard();

function initializeDashboard() {
    const routeRole = root.dataset.dashboardRole;
    const cache = { articles: [], places: [], flora: [], pending: [], notifications: [] };
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

    function card({ title, description, meta, id, actions = '' }) {
        return `<article class="dashboard-card" data-record-id="${escapeHtml(id)}">
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
                    actions += action('Despublicar', 'unpublish-article', article.id);
                }
                if (article.estado === 'borrador') {
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

        byId('placeCards').innerHTML = list.map(place => {
            const meta = `<span class="status-pill status-${escapeHtml(place.estado)}">${escapeHtml(place.estado)}</span><span>${escapeHtml(place.tipo)}</span><span>${escapeHtml(place.municipio)}</span>`;
            let actions = action('Editar', 'edit-place', place.id);
            if (user.rol === 'admin' && place.estado !== 'archivado') {
                actions += action('Archivar', 'archive-place', place.id, 'danger');
            }

            return card({
                title: place.nombre,
                description: place.descripcion || place.direccion || 'Sin descripción registrada.',
                meta,
                id: place.id,
                actions,
            });
        }).join('');
        showResourceState('places', list.length ? 'ready' : 'empty');
        root.querySelector('[data-place-heading]').textContent = user.rol === 'admin'
            ? 'Todos los lugares'
            : 'Mis lugares';
    }

    function renderFlora(user) {
        const list = user.rol === 'admin'
            ? cache.flora
            : cache.flora.filter(record => String(record.creadoPor) === String(user.id));

        byId('floraCards').innerHTML = list.map(record => {
            const meta = `<span class="status-pill status-${escapeHtml(record.estado)}">${escapeHtml(record.estado)}</span><span>${escapeHtml(record.tipo)}</span><span>${escapeHtml(record.estadoConservacion || 'Conservación sin especificar')}</span>`;
            let actions = action('Editar', 'edit-flora', record.id);
            if (user.rol === 'admin' && record.estado !== 'archivado') {
                actions += action('Archivar', 'archive-flora', record.id, 'danger');
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

    const formNames = { article: 'articleForm', place: 'placeForm', flora: 'floraForm' };
    function closeForm(type) {
        const form = byId(formNames[type]);
        form.reset();
        form.elements.id.value = '';
        form.hidden = true;
    }

    function openForm(type, record = null) {
        const form = byId(formNames[type]);
        form.hidden = false;
        form.elements.id.value = record?.id || '';
        if (type === 'flora') {
            form.elements.zonaAproximada.value = record?.zonaAproximada ? 'true' : '';
            byId('approximateLocationNotice').hidden = !record?.zonaAproximada || user.rol === 'admin';
        }
        root.querySelector(`[data-form-title="${type}"]`).textContent = record
            ? 'Editar registro'
            : type === 'article' ? 'Nuevo artículo' : type === 'place' ? 'Nuevo lugar' : 'Nuevo registro';

        if (!record) {
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }

        for (const [name, value] of Object.entries(record)) {
            if (form.elements.namedItem(name)) form.elements.namedItem(name).value = value ?? '';
        }
        if (type === 'flora') {
            form.elements.latitud.value = record.ubicacion?.latitud ?? '';
            form.elements.longitud.value = record.ubicacion?.longitud ?? '';
            form.elements.lugaresRelacionados.value = (record.lugaresRelacionados || []).join(', ');
            form.elements.imagenes.value = (record.imagenes || []).join(', ');
        }
        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function formPayload(type, form) {
        const values = Object.fromEntries(new FormData(form).entries());
        const isExistingRecord = Boolean(values.id);
        delete values.id;

        if (type === 'place') {
            values.latitud = values.latitud === '' ? null : Number(values.latitud);
            values.longitud = values.longitud === '' ? null : Number(values.longitud);
        } else if (type === 'flora') {
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
        const record = [...cache.articles, ...cache.places, ...cache.flora]
            .find(item => item.id === id);

        if (actionName.startsWith('edit-')) {
            openForm(actionName.slice(5), record);
            return;
        }
        if (actionName === 'reject-article') {
            rejectArticleId = id;
            byId('rejectReason').value = '';
            byId('rejectDialog').showModal();
            return;
        }

        const confirmMessage = actionName.startsWith('archive-')
            ? '¿Archivar este registro? Ya no aparecerá en el catálogo público.'
            : actionName === 'delete-article'
                ? '¿Eliminar este borrador? Esta acción no se puede deshacer.'
                : actionName === 'unpublish-article'
                    ? '¿Retirar este artículo de publicación?'
                    : null;
        if (confirmMessage && !window.confirm(confirmMessage)) return;

        button.disabled = true;
        try {
            if (actionName === 'approve-article') await dashboardApi.approveArticle(id);
            if (actionName === 'unpublish-article') await dashboardApi.unpublishArticle(id);
            if (actionName === 'delete-article') await dashboardApi.deleteDraft(id);
            if (actionName === 'submit-article') await dashboardApi.submitArticle(id);
            if (actionName === 'archive-place') await dashboardApi.archivePlace(id);
            if (actionName === 'archive-flora') await dashboardApi.archiveFlora(id);
            if (actionName === 'read-notification') await dashboardApi.markNotificationRead(id);
            showNotice('La acción se completó correctamente.');
            await refreshCurrentSection();
            if (actionName === 'read-notification') await loadNotifications(true);
        } catch (error) {
            showNotice(error.message, 'error');
        } finally {
            button.disabled = false;
        }
    }

    async function refreshCurrentSection() {
        await loadResource(activeSection);
    }

    let activeSection = 'articles';
    let user;
    let rejectArticleId = null;

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
            const id = form.elements.id.value;
            const submit = form.querySelector('[type="submit"]');
            submit.disabled = true;
            try {
                const payload = formPayload(type, form);
                if (type === 'article') await dashboardApi.saveArticle(id, payload);
                if (type === 'place') await dashboardApi.savePlace(id, payload);
                if (type === 'flora') await dashboardApi.saveFlora(id, payload);
                closeForm(type);
                showNotice('Los cambios se guardaron correctamente.');
                await loadResource(type === 'article' ? 'articles' : type === 'place' ? 'places' : 'flora');
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
            await dashboardApi.rejectArticle(rejectArticleId, reason);
            byId('rejectDialog').close();
            showNotice('Artículo rechazado; se notificó al autor.');
            await loadResource('articles');
        } catch (error) {
            showNotice(error.message, 'error');
        } finally {
            submit.disabled = false;
        }
    });
    byId('cancelReject').addEventListener('click', () => byId('rejectDialog').close());

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
            byId('dashboardContent').hidden = false;
            root.querySelectorAll('[data-new="article"], [data-new="place"], [data-new="flora"]').forEach(button => {
                button.hidden = !['admin', 'organizador'].includes(user.rol);
            });
            await loadResource('articles');
            await Promise.all([loadResource('places'), loadResource('flora')]);
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
