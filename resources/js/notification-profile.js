import axios from 'axios';

const page = document.querySelector('[data-notification-profile]');
if (page) {
    const token = sessionStorage.getItem('regenera_access_token');
    const status = page.querySelector('[data-profile-status]');
    const frequencyForm = page.querySelector('[data-reminder-form]');
    const reminderSettings = page.querySelector('[data-reminder-settings]');
    const pushButton = page.querySelector('[data-push-toggle]');
    const headers = {
        Accept: 'application/json',
        ...(token ? { Authorization: 'Bearer ' + token } : {}),
    };

    function setStatus(message, isError = false) {
        status.textContent = message;
        status.classList.toggle('is-error', isError);
    }

    function decodeApplicationServerKey(key) {
        const padding = '='.repeat((4 - key.length % 4) % 4);
        const base64 = (key + padding).replace(/-/g, '+').replace(/_/g, '/');
        return Uint8Array.from(atob(base64), character => character.charCodeAt(0));
    }

    async function load() {
        if (!token) {
            setStatus('Inicia sesión para configurar tus preferencias.', true);
            frequencyForm.querySelectorAll('select, button').forEach(control => { control.disabled = true; });
            pushButton.disabled = true;
            return;
        }
        try {
            const [userResponse, preferenceResponse] = await Promise.all([
                axios.get('/api/auth/me', { headers }),
                axios.get('/api/notificaciones/preferencias', { headers }),
            ]);
            if (userResponse.data.usuario.rol === 'admin') {
                reminderSettings.hidden = true;
            } else {
                frequencyForm.elements.recordatorioFrecuencia.value =
                    preferenceResponse.data.data.recordatorioFrecuencia || 'milestones';
            }
            if (!preferenceResponse.data.data.pushDisponible) {
                pushButton.disabled = true;
                setStatus('Las notificaciones push aún no están configuradas en el servidor.', true);
                return;
            }
            if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
                pushButton.disabled = true;
                setStatus('Este navegador no es compatible con notificaciones push.', true);
                return;
            }
            const registration = await navigator.serviceWorker.register('/sw.js');
            const subscription = await registration.pushManager.getSubscription();
            pushButton.dataset.endpoint = subscription?.endpoint || '';
            pushButton.textContent = subscription ? 'Desactivar push en este dispositivo' : 'Activar notificaciones push';
            setStatus(Notification.permission === 'denied'
                ? 'El permiso está bloqueado por el navegador. Puedes cambiarlo en la configuración del sitio.'
                : '');
        } catch (error) {
            setStatus(error.response?.status === 401
                ? 'Tu sesión expiró. Inicia sesión de nuevo.'
                : 'No se pudieron cargar las preferencias de notificación.', true);
        }
    }

    frequencyForm.addEventListener('submit', async event => {
        event.preventDefault();
        const submit = frequencyForm.querySelector('button[type="submit"]');
        submit.disabled = true;
        try {
            await axios.patch('/api/notificaciones/preferencias', {
                recordatorioFrecuencia: frequencyForm.elements.recordatorioFrecuencia.value,
            }, { headers });
            setStatus('Frecuencia de recordatorios guardada.');
        } catch (error) {
            setStatus(error.response?.data?.message || 'No se pudieron guardar las preferencias.', true);
        } finally {
            submit.disabled = false;
        }
    });

    pushButton.addEventListener('click', async () => {
        pushButton.disabled = true;
        try {
            const registration = await navigator.serviceWorker.register('/sw.js');
            const existing = await registration.pushManager.getSubscription();
            if (existing) {
                await axios.delete('/api/notificaciones/push/suscripcion', {
                    headers,
                    data: { endpoint: existing.endpoint },
                });
                await existing.unsubscribe();
                pushButton.dataset.endpoint = '';
                pushButton.textContent = 'Activar notificaciones push';
                setStatus('Push desactivado en este dispositivo.');
                return;
            }

            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                setStatus('No se activaron las push porque no se concedió permiso.', true);
                return;
            }
            const keyResponse = await axios.get('/api/notificaciones/push/clave-publica', { headers });
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: decodeApplicationServerKey(keyResponse.data.data.publicKey),
            });
            await axios.post('/api/notificaciones/push/suscripcion', {
                ...subscription.toJSON(),
                contentEncoding: PushManager.supportedContentEncodings?.[0] || 'aes128gcm',
            }, { headers });
            pushButton.dataset.endpoint = subscription.endpoint;
            pushButton.textContent = 'Desactivar push en este dispositivo';
            setStatus('Push activado en este dispositivo.');
        } catch (error) {
            setStatus(error.response?.data?.message || 'No se pudo cambiar la configuración push.', true);
        } finally {
            pushButton.disabled = false;
        }
    });

    load();
}
