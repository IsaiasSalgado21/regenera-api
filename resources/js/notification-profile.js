import axios from 'axios';
import { apiUrl } from './api-url';
import { getNotificationSound, playNotificationSound, setNotificationSound } from './notification-sound';

const page = document.querySelector('[data-notification-profile]');
if (page) {
    const status = page.querySelector('[data-profile-status]');
    const frequencyForm = page.querySelector('[data-reminder-form]');
    const reminderSettings = page.querySelector('[data-reminder-settings]');
    const pushButton = page.querySelector('[data-push-toggle]');
    const pushLabel = pushButton.querySelector('[data-push-toggle-label]');
    const soundSelect = page.querySelector('[data-push-sound]');
    const soundPreview = page.querySelector('[data-push-sound-preview]');
    const headers = () => {
        const token = sessionStorage.getItem('regenera_access_token');
        return {
            Accept: 'application/json',
            ...(token ? { Authorization: 'Bearer ' + token } : {}),
        };
    };

    function setStatus(message, isError = false) {
        status.textContent = message;
        status.classList.toggle('is-error', isError);
    }

    function setPushState(active, label) {
        pushButton.setAttribute('aria-checked', String(active));
        pushLabel.textContent = label || (active ? 'Activas en este dispositivo' : 'Activar push');
    }

    function decodeApplicationServerKey(key) {
        const padding = '='.repeat((4 - key.length % 4) % 4);
        const base64 = (key + padding).replace(/-/g, '+').replace(/_/g, '/');
        const decoded = Uint8Array.from(atob(base64), character => character.charCodeAt(0));
        if (decoded.length !== 65 || decoded[0] !== 4) {
            throw new Error('La clave pública VAPID del servidor no tiene un formato válido.');
        }
        return decoded;
    }

    function pushErrorMessage(error) {
        if (error.response?.data?.message) return error.response.data.message;
        if (/registration failed\s*[-–]\s*push service error/i.test(error.message || '')) {
            return 'El servicio push del navegador no respondió. Comprueba que el navegador tenga conexión a Internet y que una VPN, proxy o firewall no esté bloqueando sus notificaciones; después, vuelve a intentarlo.';
        }
        if (error.name === 'InvalidAccessError') {
            return 'El navegador rechazó la clave VAPID. Verifica que la clave pública sea la correspondiente al servidor.';
        }
        if (error.name === 'NotAllowedError') {
            return 'El navegador no permitió crear la suscripción. Revisa el permiso de notificaciones para este sitio.';
        }
        return error.message || 'No se pudo cambiar la configuración push.';
    }

    if (soundSelect && soundPreview) {
        soundSelect.value = getNotificationSound();
        soundSelect.addEventListener('change', () => {
            setNotificationSound(soundSelect.value);
            soundPreview.disabled = soundSelect.value === 'none';
        });
        soundPreview.disabled = soundSelect.value === 'none';
        soundPreview.addEventListener('click', () => playNotificationSound(soundSelect.value));
    }

    async function load() {
        const token = sessionStorage.getItem('regenera_access_token');
        if (!token) {
            setStatus('Inicia sesión para configurar tus preferencias.', true);
            frequencyForm.querySelectorAll('select, button').forEach(control => { control.disabled = true; });
            pushButton.disabled = true;
            return;
        }
        frequencyForm.querySelectorAll('select, button').forEach(control => { control.disabled = false; });
        pushButton.disabled = false;
        try {
            const userResponse = await axios.get(apiUrl('/api/auth/me'), { headers: headers() });
            if (sessionStorage.getItem('regenera_access_token') !== token) return;
            if (userResponse.data.usuario.perfilCompleto !== true) {
                frequencyForm.querySelectorAll('select, button').forEach(control => { control.disabled = true; });
                pushButton.disabled = true;
                setStatus('Completa tus datos para configurar tus preferencias.', true);
                return;
            }
            const preferenceResponse = await axios.get(apiUrl('/api/notificaciones/preferencias'), { headers: headers() });
            if (userResponse.data.usuario.rol === 'admin') {
                reminderSettings.hidden = true;
            } else {
                reminderSettings.hidden = false;
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
            setPushState(Boolean(subscription), subscription
                ? 'Activas en este dispositivo'
                : Notification.permission === 'granted' ? 'Completar activación' : 'Activar push');
            setStatus(Notification.permission === 'denied'
                ? 'El permiso está bloqueado por el navegador. Puedes cambiarlo en la configuración del sitio.'
                : Notification.permission === 'granted' && !subscription
                    ? 'El permiso está concedido, pero no hay una suscripción push activa en este navegador.'
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
            await axios.patch(apiUrl('/api/notificaciones/preferencias'), {
                recordatorioFrecuencia: frequencyForm.elements.recordatorioFrecuencia.value,
            }, { headers: headers() });
            setStatus('Frecuencia de recordatorios guardada.');
        } catch (error) {
            setStatus(error.response?.data?.message || 'No se pudieron guardar las preferencias.', true);
        } finally {
            submit.disabled = false;
        }
    });

    pushButton.addEventListener('click', async () => {
        pushButton.disabled = true;
        let registration;
        let createdSubscription = null;
        try {
            registration = await navigator.serviceWorker.register('/sw.js');
            const existing = await registration.pushManager.getSubscription();
            if (existing) {
                await axios.delete(apiUrl('/api/notificaciones/push/suscripcion'), {
                    headers: headers(),
                    data: { endpoint: existing.endpoint },
                });
                await existing.unsubscribe();
                pushButton.dataset.endpoint = '';
                setPushState(false, 'Activar push');
                setStatus('Push desactivado en este dispositivo.');
                return;
            }

            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                setPushState(false, 'Activar push');
                setStatus('No se activaron las push porque no se concedió permiso.', true);
                return;
            }
            const keyResponse = await axios.get(apiUrl('/api/notificaciones/push/clave-publica'), { headers: headers() });
            createdSubscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: decodeApplicationServerKey(keyResponse.data.data.publicKey),
            });
            await axios.post(apiUrl('/api/notificaciones/push/suscripcion'), {
                ...createdSubscription.toJSON(),
                contentEncoding: PushManager.supportedContentEncodings?.[0] || 'aes128gcm',
            }, { headers: headers() });
            pushButton.dataset.endpoint = createdSubscription.endpoint;
            setPushState(true);
            createdSubscription = null;
            setStatus('Push activado en este dispositivo.');
        } catch (error) {
            if (createdSubscription) {
                const endpoint = createdSubscription.endpoint;
                const cleanupResults = await Promise.allSettled([
                    axios.delete(apiUrl('/api/notificaciones/push/suscripcion'), {
                        headers: headers(),
                        data: { endpoint },
                    }),
                    createdSubscription.unsubscribe(),
                ]);
                pushButton.dataset.endpoint = '';
                setPushState(false, Notification.permission === 'granted' ? 'Completar activación' : 'Activar push');
                const cleanupFailed = cleanupResults.some(result => result.status === 'rejected');
                setStatus(cleanupFailed
                    ? `${pushErrorMessage(error)} No se pudo completar la limpieza de la suscripción; revisa la conexión.`
                    : pushErrorMessage(error), true);
                return;
            }
            setPushState(false, Notification.permission === 'granted' ? 'Completar activación' : 'Activar push');
            setStatus(pushErrorMessage(error), true);
        } finally {
            pushButton.disabled = false;
        }
    });

    load();
    document.addEventListener('regenera:auth-changed', load);
}
