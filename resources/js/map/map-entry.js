// MarkerCluster mantiene agrupadas las capas sin añadir un framework de frontend.
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';

window.L = L;

import('leaflet.markercluster')
    .then(() => import('./map-init.js'))
    .catch(() => {
        const status = document.querySelector('[data-layer-feedback="lugares"]');
        if (status) status.textContent = 'No se pudo cargar el agrupamiento del mapa.';
    });
