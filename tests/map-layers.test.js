import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

function setupMap() {
    const map = {
        layers: new Set(),
        addLayer(layer) { this.layers.add(layer); },
        removeLayer(layer) { this.layers.delete(layer); },
        hasLayer(layer) { return this.layers.has(layer); },
        stop() { this.stopped = true; },
        setView(position, zoom, options) { this.view = { position, zoom, options }; },
    };
    const makeGroup = () => ({
        layers: new Set(),
        addTo(target) { target.addLayer(this); },
        addLayer(layer) { this.layers.add(layer); },
        removeLayer(layer) { this.layers.delete(layer); },
        clearLayers() { this.layers.clear(); },
    });
    const makeMarker = (position, options = {}) => ({
        options,
        position,
        bindTooltip() {},
        on() {},
        setIcon(icon) { this.options.icon = icon; },
        setZIndexOffset(value) { this.offset = value; },
        addTo(target) { target.addLayer(this); },
        getLatLng() { return this.position; },
    });
    const L = {
        markerClusterGroup: makeGroup,
        featureGroup: makeGroup,
        marker: makeMarker,
        circle: makeMarker,
        divIcon: options => options,
    };
    const document = {
        body: { dataset: { symbolsBase: '/symbols' } },
        createElement: () => ({
            outerHTML: '<span></span>',
            setAttribute() {},
            appendChild() {},
            addEventListener() {},
        }),
    };
    const source = readFileSync(new URL('../resources/js/map/map-layers.js', import.meta.url), 'utf8')
        .replace(/^import .*;\s*$/gm, '')
        .replace(/^export /gm, '');
    const context = { L, document, isInsideBajaCaliforniaSur: () => true };
    runInNewContext(`${source}\nglobalThis.mapLayers = { addMapLayers, setLayerData, focusMapFeature, setLayerVisible };`, context);
    context.mapLayers.addMapLayers(map);
    return { map, ...context.mapLayers };
}

test('enfoca el lugar y permite recuperarlo después de ocultar su capa', () => {
    const { map, setLayerData, focusMapFeature, setLayerVisible } = setupMap();
    setLayerData('lugares', [{ id: 'p1', tipo: 'lugar', lat: 24.14, lng: -110.31 }], () => {});
    assert.equal(focusMapFeature(map, 'lugares', 'p1'), true);
    assert.equal(map.stopped, true);
    assert.equal(map.view.zoom, 18);
    assert.equal(map.view.position[0], 24.14);
    assert.equal(map.view.position[1], -110.31);
    assert.equal(map.view.options.animate, false);
    assert.equal(map.layers.size, 7);
    setLayerVisible(map, 'lugares', false);
    assert.equal(map.layers.size, 4);
    setLayerVisible(map, 'lugares', true);
    assert.equal(map.layers.size, 7);
    setLayerData('lugares', [], () => {});
    assert.equal(focusMapFeature(map, 'lugares', 'p1'), false);
});

test('mantiene el zoom limitado de una ubicación aproximada', () => {
    const { map, setLayerData, focusMapFeature } = setupMap();
    setLayerData('flora-fauna', [{ id: 's1', tipo: 'fauna', lat: 24.14, lng: -110.31, zona_aproximada: true, radio_metros: 5000 }], () => {});
    assert.equal(focusMapFeature(map, 'flora-fauna', 's1'), true);
    assert.equal(map.view.zoom, 10);
});
