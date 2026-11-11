import '../css/asistencia.css';

const message = document.getElementById('attendanceMessage');
const submit = document.getElementById('attendanceSubmit');
const login = document.getElementById('attendanceLogin');
const codeForm = document.getElementById('attendanceCodeForm');

if (codeForm) {
    const codeInput = document.getElementById('attendanceCode');
    const codeMessage = document.getElementById('attendanceCodeMessage');

    codeForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const codigo = codeInput.value.trim().toUpperCase();
        const accessToken = sessionStorage.getItem('regenera_access_token');
        codeMessage.classList.remove('is-error', 'is-success');

        if (!accessToken) {
            codeMessage.textContent = 'Inicia sesión con la cuenta inscrita para registrar tu asistencia.';
            codeMessage.classList.add('is-error');
            return;
        }

        codeMessage.textContent = 'Verificando código…';
        try {
            const response = await fetch('/api/asistencias', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    Authorization: `Bearer ${accessToken}`,
                },
                body: JSON.stringify({ metodo: 'QR', tokenAsistencia: codigo }),
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) {
                codeMessage.textContent = result.message || 'No se pudo registrar la asistencia.';
                codeMessage.classList.add('is-error');
                return;
            }
            codeMessage.textContent = result.message || 'Asistencia registrada.';
            codeMessage.classList.add('is-success');
            codeInput.value = '';
        } catch (error) {
            console.error('No se pudo registrar la asistencia con código.', error);
            codeMessage.textContent = 'No hay conexión con el servidor. Inténtalo de nuevo.';
            codeMessage.classList.add('is-error');
        }
    });
}

if (message && submit && login) {
    let token = '';
    try {
        token = decodeURIComponent(window.location.hash.slice(1));
    } catch (error) {
        console.error('El enlace del código QR contiene un token no válido.', error);
    }

    window.history.replaceState(null, '', `${window.location.pathname}${window.location.search}`);

    if (!token) {
        message.textContent = 'Este QR no contiene un código de asistencia válido. Escanea el QR original.';
        message.classList.add('is-error');
    } else if (!sessionStorage.getItem('regenera_access_token')) {
        message.textContent = 'Inicia sesión con la cuenta inscrita para registrar tu asistencia.';
        login.href = `/login?${new URLSearchParams({ redirect: `/asistencia#${token}` })}`;
        login.hidden = false;
    } else {
        message.textContent = 'Al confirmar, el servidor verificará tu inscripción y que la actividad esté dentro de su horario.';
        submit.hidden = false;
    }

    submit.addEventListener('click', async () => {
        submit.disabled = true;
        message.classList.remove('is-error', 'is-success');
        message.textContent = 'Verificando inscripción y registrando asistencia…';

        try {
            const response = await fetch('/api/asistencias', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    Authorization: `Bearer ${sessionStorage.getItem('regenera_access_token')}`,
                },
                body: JSON.stringify({
                    metodo: 'QR',
                    tokenAsistencia: token,
                }),
            });
            let result;
            try {
                result = await response.json();
            } catch {
                message.textContent = 'El servidor respondió con un error inesperado. Inténtalo de nuevo más tarde.';
                message.classList.add('is-error');
                return;
            }

            if (!response.ok) {
                message.textContent = result.message || 'No se pudo registrar la asistencia.';
                message.classList.add('is-error');
                if (response.status === 401) {
                    sessionStorage.removeItem('regenera_access_token');
                    login.href = `/login?${new URLSearchParams({ redirect: `/asistencia#${token}` })}`;
                    login.hidden = false;
                }
                return;
            }

            message.textContent = result.message || 'Asistencia registrada correctamente.';
            message.classList.add('is-success');
            submit.hidden = true;
        } catch (error) {
            console.error('No se pudo enviar el registro de asistencia.', error);
            message.textContent = 'No se pudo conectar con el servidor. Revisa tu conexión e inténtalo de nuevo.';
            message.classList.add('is-error');
        } finally {
            submit.disabled = false;
        }
    });
}
