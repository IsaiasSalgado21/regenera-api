import '../../css/dashboard.css';

const root = document.querySelector('[data-volunteer-panel]');

if (root) {
    const apiBase = document.querySelector('meta[name="api-base-url"]')?.content || '/api';
    const appBase = apiBase.replace(/\/api\/?$/, '');
    const find = selector => root.querySelector(selector);
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    }[character]));

    async function request(path, token) {
        const response = await fetch(`${apiBase}${path}`, {
            headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
        });
        if (!response.ok) throw new Error(response.status === 401
            ? 'Inicia sesión para consultar tu panel.'
            : 'No se pudieron consultar tus actividades. Inténtalo de nuevo.');
        return response.json();
    }

    function render(items, selector, emptySelector, proposal) {
        find(emptySelector).hidden = items.length > 0;
        find(selector).innerHTML = items.map(item => {
            const date = item.inicioIso ? new Date(item.inicioIso) : null;
            const dateLabel = date && !Number.isNaN(date.getTime())
                ? new Intl.DateTimeFormat('es-MX', { dateStyle: 'medium', timeStyle: 'short' }).format(date)
                : 'Fecha por confirmar';
            const labels = proposal
                ? { pendiente: 'Pendiente de aprobación', rechazado: 'Rechazado', publicado: 'Publicado', cancelado: 'Cancelado', finalizado: 'Finalizado' }
                : { inscrita: 'Inscripción confirmada', activa: 'Inscripción activa', completada: 'Participación completada' };
            const status = proposal ? item.estado : item.estadoInscripcion;
            const link = ['publicado', 'cancelado', 'finalizado'].includes(item.estado)
                ? `<a class="button button-secondary" href="${escapeHtml(appBase)}/voluntariados/${encodeURIComponent(item.id)}">Ver voluntariado</a>`
                : '';
            return `<article class="dashboard-card"><div class="dashboard-card-body">
                <div class="dashboard-card-meta"><span class="status-pill">${escapeHtml(labels[status] || status || 'Sin estado')}</span></div>
                <h3>${escapeHtml(item.titulo)}</h3><p>${escapeHtml(dateLabel)}</p>
                ${proposal && item.motivoRechazo ? `<p>Motivo: ${escapeHtml(item.motivoRechazo)}</p>` : ''}
                </div><div class="dashboard-card-actions">${link}</div></article>`;
        }).join('');
    }

    async function load() {
        find('[data-volunteer-error]').hidden = true;
        find('[data-volunteer-content]').hidden = true;
        find('[data-volunteer-loading]').hidden = false;
        try {
            const token = sessionStorage.getItem('regenera_access_token');
            if (!token) throw new Error('Inicia sesión para consultar tu panel.');
            const session = await request('/auth/me', token);
            if (session.usuario?.rol !== 'usuario') throw new Error('Este panel corresponde a cuentas de voluntario.');
            const data = await request('/voluntariados', token);
            const upcoming = (data.misInscripciones || []).filter(item => {
                const end = Date.parse(item.finIso);
                return item.estado === 'publicado' && item.estadoInscripcion !== 'completada'
                    && Number.isFinite(end) && end >= Date.now();
            }).sort((a, b) => Date.parse(a.inicioIso) - Date.parse(b.inicioIso));
            const proposals = data.misVoluntariados || [];
            find('[data-volunteer-welcome]').textContent = `Hola, ${session.usuario.nombre}.`;
            find('[data-volunteer-summary]').textContent = `${upcoming.length} ${upcoming.length === 1 ? 'actividad próxima' : 'actividades próximas'}.`;
            render(upcoming, '[data-volunteer-activities]', '[data-volunteer-activities-empty]', false);
            render(proposals, '[data-volunteer-proposals]', '[data-volunteer-proposals-empty]', true);
            find('[data-volunteer-content]').hidden = false;
        } catch (error) {
            find('[data-volunteer-error-message]').textContent = error.message;
            find('[data-volunteer-error]').hidden = false;
        } finally {
            find('[data-volunteer-loading]').hidden = true;
        }
    }

    root.querySelectorAll('[data-volunteer-tab]').forEach(button => button.addEventListener('click', () => {
        root.querySelectorAll('[data-volunteer-tab]').forEach(tab => {
            const active = tab === button;
            tab.classList.toggle('is-active', active);
            if (active) tab.setAttribute('aria-current', 'page');
            else tab.removeAttribute('aria-current');
        });
        root.querySelectorAll('[data-volunteer-section]').forEach(section => {
            section.hidden = section.dataset.volunteerSection !== button.dataset.volunteerTab;
        });
    }));
    find('[data-volunteer-retry]').addEventListener('click', load);
    load();
}
