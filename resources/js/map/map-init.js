import L from 'leaflet';
import { fetchCurrentUser, fetchMapLayer } from './map-api.js';
import { initializeMapFilters } from './map-filters.js';
import { addMapLayers, fitMapToResults, setLayerData, setLayerVisible } from './map-layers.js';
import { createMapPanel } from './map-panel.js';

const layerNames = ['voluntariados', 'lugares', 'flora-fauna'];
const mapElement = document.getElementById('environmentMap');
const countElements = new Map(
    layerNames.map(layer => [layer, document.querySelector(`[data-layer-count="${layer}"]`)])
);
const feedbackElements = new Map(
    layerNames.map(layer => [layer, document.querySelector(`[data-layer-feedback="${layer}"]`)])
);

if (mapElement) {
    const map = L.map(mapElement).setView([24.2, -110.3], 8);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    }).addTo(map);
    addMapLayers(map);

    let currentUser = null;
    let authError = '';
    let authLoading = true;
    let selectedFeature = null;
    const latestItems = new Map(layerNames.map(layer => [layer, []]));

    const renderFeature = createMapPanel({
        panel: document.getElementById('mapInfoPanel'),
        getUser: () => currentUser,
        getAuthError: () => authLoading ? 'Verificando la sesión…' : authError,
        onEnrolled: () => loadLayer('voluntariados'),
    });

    function showFeature(item) {
        selectedFeature = item;
        renderFeature(item);
    }

    function showFeedback(layer, message, state = '', canRetry = false, canClear = false) {
        const container = feedbackElements.get(layer);
        if (!container) return;
        container.replaceChildren();
        container.className = 'map-layer-feedback';
        if (!message) return;

        const text = document.createElement('span');
        text.textContent = message;
        container.appendChild(text);
        container.className = `map-layer-feedback${state ? ` is-${state}` : ''}`;

        if (canRetry) {
            const retry = document.createElement('button');
            retry.type = 'button';
            retry.textContent = 'Reintentar';
            retry.dataset.tooltip = 'Vuelve a consultar esta capa.';
            retry.addEventListener('click', () => loadLayer(layer));
            container.appendChild(retry);
        }

        if (canClear) {
            const clear = document.createElement('button');
            clear.type = 'button';
            clear.textContent = 'Limpiar filtros';
            clear.addEventListener('click', () => {
                const form = document.querySelector(`[data-map-filters="${layer}"]`);
                form?.reset();
                form?.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
            });
            container.appendChild(clear);
        }
    }

    const filters = initializeMapFilters(loadLayer);

    function populatePlaceFilter(places) {
        const select = document.querySelector('[data-map-place-filter]');
        if (!select) return;
        const selected = filters.getFilters('voluntariados').lugar_id || '';
        select.replaceChildren(new Option('Todos los lugares', ''));
        places.forEach(place => select.add(new Option(place.nombre, place.id)));
        select.value = selected;
    }

    async function loadLayer(layer, params) {
        const counter = countElements.get(layer);
        if (counter) counter.textContent = 'Cargando…';
        showFeedback(layer, '');

        try {
            const items = await fetchMapLayer(layer, params ?? filters.getFilters(layer));
            latestItems.set(layer, items);
            setLayerData(layer, items, showFeature);
            if (counter) counter.textContent = `${items.length} ${items.length === 1 ? 'registro' : 'registros'}`;

            if (layer === 'lugares') populatePlaceFilter(items);
            showFeedback(
                layer,
                items.length ? '' : 'No hay resultados para estos filtros.',
                'empty',
                false,
                true
            );

        } catch (error) {
            if (error.name === 'CanceledError' || error.name === 'AbortError') return;
            latestItems.set(layer, []);
            setLayerData(layer, [], showFeature);
            if (counter) counter.textContent = 'No disponible';
            showFeedback(layer, error.message, 'error', true);
        }
    }

    document.querySelectorAll('[data-layer-toggle]').forEach(toggle => {
        toggle.addEventListener('change', () => {
            setLayerVisible(map, toggle.dataset.layerToggle, toggle.checked);
        });
    });

    const tooltipStates = new WeakMap();
    let tooltipId = 0;
    function showTooltip(element) {
        if (!element?.dataset.tooltip || tooltipStates.has(element)) return;
        const timer = window.setTimeout(() => {
            const tooltip = document.createElement('span');
            tooltip.className = 'map-tooltip';
            tooltip.id = `map-tooltip-${++tooltipId}`;
            tooltip.setAttribute('role', 'tooltip');
            tooltip.textContent = element.dataset.tooltip;
            element.setAttribute('aria-describedby', tooltip.id);
            element.insertAdjacentElement('afterend', tooltip);
            tooltipStates.set(element, { tooltip });
        }, 1000);
        tooltipStates.set(element, { timer });
    }

    function hideTooltip(element) {
        const state = tooltipStates.get(element);
        if (!state) return;
        window.clearTimeout(state.timer);
        state.tooltip?.remove();
        element.removeAttribute('aria-describedby');
        tooltipStates.delete(element);
    }

    document.addEventListener('pointerover', event => showTooltip(event.target.closest('[data-tooltip]')));
    document.addEventListener('pointerout', event => {
        const element = event.target.closest('[data-tooltip]');
        if (element && !element.contains(event.relatedTarget)) hideTooltip(element);
    });
    document.addEventListener('focusin', event => showTooltip(event.target.closest('[data-tooltip]')));
    document.addEventListener('focusout', event => hideTooltip(event.target.closest('[data-tooltip]')));
    document.addEventListener('touchstart', event => {
        const element = event.target.closest('[data-tooltip]');
        showTooltip(element);
        if (element) window.setTimeout(() => hideTooltip(element), 4000);
    }, { passive: true });

    fetchCurrentUser()
        .then(user => { currentUser = user; })
        .catch(error => { authError = error.message; })
        .finally(() => {
            authLoading = false;
            if (selectedFeature) renderFeature(selectedFeature);
        });

    Promise.all(layerNames.map(layer => loadLayer(layer))).then(() => {
        fitMapToResults(map, layerNames.flatMap(layer => latestItems.get(layer)));
    });
}
