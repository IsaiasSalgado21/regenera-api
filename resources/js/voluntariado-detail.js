import axios from 'axios';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { BrowserQRCodeReader } from '@zxing/browser';
import { bajaCaliforniaSurBounds } from './geo/baja-california-sur';

const gallery = document.querySelector('[data-volunteer-gallery]');
const lightbox = document.querySelector('[data-gallery-lightbox]');

if (gallery && lightbox) {
    const images = [...gallery.querySelectorAll('[data-gallery-index]')].map(button => ({
        src: button.querySelector('img').src,
        alt: button.querySelector('img').alt,
    }));
    if (!images.length) {
        const mainImage = gallery.querySelector('[data-gallery-main]');
        if (mainImage) images.push({ src: mainImage.src, alt: mainImage.alt });
    }

    let activeIndex = 0;
    const mainImage = gallery.querySelector('[data-gallery-main]');
    const lightboxImage = lightbox.querySelector('[data-lightbox-image]');
    const lightboxCaption = lightbox.querySelector('[data-lightbox-caption]');
    const count = gallery.querySelector('[data-gallery-count]');

    function showImage(index) {
        if (!images.length) return;
        activeIndex = (index + images.length) % images.length;
        const image = images[activeIndex];
        mainImage.src = image.src;
        mainImage.alt = `Imagen ${activeIndex + 1} del voluntariado`;
        if (count) count.textContent = `${activeIndex + 1} / ${images.length}`;
        gallery.querySelectorAll('[data-gallery-index]').forEach((button, buttonIndex) => {
            const active = buttonIndex === activeIndex;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', String(active));
        });
        if (!lightbox.open) return;
        lightboxImage.src = image.src;
        lightboxImage.alt = image.alt || `Imagen ${activeIndex + 1} del voluntariado`;
        lightboxCaption.textContent = `${activeIndex + 1} / ${images.length}`;
    }

    function openLightbox() {
        if (!images.length) return;
        showImage(activeIndex);
        lightbox.showModal();
        lightbox.querySelector('[data-gallery-close]').focus();
    }

    gallery.addEventListener('click', event => {
        const step = event.target.closest('[data-gallery-step]');
        if (step) {
            showImage(activeIndex + Number(step.dataset.galleryStep));
            return;
        }
        const thumbnail = event.target.closest('[data-gallery-index]');
        if (thumbnail) {
            showImage(Number(thumbnail.dataset.galleryIndex));
            return;
        }
        if (event.target.closest('[data-gallery-open]')) openLightbox();
    });

    lightbox.addEventListener('click', event => {
        if (event.target.closest('[data-gallery-close]')) {
            lightbox.close();
            return;
        }
        const step = event.target.closest('[data-lightbox-step]');
        if (step) showImage(activeIndex + Number(step.dataset.lightboxStep));
        if (event.target === lightbox) lightbox.close();
    });

    lightbox.addEventListener('keydown', event => {
        if (event.key === 'ArrowRight') showImage(activeIndex + 1);
        if (event.key === 'ArrowLeft') showImage(activeIndex - 1);
    });

    lightbox.addEventListener('close', () => {
        gallery.querySelector('[data-gallery-open]')?.focus();
    });
}

