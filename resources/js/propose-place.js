import { initializeLocationSelector } from './map-selector';
import { isInsideBajaCaliforniaSur } from './geo/baja-california-sur';
import { reverseGeocodePlace } from './geo/reverse-place';

export function initializePlaceCreator(button, onCreated) {
    const dialog = document.getElementById('proposePlaceDialog');
    const form = document.getElementById('proposePlaceForm');
    const message = document.getElementById('proposePlaceMessage');
    const geocodeStatus = document.getElementById('placeGeocodeStatus');
    if (!button || !dialog || !form || !message || !geocodeStatus) return;
    let selector;
    let timer;
    let controller;
    let requestId = 0;
    let coordinates = null;
    const suggestions = { nombre: '', direccion: '' };
    const revisions = { nombre: 0, direccion: 0 };

    function cancelLookup(clearSuggestions = false) {
        clearTimeout(timer);
        controller?.abort();
        requestId += 1;
        if (clearSuggestions) {
            for (const field of ['nombre', 'direccion']) {
                if (suggestions[field] && form.elements[field].value === suggestions[field]) {
                    form.elements[field].value = '';
                }
                suggestions[field] = '';
            }
        }
    }

    function lookupCoordinates() {
        if (!dialog.open) return;
        const latitudeValue = form.elements.latitud.value;
        const longitudeValue = form.elements.longitud.value;
        const latitude = Number(latitudeValue);
        const longitude = Number(longitudeValue);
        const key = `${latitudeValue},${longitudeValue}`;
        if (key === coordinates) return;
        coordinates = key;
        cancelLookup(true);
        if (!latitudeValue || !longitudeValue || !isInsideBajaCaliforniaSur(latitude, longitude)) {
            geocodeStatus.textContent = 'Selecciona coordenadas dentro de Baja California Sur para sugerir los datos.';
            geocodeStatus.className = 'form-message is-error';
            return;
        }

        geocodeStatus.className = 'form-message';
        geocodeStatus.textContent = 'Buscando sugerencias para esta ubicación…';
        const currentRequest = requestId;
        const currentRevisions = { ...revisions };
        timer = setTimeout(async () => {
            controller = new AbortController();
            try {
                const { details } = await reverseGeocodePlace(latitude, longitude, controller.signal);
                if (currentRequest !== requestId || !dialog.open) return;
                for (const field of ['nombre', 'direccion']) {
                    if (details[field] && !form.elements[field].value && revisions[field] === currentRevisions[field]) {
                        form.elements[field].value = details[field];
                        suggestions[field] = details[field];
                    }
                }
                geocodeStatus.textContent = details.nombre && details.direccion
                    ? 'Revisa las sugerencias y corrígelas si es necesario.'
                    : 'No se encontraron todos los datos. Completa o corrige los campos manualmente.';
            } catch (error) {
                if (error.name === 'AbortError' || currentRequest !== requestId) return;
                console.error('No se pudieron consultar los datos de la ubicación seleccionada.', error);
                geocodeStatus.className = 'form-message is-error';
                geocodeStatus.textContent = 'No se pudieron obtener sugerencias. Puedes escribir el nombre y la dirección manualmente.';
            }
        }, 400);
    }

    for (const field of ['nombre', 'direccion']) {
        form.elements[field].addEventListener('input', () => {
            revisions[field] += 1;
            suggestions[field] = '';
        });
    }
    for (const field of ['latitud', 'longitud']) {
        form.elements[field].addEventListener('input', () => {
            coordinates = null;
            cancelLookup(true);
            geocodeStatus.textContent = 'Termina de introducir las coordenadas para buscar sugerencias.';
            geocodeStatus.className = 'form-message';
        });
        form.elements[field].addEventListener('change', lookupCoordinates);
    }
    dialog.addEventListener('close', () => {
        cancelLookup();
        coordinates = null;
    });

    button.addEventListener('click', () => {
        form.reset();
        message.textContent = '';
        geocodeStatus.textContent = '';
        suggestions.nombre = '';
        suggestions.direccion = '';
        coordinates = null;
        dialog.showModal();
        if (!selector) {
            selector = initializeLocationSelector(form);
            if (!selector) {
                geocodeStatus.textContent = 'No se pudo iniciar el mapa. Introduce los datos manualmente.';
                geocodeStatus.className = 'form-message is-error';
                return;
            }
            selector.map.on('movestart', () => {
                if (!dialog.open) return;
                coordinates = null;
                cancelLookup(true);
            });
            selector.map.on('moveend', lookupCoordinates);
        }
        selector.setCoordinates(null, null);
        selector.refresh();
        lookupCoordinates();
    });

    dialog.querySelectorAll('[data-close-place]').forEach(button => {
        button.addEventListener('click', () => dialog.close());
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const latitude = Number(form.elements.latitud.value);
        const longitude = Number(form.elements.longitud.value);
        if (!isInsideBajaCaliforniaSur(latitude, longitude)) {
            message.textContent = 'Selecciona una ubicación dentro de Baja California Sur.';
            message.className = 'form-message is-error';
            return;
        }

        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        message.textContent = '';
        try {
            const response = await fetch('/api/lugares', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    Authorization: `Bearer ${sessionStorage.getItem('regenera_access_token')}`,
                },
                body: new FormData(form),
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'No se pudo registrar el lugar.');
            }
            if (!result.data?.id || result.data.estado !== 'activo') {
                throw new Error('El lugar no está disponible para seleccionarse. Recarga la página para consultar su estado.');
            }
            onCreated(result.data);
            dialog.close();
        } catch (error) {
            message.textContent = error.message || 'No se pudo registrar el lugar.';
            message.className = 'form-message is-error';
        } finally {
            submit.disabled = false;
        }
    });
}

const select = document.getElementById('lugarId');
if (select) {
    initializePlaceCreator(document.querySelector('[data-add-volunteer-place]'), place => {
        select.options[0].textContent = 'Selecciona un lugar';
        select.add(new Option(`${place.nombre}${place.municipio ? ` · ${place.municipio}` : ''}`, place.id));
        select.value = place.id;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        const message = document.getElementById('placeLoadMessage');
        message.textContent = 'Lugar publicado y seleccionado.';
        message.className = 'form-message is-success';
    });
}
