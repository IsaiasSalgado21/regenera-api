import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import '../css/location-selector.css';

// La Paz, Baja California Sur, Mexico.
const DEFAULT_CENTER = [24.1426, -110.3128];
const DEFAULT_ZOOM = 8;

function validCoordinate(value, minimum, maximum) {
    const number = Number(value);
    return Number.isFinite(number) && number >= minimum && number <= maximum;
}

export function initializeLocationSelector(form) {
    if (form.locationSelector) return form.locationSelector;

    const mapElement = form.querySelector('[data-location-map]');
    const latitudeInput = form.elements.namedItem('latitud');
    const longitudeInput = form.elements.namedItem('longitud');
    if (!mapElement || !latitudeInput || !longitudeInput) return null;

    const initialCenter = validCoordinate(latitudeInput.value, -90, 90)
        && validCoordinate(longitudeInput.value, -180, 180)
        ? [Number(latitudeInput.value), Number(longitudeInput.value)]
        : DEFAULT_CENTER;
    const map = L.map(mapElement).setView(initialCenter, DEFAULT_ZOOM);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);

    const updateInputs = () => {
        const center = map.getCenter();
        latitudeInput.value = center.lat.toFixed(7);
        longitudeInput.value = center.lng.toFixed(7);
    };

    map.on('moveend', updateInputs);

    const moveMapFromInputs = () => {
        if (!validCoordinate(latitudeInput.value, -90, 90)
            || !validCoordinate(longitudeInput.value, -180, 180)) return;

        map.setView([Number(latitudeInput.value), Number(longitudeInput.value)]);
    };

    latitudeInput.addEventListener('input', moveMapFromInputs);
    longitudeInput.addEventListener('input', moveMapFromInputs);

    const selector = {
        map,
        setCoordinates(latitude, longitude) {
            const coordinatesAreValid = validCoordinate(latitude, -90, 90)
                && validCoordinate(longitude, -180, 180);
            map.setView(
                coordinatesAreValid ? [Number(latitude), Number(longitude)] : DEFAULT_CENTER,
                coordinatesAreValid ? map.getZoom() : DEFAULT_ZOOM,
            );
            updateInputs();
        },
        refresh() {
            window.requestAnimationFrame(() => map.invalidateSize());
        },
    };

    form.locationSelector = selector;
    updateInputs();
    return selector;
}
