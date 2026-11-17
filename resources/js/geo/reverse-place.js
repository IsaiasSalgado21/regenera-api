let lastRequestAt = 0;

function waitForSlot(signal, delay) {
    if (signal.aborted) return Promise.reject(new DOMException('La consulta fue cancelada.', 'AbortError'));
    if (delay <= 0) return Promise.resolve();

    return new Promise((resolve, reject) => {
        const timeout = setTimeout(() => {
            signal.removeEventListener('abort', onAbort);
            resolve();
        }, delay);
        const onAbort = () => {
            clearTimeout(timeout);
            reject(new DOMException('La consulta fue cancelada.', 'AbortError'));
        };
        signal.addEventListener('abort', onAbort, { once: true });
    });
}

export function placeDetailsFromGeocode(result) {
    if (!result.address) throw new Error('No se encontraron datos para este punto.');
    const address = result.address;
    const municipality = address.municipality || address.city || address.town || address.village
        || address.county || address.district || address.state_district || '';
    const name = result.name || result.namedetails?.name || address.amenity || address.road
        || address.neighbourhood || address.suburb || '';
    const streetAddress = [address.house_number, address.road, address.neighbourhood || address.suburb]
        .filter(Boolean).join(', ');

    return {
        nombre: name.slice(0, 150),
        municipio: municipality.slice(0, 100),
        direccion: (streetAddress || result.display_name || '').slice(0, 255),
    };
}

export async function reverseGeocodePlace(latitude, longitude, signal) {
    const params = new URLSearchParams({
        lat: String(latitude),
        lon: String(longitude),
        format: 'jsonv2',
        addressdetails: '1',
        namedetails: '1',
        extratags: '1',
        'accept-language': 'es',
    });
    while (Date.now() - lastRequestAt < 1000) {
        await waitForSlot(signal, 1000 - (Date.now() - lastRequestAt));
    }
    if (signal.aborted) throw new DOMException('La consulta fue cancelada.', 'AbortError');
    lastRequestAt = Date.now();
    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?${params}`, {
        headers: { Accept: 'application/json' },
        signal,
    });
    if (!response.ok) throw new Error(`El servicio de ubicación respondió con ${response.status}.`);
    const result = await response.json();
    return { details: placeDetailsFromGeocode(result), result };
}
