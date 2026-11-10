import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import boundary from '../data/baja-california-sur.json';

const mapElement = document.querySelector('[data-flora-map]');
const dataElement = document.querySelector('[data-flora-map-locations]');
const statusElement = document.querySelector('[data-flora-map-status]');

if (mapElement && dataElement) {
    try {
        const locations = JSON.parse(dataElement.textContent || '[]');
        const mode = mapElement.dataset.floraMapMode;
        if (!locations.length && mode !== 'regional') {
            throw new Error('No hay coordenadas disponibles para este mapa.');
        }

        const map = L.map(mapElement, {
            scrollWheelZoom: false,
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(map);
        const boundaryLayer = L.geoJSON(boundary, {
            interactive: false,
            style: {
                color: '#164f3d',
                weight: 2,
                opacity: 0.85,
                fillColor: '#2f8064',
                fillOpacity: 0.08,
            },
        }).addTo(map);

        const markers = locations.map(location => {
            const marker = L.marker([location.latitud, location.longitud]).addTo(map);
            const popup = document.createElement('div');
            const title = document.createElement('strong');
            const type = document.createElement('span');
            title.textContent = location.nombre;
            type.textContent = location.tipo;
            popup.append(title, document.createElement('br'), type);
            marker.bindPopup(popup);
            return marker;
        });

        const mapBounds = markers.length
            ? L.featureGroup(markers).getBounds().pad(0.2)
            : boundaryLayer.getBounds().pad(0.08);
        const fitOptions = { maxZoom: markers.length ? 10 : 7 };
        map.fitBounds(mapBounds, fitOptions);

        document.addEventListener('flora:stage-shown', event => {
            if (event.detail?.stage === 2) {
                window.requestAnimationFrame(() => {
                    map.invalidateSize();
                    map.fitBounds(mapBounds, fitOptions);
                });
            }
        });
    } catch (error) {
        if (statusElement) statusElement.textContent = error.message;
    }
}
