import test from 'node:test';
import assert from 'node:assert/strict';
import { placeDetailsFromGeocode, reverseGeocodePlace } from '../resources/js/geo/reverse-place.js';

test('obtiene el nombre y la dirección reales de la respuesta', () => {
    assert.deepEqual(placeDetailsFromGeocode({
        name: 'Parque',
        address: { house_number: '10', road: 'Reforma', suburb: 'Centro', city: 'La Paz' },
    }), {
        nombre: 'Parque',
        direccion: '10, Reforma, Centro',
        municipio: 'La Paz',
    });
});

test('no inventa un nombre si no existe información suficiente', () => {
    assert.deepEqual(placeDetailsFromGeocode({
        address: { city: 'La Paz' },
        display_name: 'La Paz, Baja California Sur',
    }), {
        nombre: '',
        direccion: 'La Paz, Baja California Sur',
        municipio: 'La Paz',
    });
    assert.throws(() => placeDetailsFromGeocode({}), /No se encontraron datos/);
});

test('informa los errores del servicio y respeta la cancelación', async () => {
    const originalFetch = globalThis.fetch;
    let calls = 0;
    globalThis.fetch = async () => {
        calls += 1;
        return { ok: false, status: 503 };
    };
    try {
        await assert.rejects(reverseGeocodePlace(24.14, -110.31, new AbortController().signal), /503/);
        const controller = new AbortController();
        const pending = reverseGeocodePlace(24.14, -110.31, controller.signal);
        controller.abort();
        await assert.rejects(pending, { name: 'AbortError' });
        assert.equal(calls, 1);
    } finally {
        globalThis.fetch = originalFetch;
    }
});
