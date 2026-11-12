(() => {
    const modal = document.querySelector('[data-auth-modal]');
    const apiUrl = path => `${document.querySelector('meta[name="api-base-url"]')?.content || ''}${path}`;
    let lastFocusedElement = null;
    let authIntent = null;

    const safeLocalPath = value => {
        if (!value || !value.startsWith('/') || value.startsWith('//')) return null;
        const url = new URL(value, window.location.origin);
        if (url.origin !== window.location.origin || url.pathname === '/login') return null;
        return `${url.pathname}${url.search}${url.hash}`;
    };

    function setMessage(element, text, type = '') {
        element.textContent = text || '';
        element.className = `auth-message${type ? ` is-${type}` : ''}`;
    }

    function setMode(widget, mode) {
        const registering = mode === 'register';
        widget.dataset.authModeCurrent = mode;
        widget.querySelectorAll('[data-auth-mode]').forEach(button => {
            const active = button.dataset.authMode === mode;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', String(active));
        });
        widget.querySelectorAll('[data-auth-panel]').forEach(panel => {
            const active = panel.dataset.authPanel === mode;
            panel.classList.toggle('is-active', active);
            panel.setAttribute('aria-hidden', String(!active));
        });

        const content = registering
            ? {
                eyebrow: 'FORMA PARTE DE LA COMUNIDAD',
                title: 'Crea tu cuenta',
                description: 'Un pequeño paso para sumarte a iniciativas que regeneran nuestro entorno.',
            }
            : {
                eyebrow: 'QUÉ BUENO TENERTE DE VUELTA',
                title: 'Inicia sesión',
                description: 'Accede para continuar con tus actividades en Regenera BCS.',
            };
        widget.querySelector('[data-auth-eyebrow]').textContent = content.eyebrow;
        widget.querySelector('[data-auth-title]').textContent = content.title;
        widget.querySelector('[data-auth-description]').textContent = content.description;
        widget.querySelectorAll('[data-auth-message]').forEach(message => setMessage(message, ''));
    }

    function openModal(mode = 'login') {
        if (!modal) return false;
        lastFocusedElement = document.activeElement;
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('auth-modal-open');
        const widget = modal.querySelector('[data-auth-widget]');
        setMode(widget, mode);
        window.requestAnimationFrame(() => widget.querySelector(`[data-auth-panel="${mode}"] input`)?.focus());
        return true;
    }

    function closeModal() {
        if (!modal || modal.hidden) return;
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('auth-modal-open');
        document.getElementById('mobileMenu')?.classList.remove('active');
        lastFocusedElement?.focus();
        authIntent = null;
    }

    async function callApi(mode, payload) {
        const response = await fetch(apiUrl(`/auth/${mode === 'register' ? 'register' : 'login'}`), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(payload),
        });

        let data = {};
        try {
            data = await response.json();
        } catch {
            throw new Error('El servidor devolvió una respuesta que no se pudo interpretar.');
        }

        if (!response.ok || data.success === false) {
            const validationError = Object.values(data.errors || {}).flat()[0];
            const error = new Error(validationError || data.message || 'No se pudo completar la solicitud.');
            error.status = response.status;
            throw error;
        }
        return data;
    }

    function showApiError(error) {
        if (error.status === 401) return 'El correo o la contraseña no coinciden.';
        if (error.status === 403) return error.message || 'No es posible acceder con esta cuenta.';
        if (error.status === 409) return 'Ya existe una cuenta con ese correo.';
        if (error.status === 422) return error.message || 'Revisa los datos ingresados.';
        return error.status ? error.message || 'No se pudo completar la solicitud.' : 'No se pudo conectar con el servidor. Intenta de nuevo.';
    }

    function standaloneDestination() {
        const requested = safeLocalPath(new URLSearchParams(window.location.search).get('redirect'));
        if (requested) return requested;

        try {
            const referrer = new URL(document.referrer);
            if (referrer.origin === window.location.origin) {
                const path = safeLocalPath(`${referrer.pathname}${referrer.search}${referrer.hash}`);
                if (path) return path;
            }
        } catch {
            // No referrer is available, so use the account page as a safe destination.
        }
        return '/perfil';
    }

    async function submitForm(form) {
        const widget = form.closest('[data-auth-widget]');
        const mode = form.dataset.authForm;
        const message = form.querySelector('[data-auth-message]');
        const submit = form.querySelector('[data-auth-submit]');
        const label = submit.querySelector('[data-auth-submit-label]');
        const values = new FormData(form);
        const payload = {
            correo: String(values.get('correo') || '').trim(),
            password: String(values.get('password') || ''),
        };

        if (!form.reportValidity()) return;
        if (mode === 'register') {
            payload.nombre = String(values.get('nombre') || '').trim();
            payload.password_confirmation = String(values.get('password_confirmation') || '');
            if (payload.password !== payload.password_confirmation) {
                setMessage(message, 'Las contraseñas no coinciden.', 'error');
                form.elements.password_confirmation.focus();
                return;
            }
        }

        const originalLabel = mode === 'register' ? 'Crear mi cuenta' : 'Iniciar sesión';
        submit.disabled = true;
        submit.classList.add('is-loading');
        submit.setAttribute('aria-busy', 'true');
        label.textContent = mode === 'register' ? 'Creando tu cuenta…' : 'Verificando…';
        setMessage(message, '');

        try {
            const data = await callApi(mode, payload);
            if (typeof data.token !== 'string' || !data.token) {
                throw new Error('El servidor no devolvió un token de acceso.');
            }
            sessionStorage.setItem('regenera_access_token', data.token);
            setMessage(message, data.message || 'Acceso correcto.', 'success');

            const user = data.usuario || {};
            if (widget.closest('[data-auth-modal]')) {
                const completedIntent = authIntent;
                if (!modal.hidden) closeModal();
                document.dispatchEvent(new CustomEvent('regenera:auth-changed', {
                    detail: { user, intent: completedIntent },
                }));
                authIntent = null;
            } else {
                document.dispatchEvent(new CustomEvent('regenera:auth-changed', {
                    detail: { user },
                }));
                window.setTimeout(() => window.location.assign(standaloneDestination()), 450);
            }
        } catch (error) {
            setMessage(message, showApiError(error), 'error');
        } finally {
            submit.disabled = false;
            submit.classList.remove('is-loading');
            submit.removeAttribute('aria-busy');
            label.textContent = originalLabel;
        }
    }

    document.querySelectorAll('[data-auth-widget]').forEach(widget => {
        widget.querySelectorAll('[data-auth-mode]').forEach(button => {
            button.addEventListener('click', () => setMode(widget, button.dataset.authMode));
        });
        widget.querySelectorAll('[data-auth-form]').forEach(form => {
            form.addEventListener('submit', event => {
                event.preventDefault();
                submitForm(form);
            });
        });
        widget.querySelectorAll('[data-auth-password-toggle]').forEach(button => {
            button.addEventListener('click', () => {
                const input = button.parentElement.querySelector('input');
                const visible = input.type === 'password';
                input.type = visible ? 'text' : 'password';
                button.textContent = visible
                    ? 'Ocultar'
                    : (input.closest('[data-auth-panel="login"]') ? 'Mostrar' : 'Ver');
                button.setAttribute('aria-label', `${visible ? 'Ocultar' : 'Mostrar'} contraseña`);
                button.setAttribute('aria-pressed', String(visible));
            });
        });
    });

    document.addEventListener('click', event => {
        const closeButton = event.target.closest('[data-auth-close]');
        if (closeButton) {
            closeModal();
            return;
        }

        const loginLink = event.target.closest('a[href]');
        if (!loginLink || event.defaultPrevented || !modal) return;
        const url = new URL(loginLink.href, window.location.origin);
        if (url.origin !== window.location.origin || url.pathname !== '/login') return;

        event.preventDefault();
        const mode = url.searchParams.get('mode') === 'register' ? 'register' : 'login';
        authIntent = null;
        openModal(mode);
    });

    document.addEventListener('regenera:auth-required', event => {
        authIntent = event.detail?.intent || null;
        openModal(event.detail?.mode || 'login');
    });

    document.addEventListener('keydown', event => {
        if (!modal || modal.hidden) return;
        if (event.key === 'Escape') {
            closeModal();
            return;
        }
        if (event.key !== 'Tab') return;
        const focusable = [...modal.querySelectorAll('button:not(:disabled):not(.auth-modal-backdrop), input:not(:disabled), a[href]')]
            .filter(element => element.offsetParent !== null);
        if (!focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });
})();
