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
