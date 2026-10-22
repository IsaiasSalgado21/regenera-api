/**
 * Login / Registro conectado al backend Laravel (regenera-api)
 *
 * Endpoints (ya definidos en routes/api.php):
 *   POST /api/auth/register  -> { nombre, correo, password, password_confirmation }
 *   POST /api/auth/login     -> { correo, password }
 *
 * Respuestas esperadas:
 *   { success: true,  message, usuario: { id, nombre, correo, rol, estado } }
 *   { success: false, message, errors? }
 *
 * Nota: como las rutas viven en routes/api.php, NO requieren CSRF.
 */

const API = "/api";

const form         = document.getElementById("loginForm");
const nameField    = document.querySelector(".auth-name-field");
const nameInput    = document.getElementById("nombre");
const correoInput  = document.getElementById("correo");
const passwordInput= document.getElementById("password");
const submitButton = document.getElementById("authSubmit");
const message      = document.getElementById("authMessage");
const passwordToggle = document.getElementById("togglePassword");

let mode = "login"; // "login" | "register"

function setMessage(text, type = "") {
    message.textContent = text || "";
    message.className = "auth-message" + (type ? " is-" + type : "");
}

function setLoading(loading) {
    submitButton.disabled = loading;
    submitButton.textContent = loading
        ? "Procesando…"
        : (mode === "register" ? "Crear cuenta →" : "Iniciar sesión →");
}

function setMode(nextMode) {
    mode = nextMode;
    const registering = mode === "register";
    nameField.hidden = !registering;
    nameInput.required = registering;
    passwordInput.autocomplete = registering ? "new-password" : "current-password";
    submitButton.textContent = registering ? "Crear cuenta →" : "Iniciar sesión →";
    setMessage("");
    document.querySelectorAll("[data-auth-mode]").forEach(button => {
        const active = button.dataset.authMode === mode;
        button.classList.toggle("active", active);
        button.setAttribute("aria-selected", String(active));
    });
}

// Mostrar / ocultar contraseña
passwordToggle.addEventListener("click", () => {
    const visible = passwordInput.type === "password";
    passwordInput.type = visible ? "text" : "password";
    passwordToggle.textContent = visible ? "Ocultar" : "Mostrar";
    passwordToggle.setAttribute("aria-label", `${visible ? "Ocultar" : "Mostrar"} contraseña`);
    passwordToggle.setAttribute("aria-pressed", String(visible));
});

// Tabs
document.querySelectorAll("[data-auth-mode]").forEach(button => {
    button.addEventListener("click", () => setMode(button.dataset.authMode));
});

async function callApi(path, payload) {
    const response = await fetch(API + path, {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Accept": "application/json",
        },
        body: JSON.stringify(payload),
    });

    let data = {};
    try { data = await response.json(); } catch (_) { /* respuesta vacía */ }

    return {
        ok: response.ok && data.success !== false,
        status: response.status,
        data,
    };
}

function mapRegisterError(errors) {
    if (!errors) return null;
    // Laravel devuelve errors.{campo}: [mensaje, ...]
    if (errors.correo)    return "El correo ya está registrado o no es válido.";
    if (errors.password)  return "La contraseña debe tener al menos 8 caracteres.";
    if (errors.nombre)    return "Ingresa tu nombre.";
    return Object.values(errors)[0]?.[0] || null;
}

form.addEventListener("submit", async event => {
    event.preventDefault();
    setMessage("");

    if (!form.reportValidity()) return;
    if (mode === "register" && !nameInput.value.trim()) {
        setMessage("Ingresa tu nombre.", "error");
        return;
    }

    setLoading(true);
    try {
        const correo   = correoInput.value.trim();
        const password = passwordInput.value;

        let result;
        if (mode === "register") {
            // El backend pide password_confirmation; la reusamos como el mismo valor.
            result = await callApi("/auth/register", {
                nombre: nameInput.value.trim(),
                correo,
                password,
                password_confirmation: password,
            });
        } else {
            result = await callApi("/auth/login", { correo, password });
        }

        if (!result.ok) {
            const friendly = mapRegisterError(result.data.errors) || result.data.message;
            setMessage(friendly || "No se pudo completar la solicitud.", "error");
            return;
        }

        // Guardamos el usuario devuelto por el backend para usarlo en otras vistas.
        try {
            localStorage.setItem("regenera_usuario", JSON.stringify(result.data.usuario));
            localStorage.setItem("regenera_sesion", JSON.stringify({
                usuario_id: result.data.usuario.id,
                fechaInicio: new Date().toISOString(),
            }));
        } catch (_) { /* localStorage no disponible */ }

        setMessage(result.data.message || "¡Bienvenido!", "success");

        // Redirección: al inicio tras iniciar sesión o crear la cuenta.
        setTimeout(() => { window.location.href = "/"; }, 600);
    } catch (error) {
        console.error(error);
        setMessage(
            "No se pudo conectar con el servidor. Verifica que `php artisan serve` esté corriendo y MongoDB activo.",
            "error"
        );
    } finally {
        setLoading(false);
    }
});
