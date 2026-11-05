import axios from 'axios';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
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
    const token = sessionStorage.getItem('regenera_access_token');
    let isEnrolled = false;
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
            if (enrollmentNote) enrollmentNote.textContent = 'La actividad ya inició y las inscripciones están cerradas.';
        }
        if (cancelButton && now >= startAt) {
            cancelButton.hidden = true;
            if (enrollmentNote) enrollmentNote.textContent = 'La actividad ya inició; ya no es posible cancelar la participación.';
        }
    }

    updateCountdown();
    window.setInterval(updateCountdown, 1000);

    if (token && getJoinControl()) {
        const headers = {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
        };
        const volunteerId = detailCard.dataset.volunteerId;

        axios.get(`/api/inscripciones/voluntariado/${encodeURIComponent(volunteerId)}/mia`, { headers })
            .then(({ data }) => {
                if (!data.inscrito) return;
                isEnrolled = true;
                const joinControl = getJoinControl();
                if (joinControl) joinControl.hidden = true;
                cancelButton.dataset.inscriptionId = data.inscripcionId;
                cancelButton.hidden = new Date() >= startAt;
                if (enrollmentNote && !cancelButton.hidden) {
                    enrollmentNote.textContent = 'Ya tienes tu lugar. Puedes cancelar tu participación antes de que inicie la actividad.';
                }
            })
            .catch(error => {
                if (error.response?.status !== 401) {
                    console.error('No se pudo consultar la inscripción al voluntariado.', error);
                    message.textContent = 'No se pudo consultar tu estado de inscripción.';
                }
            });

        cancelButton.addEventListener('click', async () => {
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
            cancelButton.dataset.inscriptionId = event.detail.inscripcionId || '';
            cancelButton.hidden = !event.detail.inscripcionId || new Date() >= startAt;
            if (enrollmentNote) enrollmentNote.textContent = 'Ya tienes tu lugar. Puedes cancelar tu participación antes de que inicie la actividad.';
        });
    }
}
