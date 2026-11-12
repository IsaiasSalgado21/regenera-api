import axios from 'axios';
import { apiUrl } from './api-url';

const page = document.querySelector('[data-notification-center]');
if (page) {
    const list = page.querySelector('[data-center-list]');
    const adminColumns = page.querySelector('[data-admin-columns]');
    const adminLists = {
        admins: page.querySelector('[data-admin-list="admins"]'),
        general: page.querySelector('[data-admin-list="general"]'),
    };
    const status = page.querySelector('[data-center-status]');
    const markAll = page.querySelector('[data-center-mark-all]');
    const token = sessionStorage.getItem('regenera_access_token');
    let notifications = [];
    let filter = 'all';
    let isAdmin = false;

    const headers = {
        Accept: 'application/json',
        ...(token ? { Authorization: 'Bearer ' + token } : {}),
    };
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    }[character]));
    const formatDate = value => value ? new Intl.DateTimeFormat('es-MX', {
        dateStyle: 'full',
        timeStyle: 'short',
    }).format(new Date(value)) : 'Fecha no disponible';

    function render() {
        const visible = filter === 'unread' ? notifications.filter(item => !item.leida) : notifications;
        if (isAdmin) {
            const adminNotifications = visible.filter(item => item.destinatario?.rol === 'admin');
            const generalNotifications = visible.filter(item => item.destinatario?.rol !== 'admin');
            list.hidden = true;
            adminColumns.hidden = false;
            renderList(adminLists.admins, adminNotifications, 'No hay notificaciones dirigidas a administradores.');
            renderList(adminLists.general, generalNotifications, 'No hay notificaciones generales para mostrar.');
            page.querySelector('[data-admin-count="admins"]').textContent = String(adminNotifications.length);
            page.querySelector('[data-admin-count="general"]').textContent = String(generalNotifications.length);
            return;
        }

        list.hidden = false;
        adminColumns.hidden = true;
        renderList(list, visible, 'No hay notificaciones en esta sección.');
    }

    function renderList(target, visible, emptyMessage) {
        if (!visible.length) {
            target.innerHTML = `<div class="notification-empty"><span aria-hidden="true">✓</span><p>${escapeHtml(emptyMessage)}</p></div>`;
            return;
        }

        target.innerHTML = visible.map(item => `
            <article class="notification-center-item ${item.leida ? '' : 'is-unread'}">
                <div class="notification-center-item-content">
                    <div class="notification-center-item-heading">
                        <h2>${escapeHtml(item.titulo)}</h2>
                        <span class="notification-read-state">${item.leida ? 'Leída' : 'No leída'}</span>
                    </div>
                    <p>${escapeHtml(item.mensaje)}</p>
                    <time datetime="${escapeHtml(item.fechaCreacion)}">${escapeHtml(formatDate(item.fechaCreacion))}</time>
                    ${item.destinatario ? `<small>Destinatario: ${escapeHtml(item.destinatario.nombre)} (${escapeHtml(item.destinatario.correo)}) · ${escapeHtml(item.destinatario.rol)}</small>` : ''}
                    ${item.fechaLectura ? `<small>Leída: ${escapeHtml(formatDate(item.fechaLectura))}</small>` : ''}
                </div>
                <div class="notification-center-item-actions">
                    ${!item.leida && item.esPropia ? `<button type="button" data-center-read="${escapeHtml(item.id)}">Marcar como leída</button>` : ''}
                    ${item.url ? `<a href="${escapeHtml(item.url)}">Abrir</a>` : ''}
                </div>
            </article>
        `).join('');
    }

    async function load() {
        if (!token) {
            status.textContent = 'Inicia sesión para consultar tus notificaciones.';
            return;
        }
        try {
            const response = await axios.get(apiUrl('/api/notificaciones'), { headers });
            notifications = response.data.data ?? [];
            isAdmin = response.data.esAdmin === true;
            const openedNotificationId = new URLSearchParams(window.location.search).get('pushNotification');
            const openedNotification = notifications.find(item => item.id === openedNotificationId);
            if (openedNotification?.esPropia && !openedNotification.leida) {
                await axios.patch(apiUrl(`/api/notificaciones/${encodeURIComponent(openedNotification.id)}/leida`), {}, { headers });
                openedNotification.leida = true;
                openedNotification.fechaLectura = new Date().toISOString();
            }
            render();
            status.textContent = '';
        } catch (error) {
            status.textContent = error.response?.status === 401
                ? 'Tu sesión expiró. Inicia sesión de nuevo.'
                : 'No pudimos cargar las notificaciones. Intenta de nuevo.';
        }
    }

    page.querySelectorAll('[data-center-filter]').forEach(button => {
        button.addEventListener('click', () => {
            filter = button.dataset.centerFilter;
            page.querySelectorAll('[data-center-filter]').forEach(tab => tab.classList.toggle('is-active', tab === button));
            render();
        });
    });

    page.addEventListener('click', async event => {
        const button = event.target.closest('[data-center-read]');
        if (!button) return;
        button.disabled = true;
        try {
            await axios.patch(apiUrl(`/api/notificaciones/${encodeURIComponent(button.dataset.centerRead)}/leida`), {}, { headers });
            const notification = notifications.find(item => item.id === button.dataset.centerRead);
            if (notification) {
                notification.leida = true;
                notification.fechaLectura = new Date().toISOString();
            }
            render();
        } catch {
            button.disabled = false;
            status.textContent = 'No se pudo actualizar el estado de lectura.';
        }
    });

    markAll.addEventListener('click', async () => {
        markAll.disabled = true;
        try {
            await axios.patch(apiUrl('/api/notificaciones/marcar-todas-leidas'), {}, { headers });
            const readAt = new Date().toISOString();
            notifications = notifications.map(item => item.esPropia ? { ...item, leida: true, fechaLectura: readAt } : item);
            render();
        } catch {
            status.textContent = 'No se pudieron marcar tus notificaciones como leídas.';
        } finally {
            markAll.disabled = false;
        }
    });

    load();
}
