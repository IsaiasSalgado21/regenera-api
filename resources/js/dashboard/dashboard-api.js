import axios from 'axios';

function accessToken() {
    return sessionStorage.getItem('regenera_access_token');
}

function headers() {
    const token = accessToken();
    return {
        Accept: 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
    };
}

async function request(method, path, data) {
    try {
        const response = await axios.request({
            method,
            url: path,
            data,
            headers: headers(),
        });

        return response.data;
    } catch (error) {
        if (error.response?.status === 401) {
            sessionStorage.removeItem('regenera_access_token');
        }

        const message = error.response?.status === 422
            ? 'Revisa los campos del formulario e inténtalo de nuevo.'
            : error.response?.status === 403
                ? 'No tienes permiso para realizar esta acción.'
                : error.response?.data?.message
                    || 'No se pudo completar la solicitud. Revisa tu conexión e inténtalo de nuevo.';
        throw new Error(message, { cause: error });
    }
}

export const dashboardApi = {
    currentUser: () => request('get', '/api/auth/me'),
    listPlaces: role => request('get', role === 'admin' ? '/api/admin/lugares' : '/api/lugares'),
    listFlora: role => request('get', role === 'admin' ? '/api/admin/flora-fauna' : '/api/flora-fauna'),
    listArticles: () => request('get', '/api/articulos'),
    listPendingArticles: () => request('get', '/api/admin/articulos/pendientes'),
    savePlace: (id, data) => request(id ? 'put' : 'post', id ? `/api/lugares/${id}` : '/api/lugares', data),
    saveFlora: (id, data) => request(id ? 'put' : 'post', id ? `/api/flora-fauna/${id}` : '/api/flora-fauna', data),
    saveArticle: (id, data) => request(id ? 'put' : 'post', id ? `/api/articulos/${id}` : '/api/articulos', data),
    archivePlace: id => request('patch', `/api/admin/lugares/${id}/archivar`),
    archiveFlora: id => request('patch', `/api/admin/flora-fauna/${id}/archivar`),
    submitArticle: id => request('patch', `/api/articulos/${id}/enviar-revision`),
    approveArticle: id => request('patch', `/api/admin/articulos/${id}/aprobar`),
    rejectArticle: (id, motivo) => request('patch', `/api/admin/articulos/${id}/rechazar`, { motivo }),
    unpublishArticle: id => request('patch', `/api/admin/articulos/${id}/despublicar`),
    deleteDraft: id => request('delete', `/api/admin/articulos/${id}`),
    unreadNotifications: () => request('get', '/api/notificaciones/no-leidas'),
    allNotifications: () => request('get', '/api/notificaciones'),
    markNotificationRead: id => request('patch', `/api/notificaciones/${id}/leida`),
    markAllNotificationsRead: () => request('patch', '/api/notificaciones/marcar-todas-leidas'),
    archivePlace: id => request('patch', `/api/admin/lugares/${id}/archivar`),
    publishPlace: id => request('patch', `/api/admin/lugares/${id}/publicar`),

    archiveFlora: id => request('patch', `/api/admin/flora-fauna/${id}/archivar`),
    publishFlora: id => request('patch', `/api/admin/flora-fauna/${id}/publicar`),

    publishArticle: id => request('patch', `/api/admin/articulos/${id}/publicar`),
    unpublishArticle: id => request('patch', `/api/admin/articulos/${id}/despublicar`),
};
