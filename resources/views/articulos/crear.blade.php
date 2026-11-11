@extends('layouts.app')

@section('title', 'Crear artículo · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/create-voluntariado.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
@endpush

@section('content')
<main class="page">
    <section class="page-hero">
        <div class="page-hero-background"></div>
        <div class="page-hero-overlay"></div>
        <div class="page-hero-content">
            <span class="page-label">PUBLICA</span>
            <h1>Comparte un artículo</h1>
            <p>
                Escribe sobre iniciativas ambientales, conservación, turismo
                responsable o cualquier tema relacionado con Regenera BCS.
            </p>
        </div>
    </section>

    <section class="page-content">
        <div id="artAuthRequired" class="art-auth-required">
            <div class="art-auth-card">
                <div class="art-auth-heading">
                    <span class="art-auth-eyebrow">ACCESO REQUERIDO</span>
                    <h1>Inicia sesión para publicar un artículo</h1>
                    <p>
                        Para compartir un artículo necesitas una cuenta en Regenera BCS.
                        Si ya tienes una, inicia sesión. Si no, crea una cuenta nueva.
                    </p>
                </div>
                <div class="art-auth-actions">
                    <a href="{{ route('login', ['redirect' => url('/articulos/crear')]) }}" class="button button-primary">Iniciar sesión</a>
                    <a href="{{ route('login') }}" class="button button-secondary">Crear cuenta</a>
                </div>
            </div>
        </div>

        <div id="artCreateFormContainer" class="art-create-form-container" hidden>
            <div class="page-intro">
                <h2>Antes de comenzar</h2>
                <p>
                    Completa el formulario con tu artículo. Según tu perfil, quedará:
                </p>
                <ul class="art-info-list">
                    <li id="artInfoAdmin" hidden><strong>Administrador:</strong> se guarda como borrador para revisar y publicar desde el panel.</li>
                    <li id="artInfoUser" hidden><strong>Usuario u organizador:</strong> queda pendiente de aprobación administrativa.</li>
                </ul>
            </div>

            <form id="artCreateForm" class="art-create-form" novalidate>
                <div class="form-message" id="artCreateMessage" role="status" aria-live="polite"></div>

                <div class="dashboard-form create-form">
                    <div class="dashboard-form-heading">
                        <span class="dashboard-eyebrow">EDITOR</span>
                        <h3 data-form-title="article">Nuevo artículo</h3>
                        <button class="icon-button" type="button" data-cancel="article" aria-label="Cerrar formulario">×</button>
                    </div>

                    <div>
                        <label for="artTitulo">Título <span class="required">*</span></label>
                        <input type="text" id="artTitulo" name="titulo" required maxlength="200"
                            placeholder="Ej. Cómo reducir el plástico en tu vida diaria">
                    </div>

                    <div>
                        <label for="artCategoria">Categoría <span class="required">*</span></label>
                        <select id="artCategoria" name="categoria" required>
                            <option value="">Selecciona una categoría</option>
                            <option value="conservacion">Conservación</option>
                            <option value="turismo">Turismo responsable</option>
                            <option value="educacion">Educación ambiental</option>
                            <option value="iniciativa">Iniciativa comunitaria</option>
                        </select>
                    </div>

                    <div>
                        <label for="artContenido">Contenido <span class="required">*</span></label>
                        <textarea id="artContenido" name="contenido" required rows="8"
                            placeholder="Escribe el contenido completo de tu artículo..."></textarea>
                    </div>

                    <div>
                        <label for="artImagen">Imagen principal (opcional)</label>
                        <input type="file" id="artImagen" name="imagen" accept="image/*">
                    </div>

                    <div class="dashboard-form-actions">
                        <button type="button" class="button button-secondary" onclick="window.location.href='{{ url('/articulos') }}'">Cancelar</button>
                        <button type="submit" class="button button-primary" id="artSubmitBtn">Enviar artículo</button>
                    </div>
                </div>
            </form>
        </div>
    </section>
</main>

@push('scripts')
    <script>
        (function () {
            const authRequired = document.getElementById('artAuthRequired');
            const createFormContainer = document.getElementById('artCreateFormContainer');
            const infoAdmin = document.getElementById('artInfoAdmin');
            const infoUser = document.getElementById('artInfoUser');
            const form = document.getElementById('artCreateForm');
            const message = document.getElementById('artCreateMessage');
            const submitButton = document.getElementById('artSubmitBtn');

            form.addEventListener('submit', async event => {
                event.preventDefault();
                if (!form.reportValidity()) return;

                const token = sessionStorage.getItem('regenera_access_token');
                if (!token) {
                    authRequired.hidden = false;
                    createFormContainer.hidden = true;
                    return;
                }

                submitButton.disabled = true;
                submitButton.textContent = 'Enviando…';
                message.textContent = '';
                message.className = 'form-message';

                try {
                    const formData = new FormData(form);
                    if (!form.elements.imagen.files.length) formData.delete('imagen');
                    const response = await fetch('/api/articulos', {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            Authorization: `Bearer ${token}`,
                        },
                        body: formData,
                    });
                    const result = await response.json();
                    if (!response.ok) {
                        const validationMessage = Object.values(result.errors || {}).flat()[0];
                        throw new Error(validationMessage || result.message || 'No se pudo enviar el artículo.');
                    }

                    message.textContent = result.message || 'Artículo enviado correctamente.';
                    message.classList.add('is-success');
                    form.reset();
                } catch (error) {
                    message.textContent = error.message || 'No se pudo enviar el artículo.';
                    message.classList.add('is-error');
                } finally {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Enviar artículo';
                }
            });

            async function initializeArticleForm() {
                const token = sessionStorage.getItem('regenera_access_token');
                if (!token) {
                    authRequired.hidden = false;
                    createFormContainer.hidden = true;
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
                        authRequired.hidden = false;
                        createFormContainer.hidden = true;
                        return;
                    }

                    if (!response.ok) {
                        throw new Error('No se pudo verificar tu sesión. Intenta recargar la página.');
                    }

                    const result = await response.json();
                    const user = result.usuario;
                    if (!user || user.estado !== 'activo') {
                        sessionStorage.removeItem('regenera_access_token');
                        authRequired.hidden = false;
                        createFormContainer.hidden = true;
                        return;
                    }

                    const isAdmin = user.rol === 'admin';
                    infoAdmin.hidden = !isAdmin;
                    infoUser.hidden = isAdmin;
                    authRequired.hidden = true;
                    createFormContainer.hidden = false;
                } catch (error) {
                    authRequired.hidden = false;
                    createFormContainer.hidden = true;
                    authRequired.querySelector('.art-auth-heading p').textContent = error.message;
                }
            }

            document.addEventListener('regenera:auth-changed', initializeArticleForm);
            initializeArticleForm();
        })();
    </script>
@endpush