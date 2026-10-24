import axios from 'axios';

const layerEndpoints = {
    voluntariados: '/api/v1/mapa/voluntariados',
    lugares: '/api/v1/mapa/lugares',
    'flora-fauna': '/api/v1/mapa/flora-fauna',
};
const requests = new Map();

function tokenHeaders() {
    const token = sessionStorage.getItem('regenera_access_token');
    return token ? { Authorization: `Bearer ${token}` } : {};
}

export async function fetchMapLayer(layer, params = {}) {
    const endpoint = layerEndpoints[layer];
    if (!endpoint) throw new Error('La capa solicitada no está configurada.');

    requests.get(layer)?.abort();
    const controller = new AbortController();
    requests.set(layer, controller);

    try {
        const response = await axios.get(endpoint, {
            params,
            signal: controller.signal,
            headers: { Accept: 'application/json' },
        });

        if (!Array.isArray(response.data?.data)) {
            throw new Error('El servicio del mapa devolvió una respuesta no válida.');
        }

        return response.data.data;
    } catch (error) {
        if (axios.isCancel(error) || error.name === 'CanceledError') throw error;
        const message = error.response?.status === 422
            ? 'Los filtros no son válidos. Revisa los valores e inténtalo de nuevo.'
            : error.response?.status === 404
                ? 'El servicio de esta capa todavía no está disponible.'
                : 'No se pudo cargar esta capa. Comprueba tu conexión e inténtalo de nuevo.';
        throw new Error(message, { cause: error });
    } finally {
        if (requests.get(layer) === controller) requests.delete(layer);
    }
}

export async function fetchCurrentUser() {
    const headers = tokenHeaders();
    if (!headers.Authorization) return null;

    try {
        const response = await axios.get('/api/auth/me', {
            headers: { ...headers, Accept: 'application/json' },
        });
        return response.data?.usuario || null;
    } catch (error) {
        if (error.response?.status === 401) sessionStorage.removeItem('regenera_access_token');
        const message = error.response?.status === 401
            ? 'Tu sesión no es válida. Inicia sesión nuevamente para inscribirte.'
            : 'No se pudo verificar la sesión.';
        throw new Error(message, { cause: error });
    }
}

export async function enrollInVolunteer(volunteerId) {
    try {
        const response = await axios.post('/api/inscripciones', {
            voluntariadoId: volunteerId,
        }, {
            headers: {
                ...tokenHeaders(),
                Accept: 'application/json',
            },
        });

        return response.data;
    } catch (error) {
        const message = error.response?.status === 401
            ? 'Inicia sesión nuevamente para inscribirte.'
            : error.response?.status === 422
                ? 'No se pudo validar la inscripción. Revisa la información e inténtalo de nuevo.'
                : error.response?.data?.message;
        throw new Error(
            message || 'No se pudo completar la inscripción. Inténtalo de nuevo.',
            { cause: error }
        );
    }
}
