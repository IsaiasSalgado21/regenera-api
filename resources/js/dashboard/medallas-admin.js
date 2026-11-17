import '../../css/dashboard.css';
import { medalIconMarkup, medalIcons } from '../medal-icons';

const root = document.querySelector('[data-medal-admin]');
if (root) {
    const apiBase = document.querySelector('meta[name="api-base-url"]')?.content || '/api';
    const find = selector => root.querySelector(selector);
    const form = find('[data-medal-form]');
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    }[character]));
    let editing = null;
    let medals = [];

    async function request(path, options = {}) {
        const token = sessionStorage.getItem('regenera_access_token');
        if (!token) throw new Error('Inicia sesión con una cuenta de administrador.');
        const response = await fetch(`${apiBase}${path}`, {
            ...options,
            headers: {
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
                ...(options.body ? { 'Content-Type': 'application/json' } : {}),
            },
        });
        const data = await response.json();
        if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'No se pudo completar la solicitud.');
        return data;
    }

    function showError(message) {
        find('[data-medal-error]').textContent = message;
        find('[data-medal-error]').hidden = false;
    }

    function render() {
        find('[data-medal-empty]').hidden = medals.length > 0;
        find('[data-medal-list]').innerHTML = medals.map(medal => `<article class="dashboard-card">
            <div class="dashboard-card-body">
                <div class="dashboard-card-meta"><span class="status-pill">${medal.activo ? 'Activa' : 'Desactivada'}</span><span>${escapeHtml(medal.slug)}</span></div>
                <span class="quiz-medal-preview-icon">${medalIconMarkup(medal.icono)}</span>
                <h3>${escapeHtml(medal.nombre)}</h3><p>${escapeHtml(medal.descripcion)}</p>
            </div>
            <div class="dashboard-card-actions">
                <button class="button button-secondary" type="button" data-medal-edit="${escapeHtml(medal.slug)}">Editar</button>
                <button class="button button-danger" type="button" data-medal-delete="${escapeHtml(medal.slug)}">${medal.activo ? 'Eliminar o desactivar' : 'Eliminar'}</button>
            </div>
        </article>`).join('');
    }

    async function load() {
        find('[data-medal-loading]').hidden = false;
        find('[data-medal-error]').hidden = true;
        find('[data-medal-retry]').hidden = true;
        try {
            medals = (await request('/admin/medallas')).data;
            find('[data-medal-create]').hidden = false;
            render();
        } catch (error) {
            find('[data-medal-create]').hidden = true;
            find('[data-medal-retry]').hidden = false;
            showError(error.message);
        } finally {
            find('[data-medal-loading]').hidden = true;
        }
    }

    function openEditor(medal = null) {
        editing = medal?.slug || null;
        form.reset();
        find('[data-medal-error]').hidden = true;
        form.elements.slug.readOnly = Boolean(medal);
        form.elements.slug.value = medal?.slug || '';
        form.elements.nombre.value = medal?.nombre || '';
        form.elements.descripcion.value = medal?.descripcion || '';
        form.elements.icono.value = medal?.icono || '';
        form.elements.activo.checked = medal ? medal.activo : true;
        find('[data-medal-active-label]').hidden = !medal;
        find('[data-medal-form-heading]').textContent = medal ? 'Editar medalla' : 'Nueva medalla';
        form.hidden = false;
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    medalIcons.forEach(icon => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'quiz-medal-icon-choice';
        button.innerHTML = `${medalIconMarkup(icon.id)}<span>${escapeHtml(icon.label)}</span>`;
        button.addEventListener('click', () => { form.elements.icono.value = icon.id; });
        find('[data-medal-icons]').append(button);
    });
    find('[data-medal-create]').addEventListener('click', () => openEditor());
    find('[data-medal-retry]').addEventListener('click', load);
    root.querySelectorAll('[data-medal-cancel]').forEach(button => button.addEventListener('click', () => { form.hidden = true; }));
    find('[data-medal-list]').addEventListener('click', async event => {
        const edit = event.target.closest('[data-medal-edit]');
        if (edit) {
            openEditor(medals.find(medal => medal.slug === edit.dataset.medalEdit));
            return;
        }
        const remove = event.target.closest('[data-medal-delete]');
        if (!remove || !window.confirm('Se desactivará si está vinculada u obtenida; de lo contrario se eliminará. ¿Continuar?')) return;
        remove.disabled = true;
        try {
            const result = await request(`/admin/medallas/${encodeURIComponent(remove.dataset.medalDelete)}`, { method: 'DELETE' });
            find('[data-medal-notice]').textContent = result.message;
            find('[data-medal-notice]').hidden = false;
            await load();
        } catch (error) {
            showError(error.message);
        } finally {
            remove.disabled = false;
        }
    });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const payload = {
            slug: form.elements.slug.value.trim(),
            nombre: form.elements.nombre.value.trim(),
            descripcion: form.elements.descripcion.value.trim(),
            icono: form.elements.icono.value.trim(),
            activo: form.elements.activo.checked,
        };
        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        try {
            const result = await request(editing ? `/admin/medallas/${encodeURIComponent(editing)}` : '/admin/medallas', {
                method: editing ? 'PUT' : 'POST',
                body: JSON.stringify(payload),
            });
            form.hidden = true;
            find('[data-medal-notice]').textContent = result.message;
            find('[data-medal-notice]').hidden = false;
            await load();
        } catch (error) {
            showError(error.message);
        } finally {
            submit.disabled = false;
        }
    });
    load();
}
