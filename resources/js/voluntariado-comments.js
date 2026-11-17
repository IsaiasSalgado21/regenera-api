import axios from 'axios';
import { apiUrl } from './api-url';

const section = document.querySelector('[data-volunteer-comments]');

if (section) {
    const volunteerId = section.dataset.volunteerId;
    const list = section.querySelector('[data-comments-list]');
    const status = section.querySelector('[data-comments-status]');
    const login = section.querySelector('[data-comments-login]');
    const retry = section.querySelector('[data-comments-retry]');
    const composer = section.querySelector('[data-comments-composer]');
    const createForm = section.querySelector('[data-comment-create]');
    const createInput = createForm.querySelector('[name="contenido"]');
    const createPrivate = createForm.querySelector('[name="es_privado"]');
    const createPrivateHelp = createForm.querySelector('[data-private-help]');
    const createError = createForm.querySelector('[data-comment-error]');
    const createCounter = createForm.querySelector('[data-comment-count]');
    let activeReplyId = null;
    let comments = [];

    function accessToken() {
        return sessionStorage.getItem('regenera_access_token');
    }

    function headers() {
        const currentToken = accessToken();
        return {
            Accept: 'application/json',
            ...(currentToken ? { Authorization: `Bearer ${currentToken}` } : {}),
        };
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, character => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        }[character]));
    }

    function roleLabel(role) {
        return ({
            usuario: 'Voluntario',
            voluntario: 'Voluntario',
            organizador: 'Organizador',
            admin: 'Administrador',
        })[role] || 'Participante';
    }

    function dateLabel(value) {
        if (!value) return 'Fecha no disponible';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return 'Fecha no disponible';
        return new Intl.DateTimeFormat('es-MX', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }).format(date);
    }

    function errorMessage(error) {
        const statusCode = error.response?.status;
        if (statusCode === 401) return 'Tu sesión expiró. Inicia sesión de nuevo para continuar.';
        if (statusCode === 403) return 'No tienes permiso para realizar esta acción.';
        if (statusCode === 422) {
            return Object.values(error.response?.data?.errors || {}).flat()[0]
                || 'Revisa el comentario e inténtalo de nuevo.';
        }
        return error.response?.data?.message || 'No se pudo completar la solicitud. Intenta de nuevo.';
    }

    function showLogin(message) {
        composer.hidden = true;
        login.hidden = false;
        status.textContent = message;
        list.replaceChildren();
        retry.hidden = true;
    }

    function renderLoading() {
        list.innerHTML = `
            <div class="comment-skeleton" aria-hidden="true"></div>
            <div class="comment-skeleton" aria-hidden="true"></div>
            <div class="comment-skeleton" aria-hidden="true"></div>
        `;
    }

    function renderReplyForm(comment) {
        if (activeReplyId !== String(comment.id)) return '';
        const inputId = `comment-reply-${escapeHtml(comment.id)}`;
        return `
            <form class="comment-reply-form" data-comment-reply="${escapeHtml(comment.id)}">
                <label for="${inputId}">Responder a ${escapeHtml(comment.autor?.nombre || 'este comentario')}</label>
                <textarea id="${inputId}" name="contenido" maxlength="1000" required aria-describedby="${inputId}-error" data-reply-input></textarea>
                <div class="comment-form-meta">
                    <span data-reply-count>0 / 1000</span>
                    <span class="comment-form-error" id="${inputId}-error" data-reply-error role="alert" hidden></span>
                </div>
                <div class="comment-actions">
                    <button class="button button-primary" type="submit">Enviar respuesta</button>
                    <button class="button button-secondary" type="button" data-comment-action="cancel-reply">Cancelar</button>
                </div>
            </form>
        `;
    }

    function renderComment(comment) {
        const privateBadge = comment.es_privado
            ? '<span class="comment-visibility comment-visibility--private"><span aria-hidden="true">🔒</span> Duda privada</span>'
            : '';
        const hiddenBadge = !comment.es_publicado && !comment.es_privado
            ? '<span class="comment-visibility comment-visibility--hidden">Oculto</span>'
            : '';
        const actions = [
            comment.puede_moderar && !comment.es_privado
                ? `<button type="button" class="button button-secondary" data-comment-action="visibility" data-comment-id="${escapeHtml(comment.id)}">${comment.es_publicado ? 'Ocultar' : 'Mostrar'}</button>`
                : '',
            comment.puede_publicar
                ? `<button type="button" class="button button-secondary" data-comment-action="publish" data-comment-id="${escapeHtml(comment.id)}">Publicar duda</button>`
                : '',
            comment.puede_moderar
                ? `<button type="button" class="button button-secondary" data-comment-action="reply" data-comment-id="${escapeHtml(comment.id)}">Responder</button>`
                : '',
            comment.puede_eliminar
                ? `<button type="button" class="button button-danger" data-comment-action="delete" data-comment-id="${escapeHtml(comment.id)}">Eliminar</button>`
                : '',
        ].filter(Boolean).join('');

        return `
            <article class="comment-card" data-comment-id="${escapeHtml(comment.id)}">
                <header class="comment-card-heading">
                    <p class="comment-author">${escapeHtml(comment.autor?.nombre || 'Participante')} <span>· ${escapeHtml(roleLabel(comment.autor?.rol))}</span></p>
                    <time datetime="${escapeHtml(comment.created_at || '')}">${escapeHtml(dateLabel(comment.created_at))}</time>
                </header>
                <div class="comment-card-badges">${privateBadge}${hiddenBadge}</div>
                <p class="comment-content">${escapeHtml(comment.contenido)}</p>
                ${actions ? `<div class="comment-actions">${actions}</div>` : ''}
                ${renderReplyForm(comment)}
            </article>
        `;
    }

    function renderComments() {
        comments.sort((first, second) => new Date(first.created_at) - new Date(second.created_at));
        if (!comments.length) {
            list.innerHTML = '<p class="comment-empty">Aún no hay comentarios.</p>';
            return;
        }
        list.innerHTML = comments.map(renderComment).join('');
    }

    function announceError(error, field = null) {
        const message = errorMessage(error);
        if (error.response?.status === 401) {
            sessionStorage.removeItem('regenera_access_token');
            showLogin(message);
            return;
        }
        if (error.response?.status === 404) {
            status.textContent = 'El comentario ya no está disponible. Actualizando la lista…';
            loadComments().then(loaded => {
                if (loaded) status.textContent = 'La lista se actualizó.';
            });
            return;
        }
        if (field) {
            field.textContent = message;
            field.hidden = false;
        } else {
            status.textContent = message;
        }
    }

    async function loadComments() {
        const currentToken = accessToken();
        if (!currentToken) {
            showLogin('Inicia sesión para ver y publicar comentarios.');
            return false;
        }

        status.textContent = 'Cargando comentarios…';
        retry.hidden = true;
        login.hidden = true;
        renderLoading();

        try {
            const { data } = await axios.get(
                apiUrl(`/api/voluntariados/${encodeURIComponent(volunteerId)}/comentarios`),
                { headers: headers() },
            );
            comments = Array.isArray(data.data) ? data.data : [];
            renderComments();
            status.textContent = '';
            return true;
        } catch (error) {
            list.replaceChildren();
            if (error.response?.status === 401) {
                sessionStorage.removeItem('regenera_access_token');
                showLogin(errorMessage(error));
            } else {
                status.textContent = errorMessage(error);
                retry.hidden = false;
            }
            return false;
        }
    }

    async function refreshAfterAction(message) {
        activeReplyId = null;
        const loaded = await loadComments();
        if (loaded) status.textContent = message;
    }

    createInput.addEventListener('input', () => {
        createCounter.textContent = `${createInput.value.length} / 1000`;
    });

    createForm.addEventListener('submit', async event => {
        event.preventDefault();
        createError.hidden = true;
        createError.textContent = '';
        const content = createInput.value.trim();
        if (!content || content.length > 1000) {
            createError.textContent = !content
                ? 'Escribe un comentario antes de enviarlo.'
                : 'El comentario no puede superar los 1000 caracteres.';
            createError.hidden = false;
            createInput.focus();
            return;
        }

        const submit = createForm.querySelector('[type="submit"]');
        submit.disabled = true;
        try {
            await axios.post(
                apiUrl(`/api/voluntariados/${encodeURIComponent(volunteerId)}/comentarios`),
                {
                    contenido: content,
                    es_privado: createPrivate.checked,
                },
                { headers: headers() },
            );
            createForm.reset();
            createCounter.textContent = '0 / 1000';
            await refreshAfterAction('Tu comentario se envió correctamente.');
        } catch (error) {
            announceError(error, createError);
        } finally {
            submit.disabled = false;
        }
    });

    list.addEventListener('input', event => {
        if (event.target.matches('[data-reply-input]')) {
            const form = event.target.closest('[data-comment-reply]');
            form.querySelector('[data-reply-count]').textContent = `${event.target.value.length} / 1000`;
        }
    });

    list.addEventListener('submit', async event => {
        const form = event.target.closest('[data-comment-reply]');
        if (!form) return;
        event.preventDefault();
        const input = form.querySelector('[data-reply-input]');
        const errorNode = form.querySelector('[data-reply-error]');
        const content = input.value.trim();
        errorNode.hidden = true;
        if (!content || content.length > 1000) {
            errorNode.textContent = !content
                ? 'Escribe una respuesta antes de enviarla.'
                : 'La respuesta no puede superar los 1000 caracteres.';
            errorNode.hidden = false;
            input.focus();
            return;
        }

        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        try {
            await axios.post(
                apiUrl(`/api/comentarios/${encodeURIComponent(form.dataset.commentReply)}/responder`),
                { contenido: content },
                { headers: headers() },
            );
            await refreshAfterAction('La respuesta se envió correctamente.');
        } catch (error) {
            announceError(error, errorNode);
        } finally {
            submit.disabled = false;
        }
    });

    list.addEventListener('click', async event => {
        const button = event.target.closest('[data-comment-action]');
        if (!button) return;
        const action = button.dataset.commentAction;
        const commentId = button.dataset.commentId;

        if (action === 'cancel-reply') {
            activeReplyId = null;
            renderComments();
            return;
        }
        if (action === 'reply') {
            activeReplyId = commentId;
            renderComments();
            list.querySelector('[data-reply-input]')?.focus();
            return;
        }
        if (action === 'delete' && !window.confirm('¿Eliminar este comentario? Esta acción no se puede deshacer.')) {
            return;
        }

        button.disabled = true;
        try {
            if (action === 'visibility') {
                const comment = comments.find(item => String(item.id) === String(commentId));
                await axios.patch(
                    apiUrl(`/api/comentarios/${encodeURIComponent(commentId)}/visibilidad`),
                    { es_publicado: !comment?.es_publicado },
                    { headers: headers() },
                );
                await refreshAfterAction(comment?.es_publicado ? 'Comentario ocultado.' : 'Comentario publicado.');
            } else if (action === 'publish') {
                await axios.patch(
                    apiUrl(`/api/comentarios/${encodeURIComponent(commentId)}/publicar`),
                    {},
                    { headers: headers() },
                );
                await refreshAfterAction('La duda se publicó correctamente.');
            } else if (action === 'delete') {
                await axios.delete(
                    apiUrl(`/api/comentarios/${encodeURIComponent(commentId)}`),
                    { headers: headers() },
                );
                await refreshAfterAction('El comentario se eliminó.');
            }
        } catch (error) {
            announceError(error);
        } finally {
            button.disabled = false;
        }
    });

    async function initialize() {
        const currentToken = accessToken();
        if (!currentToken) {
            showLogin('Inicia sesión para ver y publicar comentarios.');
            return;
        }

        status.textContent = 'Verificando tu sesión…';
        retry.hidden = true;
        composer.hidden = true;
        login.hidden = true;
        try {
            const { data } = await axios.get(apiUrl('/api/auth/me'), { headers: headers() });
            const role = data.usuario?.rol;
            if (role === 'usuario' || role === 'voluntario') {
                composer.hidden = false;
                const privateOnly = section.dataset.commentsDefault === 'privado';
                createPrivate.defaultChecked = privateOnly;
                createPrivate.checked = privateOnly;
                createPrivate.disabled = privateOnly;
                createPrivateHelp.hidden = !privateOnly;
                if (privateOnly) {
                    createPrivateHelp.textContent = 'Este voluntariado recibe solo dudas privadas.';
                }
            }
            await loadComments();
        } catch (error) {
            if (error.response?.status === 401) {
                sessionStorage.removeItem('regenera_access_token');
                showLogin(errorMessage(error));
                return;
            }
            status.textContent = errorMessage(error);
            retry.hidden = false;
        }
    }

    retry.addEventListener('click', initialize);
    initialize();
}
