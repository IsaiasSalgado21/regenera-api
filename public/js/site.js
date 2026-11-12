/**
 * Navegación global y acciones que requieren la sesión Sanctum.
 */
(function () {
    const token = () => sessionStorage.getItem("regenera_access_token");
    const themeToggle = document.querySelector("[data-theme-toggle]");
    const apiBase = document.querySelector('meta[name="api-base-url"]')?.content || '';
    const appBase = apiBase.replace(/\/api\/?$/, '');
    const apiUrl = path => `${apiBase}${path}`;

    function learningUrl(quiz, returnPath) {
        const basePath = new URL(apiBase, window.location.origin).pathname.replace(/\/api\/?$/, '');
        const destination = new URL(`${basePath}/aprendizaje`, window.location.origin);
        destination.searchParams.set('cuestionario', quiz);
        if (returnPath) destination.searchParams.set('return', returnPath);
        return `${destination.pathname}${destination.search}`;
    }

    function setTheme(theme, save = false) {
        document.documentElement.dataset.theme = theme;

        if (themeToggle) {
            const isDark = theme === "dark";
            themeToggle.setAttribute("aria-label", isDark ? "Cambiar a modo claro" : "Cambiar a modo oscuro");
            themeToggle.setAttribute("aria-pressed", String(isDark));
            themeToggle.querySelector("[data-theme-icon]").textContent = isDark ? "☀" : "☾";
        }

        if (save) {
            const secure = window.location.protocol === "https:" ? "; Secure" : "";
            document.cookie = `regenera_theme=${theme}; Path=/; Max-Age=31536000; SameSite=Lax${secure}`;
        }
    }

    if (themeToggle) {
        setTheme(document.documentElement.dataset.theme || "light");
        themeToggle.addEventListener("click", () => {
            const nextTheme = document.documentElement.dataset.theme === "dark" ? "light" : "dark";
            setTheme(nextTheme, true);
        });
    }

    async function currentUser() {
        if (!token()) return null;

        const response = await fetch(apiUrl("/auth/me"), {
            headers: {
                Accept: "application/json",
                Authorization: `Bearer ${token()}`,
            },
        });
        if (response.status === 401) {
            sessionStorage.removeItem("regenera_access_token");
            return null;
        }
        if (!response.ok) throw new Error("No se pudo verificar la sesión.");

        const data = await response.json();
        return data.usuario || null;
    }

    function updateNavigation(user) {
        if (!user) return;

        function setUserLabel(userLabel) {
            userLabel.textContent = user.nombre;
            if (user.rol !== "organizador" || !user.organizadorVerificado) return;

            const badge = document.createElement("span");
            badge.className = "verified-organizer-mark";
            badge.textContent = "✓";
            badge.setAttribute("aria-label", "Organizador verificado");
            badge.title = "Organizador verificado";
            userLabel.append(" ", badge);
        }

        document.querySelectorAll(".nav, .mobile-menu").forEach(nav => {
            nav.querySelectorAll("[data-auth-required]").forEach(link => { link.hidden = false; });
            const existingDashboard = nav.querySelector("[data-auth-dashboard]");
            if (existingDashboard) existingDashboard.remove();

            if (user.rol === "admin" || user.rol === "organizador") {
                const dashboardLink = document.createElement("a");
                dashboardLink.href = `${appBase}${user.rol === "admin" ? "/admin/dashboard" : "/organizador/dashboard"}`;
                dashboardLink.className = "user-nav";
                dashboardLink.dataset.authDashboard = "";
                dashboardLink.textContent = "Panel";
                const logoutLink = nav.querySelector("[data-auth-logout]");
                nav.insertBefore(dashboardLink, logoutLink || null);
            }

            const loginLink = nav.querySelector("[data-auth-login]");
            if (loginLink) {
                const userLabel = document.createElement("a");
                userLabel.href = "/perfil";
                userLabel.className = "user-nav";
                userLabel.dataset.authUser = "";
                setUserLabel(userLabel);
                loginLink.replaceWith(userLabel);
            }

            let userLabel = nav.querySelector("[data-auth-user]");
            if (userLabel) {
                if (userLabel.tagName !== "A") {
                    const profileLink = document.createElement("a");
                    profileLink.href = "/perfil";
                    profileLink.className = userLabel.className;
                    profileLink.dataset.authUser = "";
                    userLabel.replaceWith(profileLink);
                    userLabel = profileLink;
                }
                setUserLabel(userLabel);
            }

            if (!nav.querySelector("[data-auth-logout]")) {
                const logoutLink = document.createElement("a");
                logoutLink.href = "#";
                logoutLink.className = "user-nav";
                logoutLink.dataset.authLogout = "";
                logoutLink.textContent = "Salir";
                nav.appendChild(logoutLink);
            }
        });
    }

    function clearEnrollmentReturnFlag() {
        const url = new URL(window.location.href);
        if (!url.searchParams.has('participar')) return;
        url.searchParams.delete('participar');
        window.history.replaceState(null, '', `${url.pathname}${url.search}${url.hash}`);
    }

    async function submitEnrollment(button) {
        if (button.disabled) return;
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        const originalLabel = button.textContent;
        const message = document.getElementById('enrollmentMessage');
        if (message) message.textContent = 'Verificando tu inscripción…';

        try {
            const volunteerId = button.dataset.voluntariadoId;
            const statusResponse = await fetch(apiUrl(`/inscripciones/voluntariado/${encodeURIComponent(volunteerId)}/mia`), {
                headers: {
                    Accept: 'application/json',
                    Authorization: `Bearer ${token()}`,
                },
            });
            const enrollment = await statusResponse.json().catch(() => ({}));
            if (!statusResponse.ok) {
                const error = new Error(enrollment.message || 'No se pudo verificar tu inscripción.');
                error.status = statusResponse.status;
                throw error;
            }
            if (enrollment.inscrito) {
                button.textContent = 'Inscripción registrada';
                button.removeAttribute('aria-busy');
                if (message) message.textContent = 'Ya tienes una inscripción activa para este voluntariado.';
                document.dispatchEvent(new CustomEvent('regenera:inscription-created', {
                    detail: {
                        voluntariadoId: volunteerId,
                        inscripcionId: enrollment.inscripcionId || '',
                        alreadyEnrolled: true,
                    },
                }));
                return;
            }

            if (message) message.textContent = 'Registrando tu participación…';
            const response = await fetch(apiUrl('/inscripciones'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    Authorization: `Bearer ${token()}`,
                },
                body: JSON.stringify({ voluntariadoId: button.dataset.voluntariadoId }),
            });
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const error = new Error(data.message || 'No se pudo registrar la inscripción.');
                error.status = response.status;
                error.data = data;
                throw error;
            }

            button.textContent = 'Inscripción registrada';
            button.removeAttribute('aria-busy');
            if (message) message.textContent = data.message || 'Inscripción registrada correctamente.';
            clearEnrollmentReturnFlag();
            document.dispatchEvent(new CustomEvent('regenera:inscription-created', {
                detail: {
                    voluntariadoId: volunteerId,
                    inscripcionId: data.inscripcion?.id || data.inscripcion?._id || '',
                },
            }));
        } catch (error) {
            console.error('No se pudo completar la inscripción.', error);
            if (error.data?.code === 'cuestionario_seguridad_requerido') {
                window.location.assign(learningUrl(
                    error.data.cuestionario || 'seguridad-limpieza',
                    `${window.location.pathname}${window.location.search}${window.location.hash}`,
                ));
                return;
            }
            if (message) message.textContent = error.message || 'No se pudo completar la inscripción.';
            if (error.status === 401) {
                sessionStorage.removeItem('regenera_access_token');
                document.dispatchEvent(new CustomEvent('regenera:auth-required', {
                    detail: {
                        mode: 'login',
                        intent: { type: 'enrollment', voluntariadoId: button.dataset.voluntariadoId },
                    },
                }));
            }
            button.disabled = false;
            button.textContent = originalLabel;
            button.removeAttribute('aria-busy');
        }
    }

    function beginEnrollment(link) {
        if (!link || link.dataset.enrollmentPending === 'true') return;
        const button = document.createElement('button');
        button.type = 'button';
        button.className = link.className;
        button.dataset.voluntariadoId = link.dataset.voluntariadoId;
        button.dataset.enrollmentPending = 'true';
        button.textContent = link.textContent.trim();
        link.replaceWith(button);
        button.addEventListener('click', () => submitEnrollment(button));
        void submitEnrollment(button);
    }

    document.addEventListener("regenera:auth-changed", event => {
        const user = event.detail?.user;
        if (!user) return;
        updateNavigation(user);

        if (event.detail?.intent?.type === "enrollment") {
            const control = [...document.querySelectorAll("[data-voluntariado-id]")]
                .find(item => item.dataset.voluntariadoId === event.detail.intent.voluntariadoId);
            if (control?.tagName === "A") beginEnrollment(control);
            else if (control?.tagName === "BUTTON") void submitEnrollment(control);
        }
    });

    currentUser()
        .then(user => {
            updateNavigation(user);
            const returningToParticipate = new URLSearchParams(window.location.search).get('participar') === '1';
            if (!user || !returningToParticipate) return;
            const link = document.querySelector('[data-voluntariado-id]');
            if (link?.tagName === 'A') beginEnrollment(link);
            else clearEnrollmentReturnFlag();
        })
        .catch(error => console.error("No se pudo verificar la sesión.", error));

    document.addEventListener("click", async event => {
        const logoutLink = event.target.closest("[data-auth-logout]");
        if (logoutLink) {
            event.preventDefault();
            try {
                if (token()) {
                    const response = await fetch(apiUrl("/auth/logout"), {
                        method: "POST",
                        headers: {
                            Accept: "application/json",
                            Authorization: `Bearer ${token()}`,
                        },
                    });
                    if (!response.ok) throw new Error("No se pudo cerrar la sesión en el servidor.");
                }
                sessionStorage.removeItem("regenera_access_token");
                window.location.assign("/");
            } catch (error) {
                console.error(error);
                logoutLink.setAttribute("aria-label", error.message);
            }
            return;
        }

        const enrollmentLink = event.target.closest("[data-voluntariado-id]");
        if (enrollmentLink?.tagName !== "A") return;

        event.preventDefault();
        const accessToken = token();
        if (!accessToken) {
            document.dispatchEvent(new CustomEvent("regenera:auth-required", {
                detail: {
                    mode: "login",
                    intent: {
                        type: "enrollment",
                        voluntariadoId: enrollmentLink.dataset.voluntariadoId,
                    },
                },
            }));
            return;
        }
        beginEnrollment(enrollmentLink);
    });

    const menuButton = document.getElementById("menuButton");
    const mobileMenu = document.getElementById("mobileMenu");
    if (!menuButton || !mobileMenu) return;

    menuButton.addEventListener("click", function (event) {
        event.stopPropagation();
        mobileMenu.classList.toggle("active");
    });

    document.addEventListener("click", function (event) {
        if (!mobileMenu.contains(event.target) && event.target !== menuButton) {
            mobileMenu.classList.remove("active");
        }
    });
})();
