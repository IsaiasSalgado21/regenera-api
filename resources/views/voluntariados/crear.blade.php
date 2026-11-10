@extends('layouts.app')

@section('title', 'Crear voluntariado · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
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

            <form id="createVoluntariadoForm" class="create-form" novalidate>
                <div class="create-form-heading">
                    <div>
                        <span class="create-form-eyebrow">EDITOR</span>
                        <h2>Nuevo voluntariado</h2>
                    </div>
                    <a class="create-form-close" href="{{ url('/voluntariados') }}" aria-label="Cerrar formulario">×</a>
                </div>

                <div class="form-message" id="createMessage" role="status" aria-live="polite"></div>

                <div class="form-grid">
                    <div class="form-field full-width">
                        <label for="titulo">Título del voluntariado <span class="required">*</span></label>
                        <input type="text" id="titulo" name="titulo" required maxlength="150"
                            placeholder="Ej. Jornada de limpieza en Playa Balandra">
                    </div>

                    <div class="form-field full-width">
                        <label for="categoria">Categoría <span class="required">*</span></label>
                        <select id="categoria" name="categoria" required>
                            <option value="">Selecciona una categoría</option>
                            <option value="limpieza">Limpieza</option>
                            <option value="restauracion">Restauración</option>
                            <option value="conservacion">Conservación</option>
                            <option value="reforestacion">Reforestación</option>
                            <option value="monitoreo">Monitoreo</option>
                            <option value="educacion-ambiental">Educación ambiental</option>
                        </select>
                    </div>

                    <div class="form-field full-width">
                        <label for="lugarId">Lugar <span class="required">*</span></label>
                        <select id="lugarId" name="lugarId" required>
                            <option value="">Selecciona un lugar</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label for="fechaInicio">Fecha de inicio <span class="required">*</span></label>
                        <input type="date" id="fechaInicio" name="fechaInicio" required>
                    </div>

                    <div class="form-field">
                        <label for="fechaFin">Fecha de fin <span class="required">*</span></label>
                        <input type="date" id="fechaFin" name="fechaFin" required>
                    </div>

                    <div class="form-field">
                        <label for="horaInicio">Hora de inicio <span class="required">*</span></label>
                        <input type="time" id="horaInicio" name="horaInicio" required>
                    </div>

                    <div class="form-field">
                        <label for="horaFin">Hora de fin <span class="required">*</span></label>
                        <input type="time" id="horaFin" name="horaFin" required>
                    </div>

                    <div class="form-field form-field-capacity full-width">
                        <label for="cupo">Cupo de participantes <span class="required">*</span></label>
                        <div class="create-capacity-control">
                            <span class="create-capacity-icon" aria-hidden="true">♟</span>
                            <button type="button" class="create-capacity-step" data-capacity-step="-1" aria-label="Disminuir cupo">−</button>
                            <input type="number" id="cupo" name="cupo" required min="1" max="10000" value="10"
                                inputmode="numeric" aria-label="Número de participantes">
                            <button type="button" class="create-capacity-step" data-capacity-step="1" aria-label="Aumentar cupo">+</button>
                            <span class="create-capacity-unit">personas</span>
                        </div>
                    </div>

                    <div class="form-field full-width">
                        <label for="requisitos">Requisitos (opcional)</label>
                        <textarea id="requisitos" name="requisitos" rows="3"
                            placeholder="Ej. Traer ropa cómoda, calzado cerrado, agua."></textarea>
                    </div>

                    <div class="form-field full-width">
                        <label for="materiales">Materiales necesarios (opcional)</label>
                        <textarea id="materiales" name="materiales" rows="3"
                            placeholder="Ej. Bolsas de basura, guantes, palas."></textarea>
                    </div>

                    <div class="form-field full-width">
                        <label for="imagen">Imagen del voluntariado (opcional)</label>
                        <input type="file" id="imagen" name="imagen" accept="image/*">
                    </div>

                    <div class="form-field full-width">
                        <label for="descripcion">Descripción <span class="required">*</span></label>
                        <textarea id="descripcion" name="descripcion" required maxlength="3000" rows="4"
                            placeholder="Describe la actividad, qué se hará y por qué es importante para el entorno."></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="button button-primary" id="submitBtn">Enviar voluntariado</button>
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