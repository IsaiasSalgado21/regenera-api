/**
 * Comportamiento global del sitio.
 * - Toggle del menú móvil.
 */
(function () {
    function getStoredUser() {
        try {
            const storedUser = localStorage.getItem("regenera_usuario");
            if (!storedUser) return null;

            const user = JSON.parse(storedUser);
            if (!user || typeof user.id !== "string" || !user.id || typeof user.nombre !== "string") {
                return null;
            }

            return user;
        } catch (error) {
            console.error("No se pudo leer el usuario guardado.", error);
            return null;
        }
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

    updateNavigation(getStoredUser());

    document.addEventListener("click", event => {
        const logoutLink = event.target.closest("[data-auth-logout]");
        if (!logoutLink) return;

        event.preventDefault();
        try {
            localStorage.removeItem("regenera_usuario");
            localStorage.removeItem("regenera_sesion");
        } catch (error) {
            console.error("No se pudo cerrar la sesión guardada.", error);
            return;
        }
        window.location.assign("/");
    });

    const volunteerCta = document.querySelector("[data-voluntariado-id]");
    if (volunteerCta) {
        const user = getStoredUser();
        if (user) {
            const enrollButton = document.createElement("button");
            enrollButton.type = "button";
            enrollButton.className = volunteerCta.className;
            enrollButton.dataset.voluntariadoId = volunteerCta.dataset.voluntariadoId;
            enrollButton.textContent = volunteerCta.textContent;
            volunteerCta.replaceWith(enrollButton);

            const message = document.getElementById("enrollmentMessage");
            enrollButton.addEventListener("click", async () => {
                enrollButton.disabled = true;
                message.textContent = "";
                try {
                    const response = await fetch("/api/inscripciones", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "Accept": "application/json",
                        },
                        body: JSON.stringify({
                            usuarioId: user.id,
                            voluntariadoId: enrollButton.dataset.voluntariadoId,
                        }),
                    });
                    const data = await response.json();
                    if (!response.ok || data.success === false) {
                        throw new Error(data.message || "No se pudo registrar la inscripción.");
                    }

                    enrollButton.textContent = "Inscripción registrada";
                    message.textContent = data.message || "Inscripción registrada correctamente.";
                } catch (error) {
                    console.error("No se pudo completar la inscripción.", error);
                    message.textContent = error.message || "No se pudo conectar con el servidor.";
                    enrollButton.disabled = false;
                }
            });
        }
    }

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