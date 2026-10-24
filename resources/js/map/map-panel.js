import { enrollInVolunteer } from './map-api.js';

function addText(parent, tag, text, className = '') {
    const element = document.createElement(tag);
    element.textContent = text;
    if (className) element.className = className;
    parent.appendChild(element);
    return element;
}

export function createMapPanel({ panel, getUser, getAuthError, onEnrolled }) {
    return function showFeature(item) {
        panel.replaceChildren();

        const type = item.tipo === 'voluntariado'
            ? 'Voluntariado'
            : item.tipo === 'lugar'
                ? 'Lugar'
                : item.tipo === 'flora'
                    ? 'Flora'
                    : 'Fauna';
        addText(panel, 'p', type, 'map-panel-type');
        addText(panel, 'h2', item.titulo || item.nombre || 'Registro del mapa');

        if (item.descripcion) addText(panel, 'p', item.descripcion);
        if (item.nombre_cientifico) addText(panel, 'p', item.nombre_cientifico);
        if (item.estado_conservacion) addText(panel, 'p', `Conservación: ${item.estado_conservacion}`);
        if (item.lugar_nombre) addText(panel, 'p', `Lugar: ${item.lugar_nombre}`);
        if (item.cupo !== undefined) {
            addText(panel, 'p', `Inscripciones: ${item.inscripciones_activas || 0} de ${item.cupo}`);
        }
        if (item.tipo_lugar) addText(panel, 'p', `Tipo: ${item.tipo_lugar}`);
        if (item.fecha_inicio) {
            addText(panel, 'p', `Fecha: ${new Date(item.fecha_inicio).toLocaleString('es-MX')}`);
        }
        if (item.zona_aproximada) {
            addText(panel, 'p', 'La ubicación se muestra como una zona aproximada para proteger esta especie.');
        }

        if (item.tipo !== 'voluntariado') return;

        const user = getUser();
        let reason = '';
        if (getAuthError()) reason = getAuthError();
        else if (!user) reason = 'Inicia sesión para consultar si puedes inscribirte.';
        else if (!['usuario', 'organizador'].includes(user.rol)) reason = 'Tu rol no permite inscribirte a voluntariados.';
        else if (user.estado !== 'activo') reason = 'Tu cuenta no está activa.';
        else if (item.estado !== 'publicado') reason = 'Este voluntariado no está publicado.';
        else if (item.lleno) reason = 'Este voluntariado ya no tiene cupo disponible.';

        if (reason) {
            addText(panel, 'p', reason, 'map-panel-message');
            return;
        }

        const button = addText(panel, 'button', 'Inscribirse');
        button.type = 'button';
        button.dataset.tooltip = 'La inscripción será validada nuevamente por el servidor.';
        button.addEventListener('click', async () => {
            button.disabled = true;
            const message = addText(panel, 'p', 'Registrando inscripción…', 'map-panel-message');

            try {
                const response = await enrollInVolunteer(item.id);
                message.textContent = response.message || 'Inscripción registrada correctamente.';
                button.textContent = 'Inscripción registrada';
                button.disabled = true;
                await onEnrolled();
            } catch (error) {
                message.textContent = error.message;
                message.classList.add('is-error');
                button.disabled = false;
            }
        });
    };
}