const mapElement = document.getElementById('volunteerDetailMap');
if (mapElement) {
    const latitude = Number(mapElement.dataset.latitude);
    const longitude = Number(mapElement.dataset.longitude);
    if (Number.isFinite(latitude) && Number.isFinite(longitude)) {
        const bounds = L.latLngBounds(bajaCaliforniaSurBounds).pad(0.08);
        const map = L.map(mapElement, {
            maxBounds: bounds,
            maxBoundsViscosity: 0.8,
            scrollWheelZoom: false,
        }).setView([latitude, longitude], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(map);

        const marker = L.marker([latitude, longitude]).addTo(map);
        const popup = document.createElement('div');
        const title = document.createElement('strong');
        title.textContent = mapElement.dataset.locationName || 'Ubicación del voluntariado';
        popup.append(title);
        if (mapElement.dataset.locationAddress) {
            const address = document.createElement('p');
            address.textContent = mapElement.dataset.locationAddress;
            popup.append(address);
        }
        marker.bindPopup(popup).openPopup();

        window.requestAnimationFrame(() => map.invalidateSize());
        window.addEventListener('resize', () => map.invalidateSize());
    }
}

const detailCard = document.querySelector('.volunteer-detail-card[data-event-start-at]');
if (detailCard) {
    const startAt = new Date(detailCard.dataset.eventStartAt);
    const endAt = new Date(detailCard.dataset.eventEndAt);
    const countdown = document.querySelector('[data-volunteer-countdown]');
    const endedNotice = document.querySelector('.volunteer-ended-notice');
    const initialJoinLink = detailCard.querySelector('a[data-voluntariado-id]');
    const cancelButton = detailCard.querySelector('[data-enrollment-cancel]');
    const enrollmentNote = detailCard.querySelector('[data-enrollment-note]');
    const message = detailCard.querySelector('#enrollmentMessage');
    const attendanceTools = detailCard.querySelector('[data-participant-attendance]');
    const attendanceStatus = detailCard.querySelector('[data-participant-attendance-status]');
    const codeForm = detailCard.querySelector('[data-attendance-code-form]');
    const cameraContainer = detailCard.querySelector('[data-attendance-camera-container]');
    const cameraVideo = detailCard.querySelector('[data-attendance-camera]');
    const attendanceResult = detailCard.querySelector('[data-participant-attendance-result]');
    const attendanceResultTitle = detailCard.querySelector('[data-participant-attendance-result-title]');
    const attendanceResultMessage = detailCard.querySelector('[data-participant-attendance-result-message]');
    const requestAttendanceButton = detailCard.querySelector('[data-request-attendance-review]');
    const token = sessionStorage.getItem('regenera_access_token');
    let isEnrolled = false;
    let cameraControls = null;
    const qrReader = new BrowserQRCodeReader();
    const getJoinControl = () => detailCard.querySelector('[data-voluntariado-id]');

    function formatRemaining(milliseconds) {
        const totalSeconds = Math.max(0, Math.ceil(milliseconds / 1000));
        const days = Math.floor(totalSeconds / 86400);
        const hours = Math.floor((totalSeconds % 86400) / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
        const parts = [];
        if (days) parts.push(`${days} d`);
        if (days || hours) parts.push(`${hours} h`);
        parts.push(`${minutes} min`, `${seconds} s`);
        return parts.join(' ');
    }

    function updateCountdown() {
        if (!Number.isFinite(startAt.getTime()) || !Number.isFinite(endAt.getTime())) return;
        const now = new Date();
        const startRow = countdown?.querySelector('[data-countdown-start-row]');
        const endRow = countdown?.querySelector('[data-countdown-end-row]');
        const startValue = countdown?.querySelector('[data-countdown-start-value]');
        const endValue = countdown?.querySelector('[data-countdown-end-value]');
        const title = countdown?.querySelector('[data-countdown-title]');
        const showStart = now < startAt && startAt - now <= 24 * 60 * 60 * 1000;
        const showEnd = now < endAt && (
            showStart || (now >= startAt && endAt - now <= 24 * 60 * 60 * 1000)
        );
        const hasEnded = now >= endAt;
        const joinControl = getJoinControl();

        if (startRow) {
            startRow.hidden = !showStart;
            if (showStart) startValue.textContent = formatRemaining(startAt - now);
        }
        if (endRow) {
            endRow.hidden = !showEnd;
            if (showEnd) endValue.textContent = formatRemaining(endAt - now);
        }
        if (countdown) {
            countdown.hidden = !(showStart || showEnd);
            if (title) title.textContent = now >= startAt ? 'La actividad está en curso' : 'La actividad está por comenzar';
        }
        if (endedNotice) endedNotice.hidden = !hasEnded;
        if (now >= startAt && joinControl && !isEnrolled) {
            joinControl.hidden = true;
            if (enrollmentNote) enrollmentNote.textContent = 'La actividad ya inició; ya no es posible inscribirse a esta participación';
        }
        if (cancelButton && isEnrolled && now >= startAt) {
            cancelButton.hidden = true;
            if (enrollmentNote) enrollmentNote.textContent = 'La actividad ya inició; ya no es posible cancelar la participación.';
        }
        if (attendanceTools) {
            attendanceTools.hidden = !(isEnrolled && now >= startAt && now <= endAt);
            if (attendanceStatus && now > endAt) attendanceStatus.textContent = 'El horario de registro de asistencia terminó.';
        }
    }

    function attendanceHeaders() {
        return {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`,
        };
    }

    function extractCode(value) {
        try {
            const parsed = new URL(value);
            if (parsed.hash) return decodeURIComponent(parsed.hash.slice(1));
        } catch {
            return value.trim();
        }
        return value.trim();
    }

    async function registerAttendance(code) {
        if (!token) {
            attendanceStatus.textContent = 'Inicia sesión con la cuenta inscrita para registrar asistencia.';
            return;
        }
        attendanceStatus.textContent = 'Verificando inscripción y horario…';
        try {
            const response = await fetch('/api/asistencias', {
                method: 'POST',
                headers: attendanceHeaders(),
                body: JSON.stringify({ metodo: 'QR', tokenAsistencia: extractCode(code) }),
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) {
                attendanceStatus.textContent = result.message || 'No se pudo registrar la asistencia.';
                return;
            }
            attendanceStatus.textContent = result.message || 'Asistencia registrada.';
            codeForm?.reset();
        } catch (error) {
            console.error('No se pudo registrar la asistencia.', error);
            attendanceStatus.textContent = 'No se pudo conectar con el servidor.';
        }
    }

    function stopCamera() {
        cameraControls?.stop();
        cameraControls = null;
        if (cameraVideo) cameraVideo.srcObject = null;
        if (cameraContainer) cameraContainer.hidden = true;
    }

    async function startCamera() {
        if (!navigator.mediaDevices?.getUserMedia) {
            attendanceStatus.textContent = 'El navegador requiere una conexión segura y permiso de cámara. Escribe el código corto como alternativa.';
            return;
        }
        try {
            cameraContainer.hidden = false;
            cameraControls = await qrReader.decodeFromVideoDevice(undefined, cameraVideo, async result => {
                if (!result) return;
                const code = result.getText();
                stopCamera();
                await registerAttendance(code);
            });
        } catch (error) {
            stopCamera();
            console.error('No se pudo iniciar la cámara.', error);
            attendanceStatus.textContent = 'No se pudo abrir la cámara. Revisa los permisos o usa el código corto.';
        }
    }

    codeForm?.addEventListener('submit', event => {
        event.preventDefault();
        const code = new FormData(codeForm).get('codigo')?.toString().trim();
        if (code) registerAttendance(code);
    });
    detailCard.querySelector('[data-start-attendance-camera]')?.addEventListener('click', startCamera);
    detailCard.querySelector('[data-stop-attendance-camera]')?.addEventListener('click', stopCamera);
    detailCard.querySelector('[data-attendance-qr-photo]')?.addEventListener('change', async event => {
        const file = event.target.files?.[0];
        if (!file) return;
        let imageUrl = null;
        try {
            imageUrl = URL.createObjectURL(file);
            const result = await qrReader.decodeFromImageUrl(imageUrl);
            await registerAttendance(result.getText());
        } catch (error) {
            console.error('No se pudo leer la foto del QR.', error);
            attendanceStatus.textContent = 'No se encontró un QR legible en la foto. Prueba otra imagen o escribe el código corto.';
        } finally {
            if (imageUrl) URL.revokeObjectURL(imageUrl);
            event.target.value = '';
        }
    });

    async function loadOwnerActivity() {
        if (!token) return;
        const section = detailCard.querySelector('[data-owner-volunteer-activity]');
        const status = detailCard.querySelector('[data-owner-activity-status]');
        const list = detailCard.querySelector('[data-owner-activity-participants]');
        try {
            const response = await fetch(`/api/admin/voluntariados/${encodeURIComponent(detailCard.dataset.volunteerId)}/dashboard`, {
                headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
            });
            if (!response.ok) return;
            const result = await response.json();
            const participants = result.data?.participantes || [];
            section.hidden = false;
            status.textContent = participants.length
                ? `${participants.length} personas inscritas`
                : 'Aún no hay personas inscritas.';
            list.replaceChildren();
            participants.forEach(participant => {
                const row = document.createElement('div');
                row.className = 'volunteer-owner-participant';
                const identity = document.createElement('span');
                identity.textContent = `${participant.nombre || 'Participante'} · ${participant.correo || 'Sin correo'}`;
                const attendance = document.createElement('span');
                attendance.textContent = participant.asistencia?.confirmada
                    ? `Asistencia registrada (${participant.asistencia.metodo || 'manual'})`
                    : participant.falto ? 'Falta' : 'Pendiente';
                row.append(identity, attendance);

                if (!participant.asistencia?.confirmada && !participant.falto
                    && ['inscrita', 'activa'].includes(participant.estadoInscripcion)) {
                    const button = document.createElement('button');
                    button.className = 'button button-secondary';
                    button.type = 'button';
                    button.textContent = 'Marcar asistencia';
                    button.addEventListener('click', async () => {
                        button.disabled = true;
                        try {
                            const response = await fetch('/api/asistencias', {
                                method: 'POST',
                                headers: attendanceHeaders(),
                                body: JSON.stringify({
                                    metodo: 'manual',
                                    usuarioId: participant.usuarioId,
                                    voluntariadoId: detailCard.dataset.volunteerId,
                                }),
                            });
                            const data = await response.json().catch(() => ({}));
                            if (!response.ok) throw new Error(data.message || 'No se pudo marcar la asistencia.');
                            await loadOwnerActivity();
                        } catch (error) {
                            status.textContent = error.message;
                            button.disabled = false;
                        }
                    });
                    row.append(button);
                }

                const request = participant.solicitudAsistencia;
                if (request?.estado === 'pendiente') {
                    const acceptButton = document.createElement('button');
                    acceptButton.className = 'button button-primary';
                    acceptButton.type = 'button';
                    acceptButton.textContent = 'Aceptar solicitud';
                    const rejectButton = document.createElement('button');
                    rejectButton.className = 'button button-secondary';
                    rejectButton.type = 'button';
                    rejectButton.textContent = 'Rechazar';
                    acceptButton.addEventListener('click', () => respondToAttendanceRequest(request.id, 'aceptar', acceptButton, status));
                    rejectButton.addEventListener('click', () => {
                        const motivo = window.prompt('Motivo del rechazo (opcional):') || '';
                        respondToAttendanceRequest(request.id, 'rechazar', rejectButton, status, motivo);
                    });
                    row.append(acceptButton, rejectButton);
                } else if (request?.estado === 'aceptando') {
                    const pending = document.createElement('span');
                    pending.textContent = 'Confirmando asistencia…';
                    row.append(pending);
                }
                list.append(row);
            });
        } catch (error) {
            console.error('No se pudo cargar la actividad del voluntariado.', error);
        }
    }

    detailCard.addEventListener('regenera:attendance-recorded', loadOwnerActivity);
    loadOwnerActivity();
    window.setInterval(loadOwnerActivity, 15_000);

    async function respondToAttendanceRequest(requestId, decision, button, status, motivo = '') {
        button.disabled = true;
        try {
            const response = await fetch(`/api/asistencias/solicitudes/${encodeURIComponent(requestId)}/responder`, {
                method: 'PATCH',
                headers: { ...attendanceHeaders(), Accept: 'application/json' },
                body: JSON.stringify({ decision, motivo }),
            });
            const result = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(result.message || 'No se pudo responder la solicitud.');
            status.textContent = result.message || 'Solicitud respondida.';
            await loadOwnerActivity();
        } catch (error) {
            status.textContent = error.message;
            button.disabled = false;
        }
    }

    updateCountdown();
    window.setInterval(updateCountdown, 1000);

    if (token) {
        const headers = {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
        };
        const volunteerId = detailCard.dataset.volunteerId;

        axios.get(`/api/inscripciones/voluntariado/${encodeURIComponent(volunteerId)}/mia`, { headers })
            .then(async ({ data }) => {
                if (!data.inscrito) return;
                isEnrolled = true;
                const joinControl = getJoinControl();
                if (joinControl) joinControl.hidden = true;
                if (cancelButton) {
                    cancelButton.dataset.inscriptionId = data.inscripcionId;
                    cancelButton.hidden = new Date() >= startAt;
                }
                if (enrollmentNote && cancelButton && !cancelButton.hidden) {
                    enrollmentNote.textContent = 'Ya tienes tu lugar. Puedes cancelar tu participación antes de que inicie la actividad.';
                }
                attendanceResult.hidden = false;
                if (data.asistio) {
                    attendanceResultTitle.textContent = '¡Gracias por participar!';
                    attendanceResultMessage.textContent = 'Tu asistencia quedó registrada en esta actividad.';
                    requestAttendanceButton.hidden = true;
                } else if (data.falto) {
                    attendanceResultTitle.textContent = 'No se registró tu asistencia';
                    attendanceResultMessage.textContent = 'Lo sentimos, no se registró tu asistencia en este voluntariado al que te inscribiste.';
                    const solicitudPendiente = ['pendiente', 'aceptando'].includes(data.solicitudAsistenciaEstado);
                    requestAttendanceButton.hidden = false;
                    requestAttendanceButton.disabled = solicitudPendiente;
                    requestAttendanceButton.textContent = solicitudPendiente
                        ? 'Solicitud enviada al propietario'
                        : 'Solicitar revisión al propietario';
                } else {
                    attendanceResultTitle.textContent = 'Inscripción confirmada';
                    attendanceResultMessage.textContent = nowWithinEvent()
                        ? 'Estás inscrito. Registra tu asistencia antes de que termine la actividad.'
                        : 'Tu inscripción está guardada. La asistencia se podrá registrar durante el horario del evento.';
                    requestAttendanceButton.hidden = true;
                }
            })
            .catch(error => {
                if (error.response?.status !== 401) {
                    console.error('No se pudo consultar la inscripción al voluntariado.', error);
                    message.textContent = 'No se pudo consultar tu estado de inscripción.';
                }
            });

        cancelButton?.addEventListener('click', async () => {
            if (!window.confirm('¿Quieres cancelar tu participación en este voluntariado?')) return;
            cancelButton.disabled = true;
            message.textContent = '';
            try {
                const { data } = await axios.post(
                    `/api/inscripciones/${encodeURIComponent(cancelButton.dataset.inscriptionId)}/cancelar`,
                    {},
                    { headers },
                );
                cancelButton.hidden = true;
                isEnrolled = false;
                const joinControl = getJoinControl();
                if (joinControl && initialJoinLink) {
                    joinControl.replaceWith(initialJoinLink.cloneNode(true));
                }
                cancelButton.dataset.inscriptionId = '';
                if (enrollmentNote) enrollmentNote.textContent = 'Puedes volver a inscribirte si cambias de opinión.';
                message.textContent = data.message;
            } catch (error) {
                message.textContent = error.response?.data?.message || 'No se pudo cancelar tu participación.';
            } finally {
                cancelButton.disabled = false;
            }
        });

        document.addEventListener('regenera:inscription-created', event => {
            if (event.detail?.voluntariadoId !== volunteerId) return;
            isEnrolled = true;
            const joinControl = getJoinControl();
            if (joinControl) joinControl.hidden = true;
            if (cancelButton) {
                cancelButton.dataset.inscriptionId = event.detail.inscripcionId || '';
                cancelButton.hidden = !event.detail.inscripcionId || new Date() >= startAt;
            }
            if (enrollmentNote) enrollmentNote.textContent = 'Ya tienes tu lugar. Puedes cancelar tu participación antes de que inicie la actividad.';
        });

        requestAttendanceButton?.addEventListener('click', async () => {
            requestAttendanceButton.disabled = true;
            try {
                const { data } = await axios.post('/api/asistencias/solicitudes', { voluntariadoId: volunteerId }, { headers });
                attendanceResultMessage.textContent = data.message || 'Enviamos tu solicitud al propietario.';
                requestAttendanceButton.textContent = 'Solicitud enviada al propietario';
            } catch (error) {
                attendanceResultMessage.textContent = error.response?.data?.message || 'No se pudo enviar la solicitud.';
                requestAttendanceButton.disabled = false;
            }
        });
    }

    function nowWithinEvent() {
        const now = new Date();
        return Number.isFinite(startAt.getTime()) && Number.isFinite(endAt.getTime()) && now >= startAt && now <= endAt;
    }
}
