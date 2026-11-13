import axios from 'axios';
import { renderAvatarMarkup } from './avatar-creator';

const page = document.querySelector('[data-public-profile]');

if (page) {
    const apiBase = document.querySelector('meta[name="api-base-url"]')?.content || '/api';
    const status = page.querySelector('[data-public-profile-status]');
    const content = [...page.querySelectorAll('[data-public-profile-content]')];
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    })[character]);
    const formatDate = value => {
        if (!value) return '';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return '';
        return new Intl.DateTimeFormat('es-MX', { day: 'numeric', month: 'long', year: 'numeric' }).format(date);
    };

    axios.get(`${apiBase.replace(/\/$/, '')}/perfiles/${encodeURIComponent(page.dataset.userId)}`, {
        headers: { Accept: 'application/json' },
    }).then(({ data }) => {
        const user = data.data;
        const avatar = page.querySelector('[data-public-avatar]');
        page.querySelector('[data-public-name]').textContent = user.nombre || 'Participante';
        avatar.innerHTML = user.foto
            ? `<img src="${escapeHtml(user.foto)}" alt="Avatar de ${escapeHtml(user.nombre)}">`
            : renderAvatarMarkup({});
        if (user.foto) {
            avatar.querySelector('img').addEventListener('error', () => {
                avatar.innerHTML = renderAvatarMarkup({});
            }, { once: true });
        }
        page.querySelector('[data-public-medals]').innerHTML = user.medallas.length
            ? user.medallas.map(medal => `<article class="profile-medal is-unlocked">
                <span class="profile-medal-icon" aria-hidden="true">✦</span>
                <span class="profile-medal-state">Obtenida</span>
                <h4>${escapeHtml(medal.nombre)}</h4>
                <p>${escapeHtml(medal.descripcion)}</p>
                ${medal.fechaObtencion ? `<time datetime="${escapeHtml(medal.fechaObtencion)}">${escapeHtml(formatDate(medal.fechaObtencion))}</time>` : ''}
            </article>`).join('')
            : '<p class="profile-muted">Aún no hay medallas obtenidas para mostrar.</p>';
        content.forEach(section => { section.hidden = false; });
        status.hidden = true;
    }).catch(error => {
        status.textContent = error.response?.status === 404
            ? 'Este perfil no está disponible.'
            : 'No se pudo cargar el perfil. Intenta de nuevo más tarde.';
    });
}