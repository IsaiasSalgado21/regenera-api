const page = document.querySelector('[data-flora-admin]');

if (page) initializeFloraAdmin();

function initializeFloraAdmin() {
    const dialog = page.querySelector('[data-flora-dialog]');
    const form = page.querySelector('[data-flora-form]');
    const status = page.querySelector('[data-flora-status]');
    const title = page.querySelector('[data-flora-form-title]');
    const images = page.querySelector('[data-flora-existing-images]');
    const apiBase = document.querySelector('meta[name="api-base-url"]')?.content || '/api';
    const token = () => sessionStorage.getItem('regenera_access_token');
    let activeId = null;

    const request = async (path, options = {}) => {
        const response = await fetch(`${apiBase}${path}`, {
            ...options,
            headers: {
                Accept: 'application/json',
                ...(token() ? { Authorization: `Bearer ${token()}` } : {}),
                ...options.headers,
            },
        });
        const body = await response.json().catch(() => ({}));
        if (!response.ok) {
            const validationError = Object.values(body.errors || {}).flat()[0];
            throw new Error(validationError || body.message || 'No se pudo completar la solicitud.');
        }
        return body;
    };

    const setField = (name, value) => {
        const field = form.elements.namedItem(name);
        if (field) field.value = value ?? '';
    };

    const openEditor = async recordId => {
        activeId = recordId || null;
        form.reset();
        images.replaceChildren();
        status.textContent = '';
        title.textContent = activeId ? 'Editar registro de flora/fauna' : 'Crear registro';
        if (activeId) {
            dialog.showModal();
            status.textContent = 'Cargando registro…';
            try {
                const { data } = await request(`/flora-fauna/${encodeURIComponent(activeId)}`);
                const record = data.data;
                ['nombre', 'nombreCientifico', 'tipo', 'descripcion', 'habitat', 'estadoConservacion', 'fuenteInformacion'].forEach(key => setField(key, record[key]));
                setField('ubicacion[nombre]', record.ubicacion?.nombre);
                setField('ubicacion[latitud]', record.ubicacion?.latitud);
                setField('ubicacion[longitud]', record.ubicacion?.longitud);
                (record.imagenes || []).forEach((image, index) => {
                    const label = document.createElement('label');
                    label.className = 'flora-admin-image-remove';
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.name = 'imagenesEliminar[]';
                    checkbox.value = String(index);
                    const preview = document.createElement('img');
                    preview.src = image;
                    preview.alt = `Imagen existente ${index + 1}`;
                    preview.loading = 'lazy';
                    const text = document.createElement('span');
                    text.textContent = 'Quitar imagen';
                    label.append(checkbox, preview, text);
                    images.append(label);
                });
                status.textContent = '';
            } catch (error) {
                status.textContent = error.message;
            }
            return;
        }
        dialog.showModal();
    };

    page.addEventListener('click', async event => {
        if (event.target.closest('[data-flora-create]')) void openEditor();
        if (event.target.closest('[data-flora-close]')) dialog.close();
    });

    page.querySelector('[data-flora-admin-actions]')?.addEventListener('click', async event => {
        if (event.target.closest('[data-flora-edit]')) {
            void openEditor(page.dataset.floraId);
            return;
        }
        if (event.target.closest('[data-flora-archive]')) {
            if (!window.confirm('¿Retirar esta ficha del catálogo? El registro se archivará y podrá restaurarse desde administración.')) return;
            try {
                await request(`/admin/flora-fauna/${encodeURIComponent(page.dataset.floraId)}/archivar`, { method: 'PATCH' });
                window.location.assign('/flora');
            } catch (error) {
                window.alert(error.message);
            }
        }
    });

    dialog.addEventListener('click', event => {
        if (event.target === dialog) dialog.close();
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        status.textContent = 'Guardando…';
        const payload = new FormData(form);
        if (activeId) payload.append('_method', 'PUT');
        try {
            await request(activeId ? `/flora-fauna/${encodeURIComponent(activeId)}` : '/flora-fauna', {
                method: 'POST',
                body: payload,
            });
            window.location.reload();
        } catch (error) {
            status.textContent = error.message;
        }
    });

    const accessToken = token();
    if (!accessToken) return;
    request('/auth/me').then(({ usuario }) => {
        if (usuario.rol !== 'admin') return;
        page.querySelectorAll('[data-flora-create], [data-flora-admin-actions]').forEach(control => { control.hidden = false; });
    }).catch(() => {});
}