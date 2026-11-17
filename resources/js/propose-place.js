import { initializeLocationSelector } from './map-selector';
import { isInsideBajaCaliforniaSur } from './geo/baja-california-sur';

const dialog = document.getElementById('proposePlaceDialog');
const form = document.getElementById('proposePlaceForm');
const select = document.getElementById('lugarId');
const message = document.getElementById('proposePlaceMessage');
const placeMessage = document.getElementById('placeLoadMessage');

if (dialog && form && select && message && placeMessage) {
    let selector;
    let previousValue = '';

    select.addEventListener('change', () => {
        if (select.value !== '__propose_place__') {
            previousValue = select.value;
            return;
        }
        select.value = previousValue;
        form.reset();
        message.textContent = '';
        dialog.showModal();
        selector = initializeLocationSelector(form);
        selector.setCoordinates(null, null);
        selector.refresh();
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
            dialog.close();
            placeMessage.textContent = 'Lugar enviado a revisión. Estará disponible después de su aprobación.';
            placeMessage.className = 'form-message';
        } catch (error) {
            message.textContent = error.message || 'No se pudo registrar el lugar.';
            message.className = 'form-message is-error';
        } finally {
            submit.disabled = false;
        }
    });
}
