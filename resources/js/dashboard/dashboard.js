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

        byId('placeCards').innerHTML = list.map(place => {
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
            const field = form.elements.namedItem(name);
            if (field && field.type !== 'file') field.value = value ?? '';
        }
        if (type === 'flora') {
            form.elements.latitud.value = record.ubicacion?.latitud ?? '';
            form.elements.longitud.value = record.ubicacion?.longitud ?? '';
            form.elements.lugaresRelacionados.value = (record.lugaresRelacionados || []).join(', ');
        }
        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function formPayload(type, form) {
        const values = Object.fromEntries(new FormData(form).entries());
        const isExistingRecord = Boolean(values.id);
        delete values.id;

        if (type === 'article' || type === 'place' || type === 'flora') {
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

        const record = [...cache.articles, ...cache.places, ...cache.flora]
            .find(item => String(item.id) === String(id));

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

        const confirmationActions = [
            'publish-article',
            'unpublish-article',
            'publish-place',
            'archive-place',
            'publish-flora',
            'archive-flora',
            'delete-article',
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
