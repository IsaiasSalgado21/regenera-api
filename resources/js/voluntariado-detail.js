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

        const symbolsBase = document.body.dataset.symbolsBase;
        const markerOptions = {};
        if (symbolsBase) {
            const image = document.createElement('img');
            image.className = 'ui-symbol ui-symbol--map';
            image.src = `${symbolsBase.replace(/\/+$/, '')}/ubicacion.png`;
            image.alt = '';
            image.setAttribute('aria-hidden', 'true');
            markerOptions.icon = L.divIcon({
                className: 'ui-map-pin',
                html: image,
                iconSize: [30, 30],
                iconAnchor: [15, 30],
                popupAnchor: [0, -30],
            });
        }
        const marker = L.marker([latitude, longitude], markerOptions).addTo(map);
        if (symbolsBase) {
            marker.getElement().setAttribute('aria-label', mapElement.dataset.locationName || 'Ubicación del voluntariado');
        }
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
    const capacityCard = document.querySelector('[data-volunteer-capacity]');
    const impactForm = document.querySelector('[data-volunteer-impact-form]');
    const impactStatus = impactForm?.querySelector('[data-impact-status]');
    const impactTypeSelect = impactForm?.querySelector('[data-impact-type]');
    const impactCustomFields = impactForm?.querySelector('[data-impact-custom-fields]');
    const impactSavedTypeWrap = impactForm?.querySelector('[data-impact-saved-type-wrap]');
    const impactSavedTypeSelect = impactForm?.querySelector('[data-impact-saved-type]');
    const impactCustomLabel = impactForm?.querySelector('[data-impact-custom-label]');
    const impactUnitSelect = impactForm?.querySelector('[data-impact-unit-select]');
    const impactUnitValue = impactForm?.querySelector('[data-impact-unit-value]');
    const impactOtherUnitWrap = impactForm?.querySelector('[data-impact-other-unit-wrap]');
    const impactOtherUnit = impactForm?.querySelector('[data-impact-other-unit]');
    const impactSaveType = impactForm?.querySelector('[data-impact-save-type]');
    const impactSpeciesPanel = impactForm?.querySelector('[data-impact-panel="species"]');
    const impactSpeciesSearch = impactForm?.querySelector('[data-impact-species-search]');
    const impactSpeciesSelect = impactForm?.querySelector('[data-impact-species-select]');
    const impactSpeciesStatus = impactForm?.querySelector('[data-impact-species-status]');
    const impactSelectedSpecies = new Set();
    let impactSpeciesCatalog = [];
    let impactSpeciesLoaded = false;
    let impactSpeciesLoading = false;
    let impactCustomTypesLoaded = false;
    let impactCustomTypesLoading = false;
    const cancelDialog = document.querySelector('[data-enrollment-cancel-dialog]');
    const cancelDialogConfirm = cancelDialog?.querySelector('[data-enrollment-cancel-confirm]');
    const cancelDialogDismiss = cancelDialog?.querySelector('[data-enrollment-cancel-dismiss]');
    const cancelDialogStatus = cancelDialog?.querySelector('[data-enrollment-cancel-status]');
    let token = sessionStorage.getItem('regenera_access_token');
    const volunteerId = detailCard.dataset.volunteerId;
    let isEnrolled = false;
    let currentUser = null;
    let canViewOwnerActivity = false;
    let ownerActivityInterval = null;
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

    function updateCapacity(delta) {
        const enrolled = capacityCard?.querySelector('[data-capacity-enrolled]');
        const available = capacityCard?.querySelector('[data-capacity-available-count]');
        const availableLabel = capacityCard?.querySelector('[data-capacity-available]');
        const progress = capacityCard?.querySelector('[data-capacity-progress]');
        if (!enrolled || !available) return;

        const total = Number(detailCard.querySelector('.volunteer-capacity-stats > div:last-child strong')?.textContent) || 0;
        const enrolledCount = Math.max(0, Number(enrolled.textContent) + delta);
        const availableCount = Math.max(0, total - enrolledCount);
        enrolled.textContent = String(enrolledCount);
        available.textContent = String(availableCount);
        if (availableLabel) availableLabel.textContent = String(availableCount);
        if (progress) progress.style.width = `${total ? Math.min(100, (enrolledCount / total) * 100) : 0}%`;
    }

    async function loadVolunteerPermissions() {
        if (!token) return null;

        try {
            const { data } = await axios.get('/api/auth/me', {
                headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
            });
            const user = data.usuario;
            currentUser = user;
            const isAdmin = user.rol === 'admin';
            const isOwner = String(user.id) === String(detailCard.dataset.organizerId);
            canViewOwnerActivity = isAdmin || isOwner;
            const started = Date.now() >= startAt.getTime();
            const ended = Date.now() >= endAt.getTime();
            const isCancelled = detailCard.dataset.volunteerState === 'cancelado';
            const hasRelevantImpactMetrics = Boolean(impactForm?.querySelector('select[name="tipo"] option[value]:not([value=""])'));
            const impactEligible = ended
                && ['publicado', 'finalizado'].includes(detailCard.dataset.volunteerState)
                && hasRelevantImpactMetrics
                && (isAdmin || (isOwner && user.rol === 'organizador'));
            const ownerActions = detailCard.querySelector('[data-volunteer-owner-actions]');
            const editLink = detailCard.querySelector('[data-volunteer-owner-edit]');
            const cancelEventButton = detailCard.querySelector('[data-volunteer-management-action="cancel"]');
            const deleteEventButton = detailCard.querySelector('[data-volunteer-management-action="delete"]');
            const enrollmentActions = detailCard.querySelector('[data-enrollment-actions]');
            const ownerEnrollmentNote = detailCard.querySelector('[data-owner-enrollment-note]');
            const mayManage = isAdmin || (isOwner && !started);

            ownerActions.hidden = !mayManage;
            if (impactForm) impactForm.hidden = !impactEligible;
            if (impactEligible) void loadSavedImpactTypes();
            editLink.hidden = !(isAdmin || (isOwner && !started && user.rol === 'organizador'));
            cancelEventButton.hidden = !mayManage || isCancelled;
            deleteEventButton.hidden = !isAdmin;

            if (isAdmin || isOwner) {
                const joinControl = getJoinControl();
                if (joinControl) joinControl.hidden = true;
                if (enrollmentActions) enrollmentActions.hidden = true;
                if (ownerEnrollmentNote) {
                    ownerEnrollmentNote.hidden = false;
                    ownerEnrollmentNote.textContent = isOwner
                        ? 'Organizas este voluntariado; no puedes inscribirte.'
                        : 'La administración no puede inscribirse a voluntariados.';
                }
            }

            if (editLink && !editLink.hidden) {
                const dashboardPath = isAdmin ? '/admin/dashboard' : '/organizador/dashboard';
                const volunteerId = encodeURIComponent(detailCard.dataset.volunteerId);
                editLink.href = `${dashboardPath}?voluntariado=${volunteerId}&editar=1`;
            }
            return user;
        } catch (error) {
            if (error.response?.status !== 401) {
                console.error('No se pudieron consultar los permisos del voluntariado.', error);
            }
            return null;
        }
    }

    detailCard.querySelectorAll('[data-volunteer-management-action]').forEach(button => {
        button.addEventListener('click', async () => {
            const action = button.dataset.volunteerManagementAction;
            const managementStatus = detailCard.querySelector('[data-volunteer-management-status]');
            const actionLabel = action === 'delete' ? 'eliminar' : 'cancelar';
            if (!token) {
                managementStatus.textContent = 'Inicia sesión para administrar este voluntariado.';
                return;
            }
            if (!window.confirm(`¿Seguro que deseas ${actionLabel} este voluntariado?`)) return;

            button.disabled = true;
            try {
                const volunteerId = encodeURIComponent(detailCard.dataset.volunteerId);
                const deleting = action === 'delete';
                const endpoint = deleting
                    ? `/api/admin/voluntariados/${volunteerId}`
                    : `/api/voluntariados/${volunteerId}`;
                const response = await fetch(endpoint, {
                    method: deleting ? 'DELETE' : 'PATCH',
                    headers: attendanceHeaders(),
                    ...(deleting ? {} : { body: JSON.stringify({ estado: 'cancelado' }) }),
                });
                const result = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(result.message || `No se pudo ${actionLabel} el voluntariado.`);
                if (deleting) {
                    window.location.assign('/voluntariados');
                    return;
                }
                window.location.reload();
            } catch (error) {
                managementStatus.textContent = error.message;
                button.disabled = false;
            }
        });
    });

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
        if (!token || !canViewOwnerActivity) return;
        const section = document.querySelector('[data-owner-volunteer-activity]');
        const status = document.querySelector('[data-owner-activity-status]');
        const statsList = document.querySelector('[data-owner-activity-stats]');
        const list = document.querySelector('[data-owner-activity-participants]');
        try {
            const response = await fetch(`/api/admin/voluntariados/${encodeURIComponent(detailCard.dataset.volunteerId)}/dashboard`, {
                headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
            });
            if (!response.ok) return;
            const result = await response.json();
            const participants = result.data?.participantes || [];
            const stats = result.data?.estadisticas || {};
            const eventStarted = result.data?.voluntariado?.iniciadoPorFecha === true;
            section.hidden = false;
            statsList.replaceChildren();
            [
                ['Inscripciones totales', stats.totalInscripciones],
                ['Asistieron', stats.asistencias],
                ['Faltas', stats.faltas],
                ['Cancelaciones', stats.canceladas],
                ['Pendientes', stats.pendientes],
            ].forEach(([label, value]) => {
                const metric = document.createElement('div');
                metric.className = 'volunteer-owner-activity-stat';
                const amount = document.createElement('strong');
                amount.textContent = String(Number(value) || 0);
                const caption = document.createElement('span');
                caption.textContent = label;
                metric.append(amount, caption);
                statsList.append(metric);
            });
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

                if (eventStarted && !participant.asistencia?.confirmada
                    && ['inscrita', 'activa', 'completada'].includes(participant.estadoInscripcion)) {
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
                    acceptButton.dataset.requestDecision = 'accept';
                    acceptButton.textContent = 'Aceptar solicitud';
                    const rejectButton = document.createElement('button');
                    rejectButton.className = 'button button-secondary';
                    rejectButton.type = 'button';
                    rejectButton.dataset.requestDecision = 'reject';
                    rejectButton.textContent = 'Rechazar';
                    acceptButton.addEventListener('click', () => respondToAttendanceRequest(request.id, 'aceptar', acceptButton, status));
                    rejectButton.addEventListener('click', () => {
                        const motivoIngresado = window.prompt('Motivo del rechazo (opcional):');
                        if (motivoIngresado === null) return;
                        const motivo = motivoIngresado.trim();
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

    detailCard.addEventListener('regenera:attendance-recorded', () => {
        if (canViewOwnerActivity) loadOwnerActivity();
    });

    function startOwnerActivityPolling() {
        if (!canViewOwnerActivity) return;
        void loadOwnerActivity();
        if (!ownerActivityInterval) ownerActivityInterval = window.setInterval(loadOwnerActivity, 15_000);
    }

    async function respondToAttendanceRequest(requestId, decision, button, status, motivo = '') {
        const siblingDecision = decision === 'aceptar' ? 'reject' : 'accept';
        const sibling = button.parentElement.querySelector(`[data-request-decision="${siblingDecision}"]`);
        const buttons = [button, sibling].filter(Boolean);
        const labels = buttons.map(item => item.textContent);
        buttons.forEach((item, index) => {
            item.disabled = true;
            if (index === 0) item.textContent = 'Procesando…';
        });
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
            buttons.forEach((item, index) => {
                item.disabled = false;
                item.textContent = labels[index];
            });
        }
    }

    updateCountdown();
    window.setInterval(updateCountdown, 1000);

    function showEnrollment(data) {
        isEnrolled = true;
        const inscriptionId = data.inscripcionId || '';
        const joinControl = getJoinControl();
        if (joinControl) joinControl.hidden = true;
        if (cancelButton) {
            cancelButton.dataset.inscriptionId = inscriptionId;
            cancelButton.hidden = !inscriptionId || Date.now() >= startAt.getTime();
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
        updateCountdown();
    }

    async function loadEnrollmentStatus() {
        if (!token) return null;
        try {
            const { data } = await axios.get(
                `/api/inscripciones/voluntariado/${encodeURIComponent(volunteerId)}/mia`,
                { headers: attendanceHeaders() },
            );
            if (!data.inscrito) return false;
            showEnrollment(data);
            return true;
        } catch (error) {
            if (error.response?.status !== 401) {
                console.error('No se pudo consultar la inscripción al voluntariado.', error);
                message.textContent = 'No se pudo consultar tu estado de inscripción.';
            }
            return null;
        }
    }

    cancelButton?.addEventListener('click', async () => {
        if (!token || !cancelButton.dataset.inscriptionId || cancelButton.disabled) return;
        cancelDialogStatus.textContent = '';
        cancelDialogConfirm.disabled = false;
        cancelDialogConfirm.textContent = 'Cancelar participación';
        cancelDialog.showModal();
    });

    cancelDialogDismiss?.addEventListener('click', () => cancelDialog.close());

    cancelDialogConfirm?.addEventListener('click', async () => {
        if (!token || !cancelButton.dataset.inscriptionId || cancelDialogConfirm.disabled) return;
        const originalLabel = cancelButton.textContent;
        cancelButton.disabled = true;
        cancelButton.textContent = 'Cancelando…';
        cancelDialogConfirm.disabled = true;
        cancelDialogConfirm.textContent = 'Cancelando…';
        cancelDialogStatus.textContent = '';
        message.textContent = '';
        try {
            const { data } = await axios.post(
                `/api/inscripciones/${encodeURIComponent(cancelButton.dataset.inscriptionId)}/cancelar`,
                {},
                { headers: attendanceHeaders() },
            );
            updateCapacity(-1);
            cancelButton.hidden = true;
            cancelButton.dataset.inscriptionId = '';
            isEnrolled = false;
            attendanceResult.hidden = true;
            const joinControl = getJoinControl();
            if (joinControl && initialJoinLink) {
                const restoredLink = initialJoinLink.cloneNode(true);
                restoredLink.hidden = false;
                joinControl.replaceWith(restoredLink);
            }
            if (enrollmentNote) enrollmentNote.textContent = 'Puedes volver a inscribirte si cambias de opinión.';
            message.textContent = data.message || 'Participación cancelada.';
            cancelDialog.close();
        } catch (error) {
            const errorMessage = error.response?.data?.message || 'No se pudo cancelar tu participación.';
            cancelDialogStatus.textContent = errorMessage;
            message.textContent = errorMessage;
        } finally {
            cancelButton.disabled = false;
            cancelButton.textContent = originalLabel;
            cancelDialogConfirm.disabled = false;
            cancelDialogConfirm.textContent = 'Cancelar participación';
        }
    });

    requestAttendanceButton?.addEventListener('click', async () => {
        if (!token || requestAttendanceButton.disabled) return;
        const originalLabel = requestAttendanceButton.textContent;
        requestAttendanceButton.disabled = true;
        requestAttendanceButton.textContent = 'Enviando solicitud…';
        try {
            const { data } = await axios.post('/api/asistencias/solicitudes', { voluntariadoId: volunteerId }, { headers: attendanceHeaders() });
            attendanceResultMessage.textContent = data.message || 'Enviamos tu solicitud al propietario.';
            requestAttendanceButton.textContent = 'Solicitud enviada al propietario';
        } catch (error) {
            attendanceResultMessage.textContent = error.response?.data?.message || 'No se pudo enviar la solicitud.';
            requestAttendanceButton.disabled = false;
            requestAttendanceButton.textContent = originalLabel;
        }
    });

    document.addEventListener('regenera:inscription-created', event => {
        if (event.detail?.voluntariadoId !== volunteerId || isEnrolled) return;
        if (!event.detail?.alreadyEnrolled) updateCapacity(1);
        showEnrollment({ inscrito: true, inscripcionId: event.detail.inscripcionId || '' });
    });

    async function refreshAfterAuthentication() {
        token = sessionStorage.getItem('regenera_access_token');
        if (!token) return;
        await Promise.all([loadVolunteerPermissions(), loadEnrollmentStatus()]);
        startOwnerActivityPolling();
    }

    document.addEventListener('regenera:auth-changed', event => {
        if (event.detail?.user) void refreshAfterAuthentication();
    });

    if (token) {
        Promise.all([loadVolunteerPermissions(), loadEnrollmentStatus()])
            .then(startOwnerActivityPolling)
            .catch(error => console.error('No se pudo inicializar la actividad del voluntariado.', error));
    }

    function nowWithinEvent() {
        const now = new Date();
        return Number.isFinite(startAt.getTime()) && Number.isFinite(endAt.getTime()) && now >= startAt && now <= endAt;
    }

    function renderImpactSpeciesOptions() {
        if (!impactSpeciesSelect) return;
        const query = impactSpeciesSearch?.value.trim().toLocaleLowerCase() || '';
        const matches = impactSpeciesCatalog.filter(item => !query
            || `${item.nombre} ${item.nombreCientifico || ''}`.toLocaleLowerCase().includes(query));
        impactSpeciesSelect.replaceChildren(new Option('Selecciona una especie', ''));
        matches.forEach(item => {
            const label = `${item.nombre}${item.nombreCientifico ? ` (${item.nombreCientifico})` : ''} · ${item.tipo === 'fauna' ? 'Fauna' : 'Flora'}`;
            impactSpeciesSelect.add(new Option(label, label));
        });
        const addButton = impactForm.querySelector('[data-impact-add-catalog-species]');
        if (addButton) addButton.disabled = matches.length === 0;
        if (!matches.length && impactSpeciesStatus) {
            impactSpeciesStatus.textContent = query
                ? 'No hay especies que coincidan con esa búsqueda.'
                : 'El catálogo no tiene especies publicadas todavía.';
        } else if (impactSpeciesStatus) {
            impactSpeciesStatus.textContent = `${matches.length} especies disponibles en el catálogo.`;
        }
    }

    async function loadImpactSpecies() {
        if (impactSpeciesLoaded || impactSpeciesLoading || !impactSpeciesPanel) return;
        if (!token) {
            impactSpeciesStatus.textContent = 'Inicia sesión para consultar el catálogo de especies.';
            return;
        }
        impactSpeciesLoading = true;
        impactSpeciesStatus.textContent = 'Cargando catálogo de flora y fauna…';
        try {
            const { data } = await axios.get('/api/flora-fauna', { headers: attendanceHeaders() });
            impactSpeciesCatalog = (data.data || [])
                .filter(item => ['flora', 'fauna'].includes(item.tipo) && item.nombre)
                .sort((first, second) => first.nombre.localeCompare(second.nombre, 'es'));
            impactSpeciesLoaded = true;
            renderImpactSpeciesOptions();
        } catch (error) {
            impactSpeciesStatus.textContent = error.response?.data?.message || 'No se pudo cargar el catálogo de especies.';
        } finally {
            impactSpeciesLoading = false;
        }
    }

    async function loadSavedImpactTypes() {
        if (impactCustomTypesLoaded || impactCustomTypesLoading || !impactSavedTypeSelect || !token) return;
        impactCustomTypesLoading = true;
        try {
            const { data } = await axios.get(
                `/api/voluntariados/${encodeURIComponent(volunteerId)}/impactos?soloTipos=1`,
                { headers: attendanceHeaders() },
            );
            const savedTypes = data.data?.tiposPersonalizados || [];
            impactSavedTypeSelect.replaceChildren(new Option('Crear un tipo nuevo', ''));
            savedTypes.forEach(savedType => {
                const option = new Option(`${savedType.etiqueta} · ${savedType.unidad}`, savedType.id);
                option.dataset.label = savedType.etiqueta;
                option.dataset.unit = savedType.unidad;
                impactSavedTypeSelect.add(option);
            });
            impactSavedTypeWrap.hidden = savedTypes.length === 0;
            impactCustomTypesLoaded = true;
        } catch (error) {
            console.error('No se pudieron cargar los tipos de impacto guardados.', error);
        } finally {
            impactCustomTypesLoading = false;
        }
    }

    function syncImpactUnit() {
        if (!impactUnitSelect || !impactUnitValue) return;
        const otherUnit = impactUnitSelect.value === 'otra';
        impactOtherUnitWrap.hidden = !otherUnit;
        impactOtherUnit.required = otherUnit;
        impactUnitValue.value = otherUnit ? impactOtherUnit.value.trim() : impactUnitSelect.value;
    }

    function fillSavedImpactType() {
        const option = impactSavedTypeSelect?.selectedOptions[0];
        if (!option?.value) {
            impactCustomLabel.value = '';
            impactUnitSelect.value = '';
            impactOtherUnit.value = '';
            impactSaveType.checked = true;
            syncImpactUnit();
            return;
        }

        impactCustomLabel.value = option.dataset.label || '';
        const savedUnit = option.dataset.unit || '';
        const unitIsListed = [...impactUnitSelect.options].some(unitOption => unitOption.value === savedUnit);
        impactUnitSelect.value = unitIsListed ? savedUnit : 'otra';
        impactOtherUnit.value = unitIsListed ? '' : savedUnit;
        impactSaveType.checked = false;
        syncImpactUnit();
    }

    function renderSelectedImpactSpecies() {
        const selectedContainer = impactForm?.querySelector('[data-impact-selected-species]');
        if (!selectedContainer) return;
        selectedContainer.replaceChildren();
        impactSelectedSpecies.forEach(species => {
            const item = document.createElement('span');
            item.className = 'volunteer-impact-species-chip';
            const label = document.createElement('span');
            label.textContent = species;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.dataset.removeImpactSpecies = species;
            remove.setAttribute('aria-label', `Quitar ${species}`);
            remove.textContent = '×';
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'especies[]';
            input.value = species;
            item.append(label, remove, input);
            selectedContainer.append(item);
        });
    }

    function addImpactSpecies(value) {
        const species = String(value || '').trim();
        if (!species) return;
        if (impactSelectedSpecies.has(species)) {
            impactSpeciesStatus.textContent = 'Esa especie ya fue añadida.';
            return;
        }
        impactSelectedSpecies.add(species);
        renderSelectedImpactSpecies();
        impactSpeciesStatus.textContent = `${species} añadida al reporte.`;
    }

    impactTypeSelect?.addEventListener('change', () => {
        const isCustom = impactTypeSelect.value === 'otro';
        impactCustomFields.hidden = !isCustom;
        impactCustomLabel.required = isCustom;
        impactUnitSelect.required = isCustom;
        if (!isCustom) {
            impactCustomLabel.value = '';
            impactSavedTypeSelect.value = '';
            impactUnitSelect.value = '';
            impactOtherUnit.value = '';
            impactSaveType.checked = true;
        }
        syncImpactUnit();
    });

    impactSavedTypeSelect?.addEventListener('change', fillSavedImpactType);
    impactUnitSelect?.addEventListener('change', syncImpactUnit);
    impactOtherUnit?.addEventListener('input', syncImpactUnit);

    impactSpeciesSearch?.addEventListener('input', renderImpactSpeciesOptions);
    impactForm?.querySelector('[data-impact-add-catalog-species]')?.addEventListener('click', () => {
        addImpactSpecies(impactSpeciesSelect.value);
    });
    impactForm?.querySelector('[data-impact-add-custom-species]')?.addEventListener('click', () => {
        const input = impactForm.querySelector('[data-impact-custom-species]');
        addImpactSpecies(input.value);
        input.value = '';
    });
    impactForm?.addEventListener('click', event => {
        const toggle = event.target.closest('[data-impact-toggle]');
        if (toggle) {
            const panel = impactForm.querySelector(`[data-impact-panel="${toggle.dataset.impactToggle}"]`);
            panel.hidden = !panel.hidden;
            toggle.setAttribute('aria-expanded', String(!panel.hidden));
            if (toggle.dataset.impactToggle === 'species' && !panel.hidden) void loadImpactSpecies();
            return;
        }
        const remove = event.target.closest('[data-remove-impact-species]');
        if (remove) {
            impactSelectedSpecies.delete(remove.dataset.removeImpactSpecies);
            renderSelectedImpactSpecies();
        }
    });

    impactForm?.addEventListener('submit', async event => {
        event.preventDefault();
        const submit = impactForm.querySelector('[data-impact-submit]');
        if (submit.disabled) return;
        const formData = new FormData(impactForm);
        const originalLabel = submit.textContent;
        submit.disabled = true;
        submit.setAttribute('aria-busy', 'true');
        submit.textContent = 'Registrando…';
        impactStatus.textContent = '';

        try {
            const payload = new FormData();
            payload.append('tipo', String(formData.get('tipo') || ''));
            payload.append('cantidad', String(formData.get('cantidad') || ''));
            payload.append('nota', String(formData.get('nota') || '').trim());
            if (formData.get('tipo') === 'otro') {
                payload.append('etiquetaPersonalizada', String(formData.get('etiquetaPersonalizada') || '').trim());
                payload.append('unidadPersonalizada', String(formData.get('unidadPersonalizada') || '').trim());
                payload.append('guardarTipoPersonalizado', formData.get('guardarTipoPersonalizado') ? '1' : '0');
            }
            formData.getAll('especies[]').forEach(species => payload.append('especies[]', String(species)));
            formData.getAll('imagenes[]')
                .filter(file => file instanceof File && file.size > 0)
                .forEach(file => payload.append('imagenes[]', file));

            const { data } = await axios.post(
                `/api/admin/voluntariados/${encodeURIComponent(volunteerId)}/impactos`,
                payload,
                { headers: { Accept: 'application/json', Authorization: `Bearer ${token}` } },
            );
            impactStatus.textContent = data.message || 'Impacto registrado correctamente.';
            window.location.reload();
        } catch (error) {
            impactStatus.textContent = error.response?.data?.message || 'No se pudo registrar el impacto.';
            submit.disabled = false;
            submit.textContent = originalLabel;
            submit.removeAttribute('aria-busy');
        }
    });
}
