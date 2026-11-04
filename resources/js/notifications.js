import axios from 'axios';

const drawer = document.querySelector('[data-notification-drawer]');
if (drawer) {
    const toggles = [...document.querySelectorAll('[data-notification-toggle]')];
    const list = drawer.querySelector('[data-notification-list]');
    const status = drawer.querySelector('[data-notification-status]');
    const badges = [...document.querySelectorAll('[data-notification-badge]')];
    let notifications = [];
    let filter = 'all';
    let lastFocused = null;

    const tokenHeaders = () => {
        const token = sessionStorage.getItem('regenera_access_token');
        return {
            Accept: 'application/json',
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
        };
    };

    const icon = {
        check: '✓', x: '!', calendar: '◷', bell: '♩', heart: '♥',
        'file-check': '✓', 'file-x': '!', shield: '◆', info: 'i',
    };

    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    }[character]));

    function updateBadge(count) {
        badges.forEach(badge => {
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.hidden = count < 1;
        });
    }

    function render() {
        const visible = filter === 'unread' ? notifications.filter(item => !item.leida) : notifications;
        if (!visible.length) {
            list.innerHTML = '<div class="notification-empty"><span aria-hidden="true">✓</span><p>¡Estás al día!<br>No tienes notificaciones pendientes.</p></div>';
            return;
        }
        list.innerHTML = visible.map(item => `
            <button type="button" class="notification-item ${item.leida ? '' : 'is-unread'} priority-${escapeHtml(item.prioridad)}"
                data-notification-id="${escapeHtml(item.id)}" ${item.url ? `data-notification-url="${escapeHtml(item.url)}"` : ''}>
                <span class="notification-item-icon icon-${escapeHtml(item.icono)}" aria-hidden="true">${icon[item.icono] ?? 'i'}</span>
                <span class="notification-item-content">
                    <strong>${escapeHtml(item.titulo)}</strong>
                    <span>${escapeHtml(item.mensaje)}</span>
                </span>
                ${item.leida ? '' : '<span class="notification-unread-dot" aria-label="No leída"></span>'}
            </button>
        `).join('');
    }

    async function load() {
        if (!sessionStorage.getItem('regenera_access_token')) {
            toggles.forEach(toggle => { toggle.hidden = true; });
            return;
        }
        try {
            const response = await axios.get('/api/notificaciones', { headers: tokenHeaders() });
            notifications = response.data.data ?? [];
            updateBadge(notifications.filter(item => !item.leida).length);
            render();
            status.textContent = '';
            toggles.forEach(toggle => { toggle.hidden = false; });
        } catch (error) {
            if (error.response?.status === 401) sessionStorage.removeItem('regenera_access_token');
            status.textContent = 'No pudimos cargar tus notificaciones. Intenta de nuevo.';
        }
    }

    function setOpen(open) {
        drawer.hidden = !open;
        document.querySelector('[data-notification-close].notification-drawer-backdrop').hidden = !open;
        toggles.forEach(toggle => toggle.setAttribute('aria-expanded', String(open)));
        document.body.classList.toggle('notification-drawer-open', open);
        if (open) {
            lastFocused = document.activeElement;
            drawer.focus();
            load();
        } else {
            lastFocused?.focus();
        }
    }

    toggles.forEach(toggle => toggle.addEventListener('click', () => setOpen(true)));
    document.querySelectorAll('[data-notification-close]').forEach(button => {
        button.addEventListener('click', () => setOpen(false));
    });
    drawer.querySelectorAll('[data-notification-filter]').forEach(button => {
        button.addEventListener('click', () => {
            filter = button.dataset.notificationFilter;
            drawer.querySelectorAll('[data-notification-filter]').forEach(item => item.classList.toggle('is-active', item === button));
            render();
        });
    });
    drawer.querySelector('[data-notification-mark-all]').addEventListener('click', async () => {
        try {
            await axios.patch('/api/notificaciones/marcar-todas-leidas', {}, { headers: tokenHeaders() });
            notifications = notifications.map(item => ({ ...item, leida: true }));
            updateBadge(0);
            render();
        } catch {
            status.textContent = 'No se pudieron marcar como leídas.';
        }
    });
    list.addEventListener('click', async event => {
        const item = event.target.closest('[data-notification-id]');
        if (!item) return;
        const notification = notifications.find(value => value.id === item.dataset.notificationId);
        try {
            await axios.patch(`/api/notificaciones/${encodeURIComponent(item.dataset.notificationId)}/leida`, {}, { headers: tokenHeaders() });
            if (notification) notification.leida = true;
            updateBadge(notifications.filter(value => !value.leida).length);
            if (notification?.url) window.location.assign(notification.url);
            else render();
        } catch {
            status.textContent = 'No se pudo actualizar la notificación.';
        }
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !drawer.hidden) setOpen(false);
    });
    load();
    window.setInterval(load, 60_000);
}
