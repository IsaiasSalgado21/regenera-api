import L from 'leaflet';

function createClusterGroup(layer) {
    return L.markerClusterGroup({
        chunkedLoading: true,
        showCoverageOnHover: false,
        iconCreateFunction(cluster) {
            const icon = document.createElement('span');
            icon.className = `map-cluster-dot map-cluster-dot--${layer}`;
            icon.textContent = String(cluster.getChildCount());

            return L.divIcon({
                className: '',
                html: icon.outerHTML,
                iconSize: [42, 42],
            });
        },
    });
}

const layerGroups = {
    voluntariados: {
        points: createClusterGroup('voluntariados'),
        zones: L.featureGroup(),
    },
    lugares: {
        points: createClusterGroup('lugares'),
        zones: L.featureGroup(),
    },
    'flora-fauna': {
        points: createClusterGroup('flora-fauna'),
        zones: L.featureGroup(),
    },
};

export function addMapLayers(map) {
    Object.values(layerGroups).forEach(group => {
        group.points.addTo(map);
        group.zones.addTo(map);
    });
}

function markerLabel(item) {
    return item.titulo || item.nombre || 'Registro del mapa';
}

function makeIcon(item) {
    const allowedTypes = ['voluntariado', 'lugar', 'flora', 'fauna'];
    const type = item.tipo === 'voluntariado' && item.lleno
        ? 'lleno'
        : (allowedTypes.includes(item.tipo) ? item.tipo : 'lugar');
    const iconElement = document.createElement('span');
    iconElement.className = `map-marker-icon map-marker-icon--${type}`;

    const icon = L.divIcon({
        className: '',
        html: iconElement.outerHTML,
        iconSize: [24, 24],
        iconAnchor: [12, 12],
    });

    return icon;
}

function openFeature(marker, item, onSelect) {
    marker.options.title = markerLabel(item);
    const tooltip = document.createElement('span');
    tooltip.textContent = markerLabel(item);
    marker.bindTooltip(tooltip, {
        delay: 1000,
        direction: 'top',
        sticky: true,
    });
    marker.on('focus', () => marker.openTooltip());
    marker.on('blur', () => marker.closeTooltip());
    marker.on('click', () => onSelect(item));
}

export function setLayerData(layer, items, onSelect) {
    const groups = layerGroups[layer];
    if (!groups) return;
    groups.points.clearLayers();
    groups.zones.clearLayers();

    items.forEach(item => {
        const lat = Number(item.lat);
        const lng = Number(item.lng);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

        if (item.zona_aproximada && Number(item.radio_metros) > 0) {
            const zone = L.circle([lat, lng], {
                radius: Number(item.radio_metros),
                className: 'map-zone-circle',
                color: '#a34035',
                fillColor: '#a34035',
                fillOpacity: 0.2,
                weight: 2,
            });
            openFeature(zone, item, onSelect);
            groups.zones.addLayer(zone);
        }

        const marker = L.marker([lat, lng], {
            icon: makeIcon(item),
            keyboard: true,
            alt: markerLabel(item),
        });
        openFeature(marker, item, onSelect);
        groups.points.addLayer(marker);
    });
}

export function setLayerVisible(map, layer, visible) {
    const groups = layerGroups[layer];
    if (!groups) return;

    [groups.points, groups.zones].forEach(group => {
        if (visible && !map.hasLayer(group)) group.addTo(map);
        if (!visible && map.hasLayer(group)) map.removeLayer(group);
    });
}

export function fitMapToResults(map, allItems) {
    const points = allItems
        .map(item => [Number(item.lat), Number(item.lng)])
        .filter(([lat, lng]) => Number.isFinite(lat) && Number.isFinite(lng));

    if (points.length) map.fitBounds(points, { padding: [30, 30], maxZoom: 12 });
}
