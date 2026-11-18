import axios from 'axios';
import { apiUrl } from './api-url';
import { loadForCompleteProfile } from './notification-access';
import { playNotificationSound } from './notification-sound';

const drawer = document.querySelector('[data-notification-drawer]');
if (drawer) {
    const toggles = [...document.querySelectorAll('[data-notification-toggle]')];
    const mobileMenu = document.getElementById('mobileMenu');
    const menuButton = document.getElementById('menuButton');
    const list = drawer.querySelector('[data-notification-list]');
    const status = drawer.querySelector('[data-notification-status]');
    const recipientFilters = drawer.querySelector('[data-notification-recipient-filters]');
    const badges = [...document.querySelectorAll('[data-notification-badge]')];
    let notifications = [];
    let filter = 'all';
    let recipientFilter = 'mine';
    let isAdmin = false;
    let lastFocused = null;
    let hasLoadedNotifications = false;
    const seenNotificationIds = new Set();
    let toastContainer = null;

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
    const formatDate = value => value ? new Intl.DateTimeFormat('es-MX', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value)) : '';

    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    }[character]));
    const symbols = new Map([
        ['check', 'confirmado'], ['file-check', 'confirmado'],
        ['x', 'alerta'], ['file-x', 'alerta'], ['calendar', 'calendario'], ['shield', 'proteccion'],
    ]);
    const symbol = (name, fallback) => document.body.dataset.symbolsBase && name
        ? `<img class="ui-symbol" src="${escapeHtml(document.body.dataset.symbolsBase.replace(/\/+$/, ''))}/${name}.png" alt="" aria-hidden="true" width="20" height="20">`
        : fallback;

    function updateBadge(count) {
        badges.forEach(badge => {
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.hidden = count < 1;
        });
    }

    function positionToasts() {
        if (!toastContainer) return;
        const bell = toggles.find(toggle => !toggle.hidden && toggle.getBoundingClientRect().width > 0);
        if (!bell) return;
        const bounds = bell.getBoundingClientRect();
        toastContainer.style.top = `${Math.ceil(bounds.bottom + 8)}px`;
        toastContainer.style.right = `${Math.max(8, Math.ceil(window.innerWidth - bounds.right))}px`;
    }

    function showToast(notification) {
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'notification-toast-container';
            toastContainer.setAttribute('aria-live', 'polite');
            toastContainer.setAttribute('aria-relevant', 'additions');
            document.body.append(toastContainer);
        }

        const toast = document.createElement('article');
        toast.className = 'notification-toast';
        toast.setAttribute('role', 'status');

        const heading = document.createElement('strong');
        heading.className = 'notification-toast-title';
        heading.textContent = notification.titulo || 'Nueva notificación';
        toast.append(heading);

        const message = document.createElement('p');
        message.className = 'notification-toast-message';
        message.textContent = notification.mensaje || '';
        toast.append(message);

        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'notification-toast-close';
        close.setAttribute('aria-label', 'Cerrar notificación');
        close.textContent = '×';
        toast.append(close);

        if (notification.url) {
            const target = new URL(notification.url, window.location.origin);
            if (target.origin === window.location.origin) {
                const link = document.createElement('a');
                link.className = 'notification-toast-link';
                link.href = target.href;
                link.textContent = 'Ver notificación';
                toast.append(link);
            }
        }

        const removeToast = () => {
            toast.remove();
            if (toastContainer && !toastContainer.childElementCount) {
                toastContainer.remove();
                toastContainer = null;
            }
        };
        let dismissTimer = window.setTimeout(removeToast, 6_000);
        const pauseDismissal = () => window.clearTimeout(dismissTimer);
        const resumeDismissal = () => {
            window.clearTimeout(dismissTimer);
            dismissTimer = window.setTimeout(removeToast, 6_000);
        };
        toast.addEventListener('mouseenter', pauseDismissal);
        toast.addEventListener('mouseleave', resumeDismissal);
        toast.addEventListener('focusin', pauseDismissal);
        toast.addEventListener('focusout', resumeDismissal);
        close.addEventListener('click', removeToast);
        toastContainer.append(toast);
        positionToasts();
        playNotificationSound();
    }

    function announceNewNotifications(nextNotifications) {
        const newNotifications = [];
        nextNotifications.forEach(notification => {
            const id = String(notification.id ?? '');
            if (!id || seenNotificationIds.has(id)) return;
            seenNotificationIds.add(id);
            if (hasLoadedNotifications) newNotifications.push(notification);
        });
        if (!hasLoadedNotifications) {
            hasLoadedNotifications = true;
            return;
        }
        newNotifications.reverse().forEach(showToast);
    }

    function render() {
        const recipientFiltered = isAdmin
            ? notifications.filter(item => recipientFilter === 'mine' ? item.esPropia : !item.esPropia)
            : notifications;
        const visible = filter === 'unread'
            ? recipientFiltered.filter(item => !item.leida)
            : recipientFiltered;
        if (!visible.length) {
            list.innerHTML = `<div class="notification-empty"><span aria-hidden="true">${symbol('confirmado', '✓')}</span><p>¡Estás al día!<br>No tienes notificaciones pendientes.</p></div>`;
            return;
        }
        list.innerHTML = visible.map(item => `
            <button type="button" class="notification-item ${item.leida ? '' : 'is-unread'} priority-${escapeHtml(item.prioridad)}"
                data-notification-id="${escapeHtml(item.id)}" ${item.url ? `data-notification-url="${escapeHtml(item.url)}"` : ''}>
                <span class="notification-item-icon icon-${escapeHtml(item.icono)}" aria-hidden="true">${symbol(symbols.get(item.icono), Object.hasOwn(icon, item.icono) ? icon[item.icono] : 'i')}</span>
                <span class="notification-item-content">
                    <strong>${escapeHtml(item.titulo)}</strong>
                    <span${item.solicitante ? ' class="user-identity"' : ''}>${item.solicitante ? window.RegeneraAvatar.markup(item.solicitante.nombre, item.solicitante.foto) : ''}<span>${escapeHtml(item.mensaje)}</span></span>
                    ${item.destinatario ? `<small class="user-identity">${window.RegeneraAvatar.markup(item.destinatario.nombre, item.destinatario.foto)}<span>Para: ${escapeHtml(item.destinatario.nombre)} · ${escapeHtml(item.destinatario.rol)}</span></small>` : ''}
                    <time datetime="${escapeHtml(item.fechaCreacion)}">${escapeHtml(formatDate(item.fechaCreacion))}</time>
                </span>
                ${item.leida ? '' : '<span class="notification-unread-dot" aria-label="No leída"></span>'}
            </button>
        `).join('');
    }

    async function load() {
        const token = sessionStorage.getItem('regenera_access_token');
        if (!token) {
            toggles.forEach(toggle => { toggle.hidden = true; });
            updateBadge(0);
            return;
        }
        try {
            const response = await loadForCompleteProfile(
                token,
                () => sessionStorage.getItem('regenera_access_token'),
                async accessToken => {
                    const result = await axios.get(apiUrl('/api/auth/me'), {
                        headers: { Accept: 'application/json', Authorization: `Bearer ${accessToken}` },
                    });
                    return result.data.usuario;
                },
                accessToken => axios.get(apiUrl('/api/notificaciones'), {
                    headers: { Accept: 'application/json', Authorization: `Bearer ${accessToken}` },
                }),
            );
            if (sessionStorage.getItem('regenera_access_token') !== token) return;
            if (!response) {
                notifications = [];
                updateBadge(0);
                toggles.forEach(toggle => { toggle.hidden = true; });
                return;
            }
            notifications = response.data.data ?? [];
            announceNewNotifications(notifications);
            isAdmin = response.data.esAdmin === true;
            recipientFilters.hidden = !isAdmin;
            updateBadge(notifications.filter(item => !item.leida).length);
            render();
            status.textContent = '';
            toggles.forEach(toggle => { toggle.hidden = false; });
        } catch (error) {
            if (sessionStorage.getItem('regenera_access_token') !== token) return;
            if (error.response?.status === 401 && sessionStorage.getItem('regenera_access_token') === token) {
                sessionStorage.removeItem('regenera_access_token');
            }
            status.textContent = 'No pudimos cargar tus notificaciones. Intenta de nuevo.';
        }
    }

    function setOpen(open, focusReturnTarget = document.activeElement) {
        drawer.hidden = !open;
        document.querySelector('[data-notification-close].notification-drawer-backdrop').hidden = !open;
        toggles.forEach(toggle => toggle.setAttribute('aria-expanded', String(open)));
        document.body.classList.toggle('notification-drawer-open', open);
        if (open) {
            lastFocused = focusReturnTarget;
            drawer.focus();
            load();
        } else {
            lastFocused?.focus();
        }
    }

    toggles.forEach(toggle => toggle.addEventListener('click', () => {
        if (mobileMenu?.classList.contains('active')) {
            menuButton?.click();
        }
        setOpen(true);
    }));
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
    drawer.querySelectorAll('[data-recipient-filter]').forEach(button => {
        button.addEventListener('click', () => {
            recipientFilter = button.dataset.recipientFilter;
            drawer.querySelectorAll('[data-recipient-filter]').forEach(item => {
                item.classList.toggle('is-active', item === button);
            });
            render();
        });
    });
    drawer.querySelector('[data-notification-mark-all]').addEventListener('click', async event => {
        const button = event.currentTarget;
        if (button.disabled) return;
        button.disabled = true;
        try {
            await axios.patch(apiUrl('/api/notificaciones/marcar-todas-leidas'), {}, { headers: tokenHeaders() });
            notifications = notifications.map(item => item.esPropia ? { ...item, leida: true } : item);
            updateBadge(notifications.filter(item => !item.leida).length);
            render();
        } catch {
            status.textContent = 'No se pudieron marcar como leídas.';
        } finally {
            button.disabled = false;
        }
    });
    list.addEventListener('click', async event => {
        const item = event.target.closest('[data-notification-id]');
        if (!item) return;
        const notification = notifications.find(value => value.id === item.dataset.notificationId);
        if (!notification?.esPropia) {
            if (notification?.url) window.location.assign(notification.url);
            return;
        }
        if (notification.leida) {
            if (notification.url) window.location.assign(notification.url);
            return;
        }
        if (item.disabled) return;
        item.disabled = true;
        try {
            await axios.patch(apiUrl(`/api/notificaciones/${encodeURIComponent(item.dataset.notificationId)}/leida`), {}, { headers: tokenHeaders() });
            if (notification) notification.leida = true;
            updateBadge(notifications.filter(value => !value.leida).length);
            if (notification?.url) window.location.assign(notification.url);
            else render();
        } catch {
            status.textContent = 'No se pudo actualizar la notificación.';
            item.disabled = false;
        }
    });
    document.addEventListener('regenera:auth-changed', event => {
        if (event.detail?.user) {
            hasLoadedNotifications = false;
            seenNotificationIds.clear();
            load();
            return;
        }
        hasLoadedNotifications = false;
        seenNotificationIds.clear();
        toggles.forEach(toggle => { toggle.hidden = true; });
        updateBadge(0);
    });
    document.addEventListener('regenera:inscription-created', load);
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', event => {
            if (event.data?.type === 'regenera:notifications-updated') {
                load();
            }
        });
    }
    window.addEventListener('resize', positionToasts);
    window.addEventListener('scroll', positionToasts, { passive: true });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !drawer.hidden) setOpen(false);
    });
    load();
    window.setInterval(load, 15_000);
}
