@extends('layouts.app')

@section('title', 'Crear voluntariado · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/create-voluntariado.css') }}">
@endpush

@section('content')
<main class="page">
    <section class="page-hero">
        <div class="page-hero-background"></div>
        <div class="page-hero-overlay"></div>
        <div class="page-hero-content">
            <span class="page-label">PROPÓNLO</span>
            <h1>Crea un voluntariado</h1>
            <p>
                Organiza una actividad de conservación, limpieza o educación ambiental
                en Baja California Sur y convoca a la comunidad a participar.
            </p>
        </div>
    </section>

    <section class="page-content">
        <div id="authRequired" class="auth-required" hidden>
            <div class="auth-card">
                <div class="auth-heading">
                    <span class="auth-eyebrow">ACCESO REQUERIDO</span>
                    <h1>Inicia sesión para crear un voluntariado</h1>
                    <p>
                        <span id="authRequiredMessage">
                        Para proponer un voluntariado necesitas una cuenta en Regenera BCS.
                        Si ya tienes una, inicia sesión. Si no, crea una cuenta nueva.
                        </span>
                    </p>
                </div>
                <div class="auth-actions">
                    <a href="{{ route('login', ['redirect' => url('/voluntariados/crear')]) }}" class="button button-primary">Iniciar sesión</a>
                    <a href="{{ route('login') }}" class="button button-secondary">Crear cuenta</a>
                </div>
            </div>
        </div>

        <div id="createFormContainer" class="create-form-container" hidden>
            <div class="page-intro">
                <h2>Antes de comenzar</h2>
                <p>
                    Completa el formulario con los detalles de tu actividad. Una vez enviado,
                    quedará registrado según tu perfil:
                </p>
                <ul class="create-info-list">
                    <li id="infoAdminVerificado" hidden><strong>Administrador o organizador verificado:</strong> se publica inmediatamente.</li>
                    <li id="infoUsuarioNormal" hidden><strong>Usuario sin verificar:</strong> queda pendiente de aprobación del equipo.</li>
                </ul>
            </div>

            <form id="createVoluntariadoForm" class="dashboard-form create-form" novalidate>
                <div class="dashboard-form-heading">
                    <div>
                        <span class="dashboard-eyebrow">EDITOR</span>
                        <h3>Nuevo voluntariado</h3>
                    </div>
                    <a class="icon-button" href="{{ url('/voluntariados') }}" aria-label="Cerrar formulario">×</a>
                </div>

                <div class="form-message" id="createMessage" role="status" aria-live="polite"></div>

                <div class="dashboard-form-row">
                    <label for="titulo">Título del voluntariado <span class="required">*</span>
                        <input type="text" id="titulo" name="titulo"
                            required maxlength="150"
                            placeholder="Ej. Jornada de limpieza en Playa Balandra">
                    </label>

                    <label for="categoria">Categoría <span class="required">*</span>
                        <select id="categoria" name="categoria" required>
                            <option value="">Selecciona una categoría</option>
                            <option value="limpieza">Limpieza</option>
                            <option value="restauracion">Restauración</option>
                            <option value="conservacion">Conservación</option>
                            <option value="reforestacion">Reforestación</option>
                            <option value="monitoreo">Monitoreo</option>
                            <option value="educacion-ambiental">Educación ambiental</option>
                        </select>
                    </label>
                </div>

                <label for="lugarId">Lugar <span class="required">*</span>
                    <select id="lugarId" name="lugarId" required>
                        <option value="">Selecciona un lugar</option>
                    </select>
                </label>

                <div class="dashboard-form-row">
                    <label class="dashboard-date-field" for="fechaInicio">Fecha de inicio <span class="required">*</span>
                        <span class="dashboard-date-control">
                            <span aria-hidden="true">📅</span>
                            <input type="date" id="fechaInicio" name="fechaInicio" required>
                        </span>
                    </label>

                    <label class="dashboard-date-field" for="fechaFin">Fecha de fin <span class="required">*</span>
                        <span class="dashboard-date-control">
                            <span aria-hidden="true">📅</span>
                            <input type="date" id="fechaFin" name="fechaFin" required>
                        </span>
                    </label>
                </div>

                <div class="dashboard-form-row">
                    <label class="dashboard-time-field" for="horaInicio">Hora de inicio
                        <span class="dashboard-time-control">
                            <span aria-hidden="true">🕒</span>
                            <input type="time" id="horaInicio" name="horaInicio" required>
                        </span>
                    </label>

                    <label class="dashboard-time-field" for="horaFin">Hora de fin
                        <span class="dashboard-time-control">
                            <span aria-hidden="true">🕒</span>
                            <input type="time" id="horaFin" name="horaFin" required>
                        </span>
                    </label>
                </div>

                <div class="dashboard-capacity-field">
                    <span class="dashboard-choice-legend">Cupo de participantes</span>
                    <div class="dashboard-capacity-control">
                        <span class="dashboard-capacity-icon" aria-hidden="true">👤</span>
                        <button type="button" class="dashboard-capacity-step"
                                data-capacity-step="-1"
                                aria-label="Disminuir cupo">−</button>
                        <input type="number" id="cupo" name="cupo"
                            min="1" max="10000" value="10"
                            inputmode="numeric" required
                            aria-label="Número de participantes">
                        <button type="button" class="dashboard-capacity-step"
                                data-capacity-step="1"
                                aria-label="Aumentar cupo">+</button>
                        <span class="dashboard-capacity-unit">personas</span>
                    </div>
                </div>

                <div class="dashboard-token-field" data-suggestion-field="requisitos">
                    <label for="requisitos-query">Requisitos (opcional)</label>
                    <div class="dashboard-token-selected" data-selected-values></div>
                    <input id="requisitos-query" type="search" data-suggestion-query
                        placeholder="Buscar o agregar requisito…" autocomplete="off">
                    <div class="dashboard-token-suggestions" role="listbox" aria-label="Sugerencias de requisitos" hidden></div>
                    <textarea id="requisitos" name="requisitos" class="suggestion-values" rows="1" tabindex="-1" aria-hidden="true"></textarea>
                </div>

                <div class="dashboard-token-field" data-suggestion-field="materiales">
                    <label for="materiales-query">Materiales necesarios (opcional)</label>
                    <div class="dashboard-token-selected" data-selected-values></div>
                    <input id="materiales-query" type="search" data-suggestion-query
                        placeholder="Buscar o agregar material…" autocomplete="off">
                    <div class="dashboard-token-suggestions" role="listbox" aria-label="Sugerencias de materiales" hidden></div>
                    <textarea id="materiales" name="materiales" class="suggestion-values" rows="1" tabindex="-1" aria-hidden="true"></textarea>
                </div>

                <div class="dashboard-image-field">
                    <label for="imagen">Imagen del voluntariado (opcional)
                        <input type="file" id="imagen" name="imagen" accept="image/*">
                    </label>
                </div>

                <label for="descripcion">Descripción <span class="required">*</span>
                    <textarea id="descripcion" name="descripcion"
                            rows="5" required maxlength="3000"
                            placeholder="Describe la actividad, qué se hará y por qué es importante para el entorno."></textarea>
                </label>

                <div class="dashboard-form-actions">
                    <button type="submit" class="button button-primary" id="submitBtn">
                        Enviar voluntariado
                    </button>
                    <a class="button button-secondary" href="{{ url('/voluntariados') }}">Cancelar</a>
                </div>
            </form>

        </div>
    </section>
