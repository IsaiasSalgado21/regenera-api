/**
 * Navegación global y acciones que requieren la sesión Sanctum.
 */
(function () {
    const token = () => sessionStorage.getItem("regenera_access_token");

    async function currentUser() {
        if (!token()) return null;

        const response = await fetch("/api/auth/me", {
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

        document.querySelectorAll(".nav, .mobile-menu").forEach(nav => {
            const loginLink = nav.querySelector("[data-auth-login]");
            if (loginLink) {
                const userLabel = document.createElement("span");
                userLabel.className = "user-nav";
                userLabel.dataset.authUser = "";
                userLabel.textContent = user.nombre;
                loginLink.replaceWith(userLabel);
            }

            const userLabel = nav.querySelector("[data-auth-user]");
            if (userLabel) userLabel.textContent = user.nombre;

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

    currentUser()
        .then(updateNavigation)
        .catch(error => console.error("No se pudo verificar la sesión.", error));

    document.addEventListener("click", async event => {
        const logoutLink = event.target.closest("[data-auth-logout]");
        if (logoutLink) {
            event.preventDefault();
            try {
                if (token()) {
                    const response = await fetch("/api/auth/logout", {
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
            window.location.assign(enrollmentLink.href);
            return;
        }

        const button = document.createElement("button");
        button.type = "button";
        button.className = enrollmentLink.className;
        button.dataset.voluntariadoId = enrollmentLink.dataset.voluntariadoId;
        button.textContent = enrollmentLink.textContent;
        enrollmentLink.replaceWith(button);
        const message = document.getElementById("enrollmentMessage");

        button.addEventListener("click", async () => {
            button.disabled = true;
            message.textContent = "";
            try {
                const response = await fetch("/api/inscripciones", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        Authorization: `Bearer ${token()}`,
                    },
                    body: JSON.stringify({ voluntariadoId: button.dataset.voluntariadoId }),
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || "No se pudo registrar la inscripción.");

                button.textContent = "Inscripción registrada";
                message.textContent = data.message || "Inscripción registrada correctamente.";
            } catch (error) {
                console.error("No se pudo completar la inscripción.", error);
                message.textContent = error.message;
                button.disabled = false;
            }
        });
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