</main>

@push('scripts')
    <script>
        (function () {
            const form = document.getElementById('createVoluntariadoForm');
            const message = document.getElementById('createMessage');
            const submitBtn = document.getElementById('submitBtn');
            const lugarSelect = document.getElementById('lugarId');
            const cupoInput = document.getElementById('cupo');
            const fechaFinInput = document.getElementById('fechaFin');
            const fechaInicioInput = document.getElementById('fechaInicio');
            const suggestionOptions = {
                requisitos: [
                    ['🧴', 'Usar bloqueador biodegradable'],
                    ['👟', 'Usar calzado cómodo'],
                    ['💧', 'Llevar agua suficiente'],
                    ['🧢', 'Llevar gorra o sombrero'],
                    ['🧤', 'Seguir indicaciones del equipo'],
                    ['♻️', 'Separar y recoger residuos'],
                    ['🪪', 'Ser mayor de edad'],
                    ['🤝', 'Participar en equipo'],
                ],
                materiales: [
                    ['🧤', 'Guantes de trabajo'],
                    ['🧴', 'Bloqueador biodegradable'],
                    ['💧', 'Agua potable'],
                    ['🧢', 'Gorra o sombrero'],
                    ['🧹', 'Bolsas para residuos'],
                    ['🛠️', 'Herramientas de trabajo'],
                    ['🦺', 'Chaleco de seguridad'],
                    ['🩹', 'Botiquín de primeros auxilios'],
                ],
            };

            if (!form) return;

            fetch('/api/lugares')
                .then(response => response.json())
                .then(data => {
                    lugarSelect.innerHTML = '<option value="">Selecciona un lugar</option>';
                    (data.data || data.lugares || []).forEach(lugar => {
                        if (lugar.estado === 'activo') {
                            const option = document.createElement('option');
                            option.value = lugar.id || lugar._id || lugar.lugares_id;
                            option.textContent = lugar.nombre;
                            lugarSelect.appendChild(option);
                        }
                    });
                });

            const hoy = new Date().toISOString().split('T')[0];
            fechaInicioInput.min = hoy;
            fechaFinInput.min = hoy;

            fechaInicioInput.addEventListener('change', function () {
                fechaFinInput.min = this.value;
                if (fechaFinInput.value && fechaFinInput.value < this.value) {
                    fechaFinInput.value = this.value;
                }
            });

            form.querySelectorAll('.dashboard-date-control, .dashboard-time-control').forEach(control => {
                control.addEventListener('click', function (event) {
                    if (event.target.closest('input')) return;

                    const input = control.querySelector('input');
                    if (typeof input.showPicker === 'function') {
                        try {
                            input.showPicker();
                        } catch {
                            input.focus();
                            input.click();
                        }
                    } else {
                        input.focus();
                        input.click();
                    }
                });
            });

            form.querySelectorAll('[data-suggestion-field]').forEach(field => {
                const type = field.dataset.suggestionField;
                const textarea = field.querySelector('textarea');
                const queryInput = field.querySelector('[data-suggestion-query]');
                const selectedContainer = field.querySelector('[data-selected-values]');
                const suggestions = field.querySelector('.dashboard-token-suggestions');
                let selected = textarea.value.split(/\r?\n/).map(value => value.trim()).filter(Boolean);

                function renderSelected() {
                    selectedContainer.replaceChildren();
                    selected.forEach(value => {
                        const chip = document.createElement('span');
                        chip.className = 'dashboard-token-chip';

                        const label = document.createElement('span');
                        label.textContent = value;
                        const remove = document.createElement('button');
                        remove.type = 'button';
                        remove.dataset.removeValue = value;
                        remove.setAttribute('aria-label', `Quitar ${value}`);
                        remove.textContent = '×';
                        chip.append(label, remove);
                        selectedContainer.append(chip);
                    });
                    textarea.value = selected.join('\n');
                }

                function renderSuggestions() {
                    const query = queryInput.value.trim().toLocaleLowerCase();
                    const selectedValues = selected.map(value => value.toLocaleLowerCase());
                    const matches = suggestionOptions[type].filter(([, label]) =>
                        !selectedValues.includes(label.toLocaleLowerCase())
                        && (!query || label.toLocaleLowerCase().includes(query)),
                    );

                    suggestions.replaceChildren();
                    matches.forEach(([icon, label]) => {
                        const option = document.createElement('button');
                        option.type = 'button';
                        option.setAttribute('role', 'option');
                        option.dataset.addValue = label;

                        const iconElement = document.createElement('span');
                        iconElement.setAttribute('aria-hidden', 'true');
                        iconElement.textContent = icon;
                        const labelElement = document.createElement('span');
                        labelElement.textContent = label;
                        const addIcon = document.createElement('span');
                        addIcon.className = 'dashboard-token-add-icon';
                        addIcon.setAttribute('aria-hidden', 'true');
                        addIcon.textContent = '+';
                        option.append(iconElement, labelElement, addIcon);
                        suggestions.append(option);
                    });

                    const exactMatch = suggestionOptions[type].some(([, label]) =>
                        label.toLocaleLowerCase() === query,
                    );
                    const customValue = queryInput.value.trim();
                    const alreadySelected = selectedValues.includes(query);
                    if (customValue && !exactMatch && !alreadySelected) {
                        const addCustom = document.createElement('button');
                        addCustom.type = 'button';
                        addCustom.dataset.addValue = customValue;
                        const customLabel = document.createElement('span');
                        customLabel.textContent = `Agregar “${customValue}”`;
                        const addIcon = document.createElement('span');
                        addIcon.className = 'dashboard-token-add-icon';
                        addIcon.setAttribute('aria-hidden', 'true');
                        addIcon.textContent = '+';
                        addCustom.append(customLabel, addIcon);
                        suggestions.append(addCustom);
                    }

                    if (!suggestions.childElementCount) {
                        const empty = document.createElement('p');
                        empty.textContent = 'No hay más opciones para agregar.';
                        suggestions.append(empty);
                    }
                    suggestions.hidden = false;
                }

                function addValue(value) {
                    if (!value || selected.some(item => item.toLocaleLowerCase() === value.toLocaleLowerCase())) return;
                    selected.push(value);
                    queryInput.value = '';
                    renderSelected();
                    renderSuggestions();
                    queryInput.focus();
                }

                renderSelected();
                queryInput.addEventListener('focus', renderSuggestions);
                queryInput.addEventListener('input', renderSuggestions);
                suggestions.addEventListener('click', event => {
                    const option = event.target.closest('[data-add-value]');
                    if (!option) return;
                    addValue(option.dataset.addValue);
                });
                selectedContainer.addEventListener('click', event => {
                    const remove = event.target.closest('[data-remove-value]');
                    if (!remove) return;

                    selected = selected.filter(value => value !== remove.dataset.removeValue);
                    renderSelected();
                    renderSuggestions();
                    queryInput.focus();
                });

                document.addEventListener('click', event => {
                    if (!field.contains(event.target)) suggestions.hidden = true;
                });

                form.addEventListener('reset', () => {
                    window.setTimeout(() => {
                        selected = [];
                        queryInput.value = '';
                        renderSelected();
                        suggestions.hidden = true;
                    });
                });
            });

            form.querySelectorAll('[data-capacity-step]').forEach(button => {
                button.addEventListener('click', function () {
                    const step = Number(this.dataset.capacityStep);
                    const current = Number(cupoInput.value) || 1;
                    cupoInput.value = Math.min(10000, Math.max(1, current + step));
                });
            });

            form.addEventListener('submit', async function (event) {
                event.preventDefault();
                message.textContent = '';
                message.className = 'form-message';
                submitBtn.disabled = true;
                submitBtn.textContent = 'Enviando…';

                const formData = new FormData(form);
                const fechaInicio = formData.get('fechaInicio');
                const fechaFin = formData.get('fechaFin');
                const horaInicio = formData.get('horaInicio');
                const horaFin = formData.get('horaFin');
                const tieneImagen = formData.get('imagen') && formData.get('imagen').size > 0;

                if (tieneImagen) {
                    await submitWithImage(formData, fechaInicio, horaInicio, fechaFin, horaFin);
                } else {
                    await submitWithoutImage(formData, fechaInicio, horaInicio, fechaFin, horaFin);
                }

                submitBtn.disabled = false;
                submitBtn.textContent = 'Enviar voluntariado';
            });

            async function submitWithoutImage(formData, fechaInicio, horaInicio, fechaFin, horaFin) {
                const payload = {
                    titulo: formData.get('titulo'),
                    descripcion: formData.get('descripcion'),
                    categoria: formData.get('categoria'),
                    lugarId: formData.get('lugarId'),
                    fechaInicio: fechaInicio + 'T' + horaInicio + ':00',
                    fechaFin: fechaFin + 'T' + horaFin + ':00',
                    horaInicio: horaInicio,
                    horaFin: horaFin,
                    cupo: parseInt(formData.get('cupo')),
                    requisitos: formData.get('requisitos') ? formData.get('requisitos').split('\n').filter(Boolean) : [],
                    materiales: formData.get('materiales') ? formData.get('materiales').split('\n').filter(Boolean) : [],
                };

                await sendPayload(payload);
            }

            async function submitWithImage(formData, fechaInicio, horaInicio, fechaFin, horaFin) {
                const fd = new FormData();
                fd.append('titulo', formData.get('titulo'));
                fd.append('descripcion', formData.get('descripcion'));
                fd.append('categoria', formData.get('categoria'));
                fd.append('lugarId', formData.get('lugarId'));
                fd.append('fechaInicio', fechaInicio + 'T' + horaInicio + ':00');
                fd.append('fechaFin', fechaFin + 'T' + horaFin + ':00');
                fd.append('horaInicio', horaInicio);
                fd.append('horaFin', horaFin);
                fd.append('cupo', formData.get('cupo'));
                const requisitos = formData.get('requisitos');
                if (requisitos) requisitos.split('\n').filter(Boolean).forEach(value => fd.append('requisitos[]', value));
                const materiales = formData.get('materiales');
                if (materiales) materiales.split('\n').filter(Boolean).forEach(value => fd.append('materiales[]', value));
                fd.append('imagen', formData.get('imagen'));

                const payload = {};
                for (let [key, value] of fd.entries()) {
                    if (key === 'imagen') {
                        payload.imagen = value;
                    } else {
                        payload[key] = value;
                    }
                }
                await sendPayload(payload, fd);
            }

            async function sendPayload(payload, formData = null) {
                try {
                    const token = sessionStorage.getItem('regenera_access_token');
                    const headers = {
                        Accept: 'application/json',
                        ...(token ? { Authorization: `Bearer ${token}` } : {}),
                        ...(!formData ? { 'Content-Type': 'application/json' } : {}),
                    };
                    const response = await fetch('/api/voluntariados', {
                        method: 'POST',
                        headers,
                        body: formData || JSON.stringify(payload),
                    });
                    const result = await response.json();
                    if (!response.ok) throw new Error(result.message || 'Error al crear el voluntariado.');
                    const el = document.getElementById('createMessage');
                    el.textContent = result.message || 'Voluntariado creado correctamente.';
                    el.className = 'form-message is-success';
                    const voluntariado = result.voluntariado || result.data;
                    const voluntariadoId = voluntariado?.id || voluntariado?._id;
                    if (voluntariado?.estado === 'publicado' && voluntariadoId) {
                        setTimeout(() => {
                            window.location.href = '/voluntariados/' + voluntariadoId;
                        }, 1500);
                    } else {
                        form.reset();
                    }
                } catch (error) {
                    const el = document.getElementById('createMessage');
                    el.textContent = error.message || 'Error inesperado. Intenta nuevamente.';
                    el.className = 'form-message is-error';
                }
            }

            // Mostrar solo el mensaje correspondiente al tipo de usuario autenticado
            const authRequired = document.getElementById('authRequired');
            const createFormContainer = document.getElementById('createFormContainer');
            const infoAdmin = document.getElementById('infoAdminVerificado');
            const infoUsuario = document.getElementById('infoUsuarioNormal');

            function showAuthenticationRequired(message) {
                authRequired.hidden = false;
                createFormContainer.hidden = true;
                if (message) {
                    document.getElementById('authRequiredMessage').textContent = message;
                }
            }

            async function initializeForm() {
                const token = sessionStorage.getItem('regenera_access_token');
                if (!token) {
                    showAuthenticationRequired();
                    return;
                }

                try {
                    const response = await fetch('/api/auth/me', {
                        headers: {
                            Accept: 'application/json',
                            Authorization: `Bearer ${token}`,
                        },
                    });
                    if (response.status === 401) {
                        sessionStorage.removeItem('regenera_access_token');
                        showAuthenticationRequired();
                        return;
                    }
                    if (!response.ok) {
                        throw new Error('No se pudo verificar tu sesión. Intenta recargar la página.');
                    }

                    const result = await response.json();
                    const user = result.usuario;
                    if (!user || user.estado !== 'activo') {
                        sessionStorage.removeItem('regenera_access_token');
                        showAuthenticationRequired();
                        return;
                    }

                    authRequired.hidden = true;
                    createFormContainer.hidden = false;

                    const esAdmin = user.rol === 'admin';
                    const esOrganizadorVerificado = user.rol === 'organizador' && Boolean(user.organizadorVerificado);
                    infoAdmin.hidden = !(esAdmin || esOrganizadorVerificado);
                    infoUsuario.hidden = esAdmin || esOrganizadorVerificado;
                } catch (error) {
                    showAuthenticationRequired(error.message);
                }
            }

            initializeForm();
        })();
    </script>
@endpush