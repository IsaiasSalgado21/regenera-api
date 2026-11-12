import axios from 'axios';

const page = document.querySelector('[data-personal-profile]');

if (page) {
    const headers = () => {
        const token = sessionStorage.getItem('regenera_access_token');
        return {
            Accept: 'application/json',
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
        };
    };
    const accessMessage = page.querySelector('[data-profile-access]');
    const profileError = page.querySelector('[data-profile-error]');
    const api = (path) => axios.get(path, { headers: headers() });

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;',
    })[character]);

    const formatDate = (value) => {
        if (!value) return 'No disponible';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return 'No disponible';
        return new Intl.DateTimeFormat('es-MX', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(date);
    };

    const formatDateTime = (value, hour) => {
        if (!value) return 'Fecha por confirmar';
        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return 'Fecha por confirmar';
        const formatted = new Intl.DateTimeFormat('es-MX', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        }).format(date);
        return `${formatted}${hour ? ` · ${escapeHtml(hour)}` : ''}`;
    };

    const showSectionError = (element, message) => {
        element.innerHTML = `<p class="profile-inline-error">${escapeHtml(message)}</p>`;
    };

    const setCount = (selector, value) => {
        const element = page.querySelector(selector);
        if (element) element.textContent = value;
    };

    const activityCard = (volunteer, enrollment, attendance) => {
        const status = enrollment.estado === 'cancelada'
            ? 'Inscripción cancelada'
            : volunteer.estado === 'cancelado'
                ? 'Voluntariado cancelado'
                : attendance?.confirmada
                    ? 'Asistió'
                    : enrollment.estado === 'completada'
                        ? 'Participación completada'
                        : volunteer.finalizadoPorFecha || volunteer.estado === 'finalizado'
                            ? 'No asistió'
                            : volunteer.estado === 'pendiente'
                                ? 'Voluntariado en revisión'
                                : 'Inscripción activa';
            const title = ['publicado', 'cancelado', 'finalizado'].includes(volunteer.estado)
                ? `<a href="/voluntariados/${encodeURIComponent(volunteer.id)}">${escapeHtml(volunteer.titulo || 'Voluntariado')}</a>`
                : escapeHtml(volunteer.titulo || 'Voluntariado');
            const requestButton = status === 'No asistió'
                ? `<button type="button" class="profile-request-attendance-button" data-request-attendance="${escapeHtml(volunteer.id)}">Solicitar revisión al propietario</button>`
                : '';
        return `<article class="profile-list-item">
            <div class="profile-list-copy">
                    <h4>${title}</h4>
                <p>${escapeHtml(formatDateTime(volunteer.fechaInicio, volunteer.horaInicio))}${volunteer.lugar?.nombre ? ` · ${escapeHtml(volunteer.lugar.nombre)}` : ''}</p>
            </div>
                <div class="profile-activity-status"><span class="profile-item-status">${escapeHtml(status)}</span>${requestButton}</div>
        </article>`;
    };

        page.addEventListener('click', async event => {
            const button = event.target.closest('[data-request-attendance]');
            if (!button) return;
            button.disabled = true;
            try {
                const token = sessionStorage.getItem('regenera_access_token');
                const response = await axios.post('/api/asistencias/solicitudes', {
                    voluntariadoId: button.dataset.requestAttendance,
                }, { headers: headers() });
                button.textContent = response.data.message || 'Solicitud enviada al propietario.';
            } catch (error) {
                button.disabled = false;
                button.textContent = error.response?.data?.message || 'No se pudo enviar la solicitud.';
            }
        });

    const loadUserActivity = async (usuario) => {
        const upcoming = page.querySelector('[data-upcoming-list]');
        const history = page.querySelector('[data-history-list]');
        const [enrollmentResult, attendanceResult, articleResult] = await Promise.allSettled([
            api('/api/inscripciones'),
            api('/api/asistencias'),
            api('/api/articulos'),
        ]);
        try {
            if (enrollmentResult.status === 'rejected' || attendanceResult.status === 'rejected') {
                throw enrollmentResult.status === 'rejected' ? enrollmentResult.reason : attendanceResult.reason;
            }
            const enrollmentResponse = enrollmentResult.value;
            const attendanceResponse = attendanceResult.value;
            const enrollments = enrollmentResponse.data.inscripciones || [];
            const attendances = (attendanceResponse.data.asistencias || [])
                .filter(item => item.confirmada === true);
            const attendanceByVolunteer = new Map(
                attendances.map(item => [String(item.voluntariadoId), item]),
            );
            const activeEnrollments = enrollments.filter(item => item.estado !== 'cancelada');
            setCount('[data-user-enrollment-count]', activeEnrollments.length);
            setCount('[data-user-attendance-count]', attendances.length);

            const volunteerIds = [...new Set(enrollments.map(item => String(item.voluntariadoId)).filter(Boolean))];
            const volunteerResults = await Promise.allSettled(
                volunteerIds.map(async id => {
                    const response = await api(`/api/voluntariados/${encodeURIComponent(id)}`);
                    return response.data.voluntariado;
                }),
            );
            const volunteers = new Map();
            let failedVolunteerLookups = false;
            volunteerResults.forEach(result => {
                if (result.status === 'fulfilled') {
                    volunteers.set(String(result.value.id ?? result.value._id), result.value);
                } else {
                    failedVolunteerLookups = true;
                }
            });

            const now = Date.now();
            const upcomingItems = [];
            const historyItems = [];
            enrollments.forEach(enrollment => {
                const volunteer = volunteers.get(String(enrollment.voluntariadoId));
                if (!volunteer) return;
                const attendance = attendanceByVolunteer.get(String(enrollment.voluntariadoId));
                const isUpcoming = enrollment.estado !== 'cancelada'
                    && volunteer.estado !== 'cancelado'
                    && !volunteer.finalizadoPorFecha
                    && volunteer.estado !== 'finalizado'
                    && new Date(volunteer.fechaInicio).getTime() >= now
                    && enrollment.estado !== 'completada'
                    && !attendance;
                (isUpcoming ? upcomingItems : historyItems).push({
                    volunteer,
                    enrollment,
                    attendance,
                });
            });
            upcomingItems.sort((a, b) => new Date(a.volunteer.fechaInicio) - new Date(b.volunteer.fechaInicio));
            historyItems.sort((a, b) => new Date(b.volunteer.fechaInicio) - new Date(a.volunteer.fechaInicio));

            upcoming.innerHTML = upcomingItems.length
                ? upcomingItems.map(item => activityCard(item.volunteer, item.enrollment, item.attendance)).join('')
                : '<p class="profile-muted">No tienes voluntariados próximos inscritos.</p>';
            history.innerHTML = historyItems.length
                ? historyItems.map(item => activityCard(item.volunteer, item.enrollment, item.attendance)).join('')
                : '<p class="profile-muted">Aún no tienes participaciones en tu historial.</p>';
            if (failedVolunteerLookups) {
                const notice = '<p class="profile-inline-error">Algunos detalles de actividades no están disponibles en este momento.</p>';
                upcoming.insertAdjacentHTML('beforeend', notice);
            }

            const articleList = page.querySelector('[data-profile-articles]');
            if (articleResult.status === 'rejected') {
                showSectionError(articleList, 'No se pudieron cargar tus artículos.');
                return;
            }
            const articles = (articleResult.value.data.data || [])
                .filter(article => String(article.autorId) === String(usuario.id))
                .sort((a, b) => new Date(b.fechaActualizacion || b.fechaPublicacion || 0) - new Date(a.fechaActualizacion || a.fechaPublicacion || 0));
            articleList.innerHTML = articles.length
                ? articles.map(article => `<article class="profile-article-item">
                    <div><h4><a href="/articulos/${encodeURIComponent(article.id)}">${escapeHtml(article.titulo)}</a></h4>
                    <p>${escapeHtml(formatDate(article.fechaActualizacion || article.fechaPublicacion))}</p></div>
                    <span class="profile-item-status">${escapeHtml(article.estado || 'Sin estado')}</span>
                </article>`).join('')
                : '<p class="profile-muted">Todavía no tienes artículos propios.</p>';
        } catch (error) {
            const message = error.response?.status === 401
                ? 'Tu sesión expiró. Inicia sesión nuevamente para consultar tu actividad.'
                : 'No se pudo cargar toda tu actividad. Intenta de nuevo más tarde.';
            showSectionError(upcoming, message);
            showSectionError(history, message);
            showSectionError(page.querySelector('[data-profile-articles]'), message);
            setCount('[data-user-enrollment-count]', '—');
            setCount('[data-user-attendance-count]', '—');
        }
    };

    const loadOrganizerActivity = async () => {
        const list = page.querySelector('[data-organizer-list]');
        try {
            const response = await api('/api/admin/voluntariados');
            const volunteers = response.data.data || [];
            const statuses = volunteers.reduce((counts, volunteer) => {
                if (volunteer.estado === 'publicado') counts.published += 1;
                if (volunteer.estado === 'pendiente') counts.pending += 1;
                if (volunteer.estado === 'finalizado') counts.finished += 1;
                return counts;
            }, { published: 0, pending: 0, finished: 0 });
            setCount('[data-organizer-total]', volunteers.length);
            setCount('[data-organizer-published]', statuses.published);
            setCount('[data-organizer-pending]', statuses.pending);
            setCount('[data-organizer-finished]', statuses.finished);
            list.innerHTML = volunteers.length
                ? volunteers.map(volunteer => `<article class="profile-list-item">
                    <div class="profile-list-copy">
                        <h4><a href="/voluntariados/${encodeURIComponent(volunteer.id)}">${escapeHtml(volunteer.titulo || 'Voluntariado')}</a></h4>
                        <p>${escapeHtml(formatDateTime(volunteer.fechaInicio, volunteer.horaInicio))}</p>
                    </div>
                    <span class="profile-item-status">${escapeHtml(volunteer.estado || 'Sin estado')}</span>
                </article>`).join('')
                : '<p class="profile-muted">Aún no has creado voluntariados.</p>';

            if (!volunteers.length) {
                setCount('[data-organizer-participants]', '0');
                setCount('[data-organizer-attendances]', '0');
                return;
            }
            const dashboards = [];
            for (let index = 0; index < volunteers.length; index += 5) {
                const batch = await Promise.allSettled(volunteers.slice(index, index + 5).map(volunteer =>
                    api(`/api/admin/voluntariados/${encodeURIComponent(volunteer.id)}/dashboard`),
                ));
                dashboards.push(...batch);
            }
            const hasFailures = dashboards.some(result => result.status === 'rejected');
            const statistics = dashboards
                .filter(result => result.status === 'fulfilled')
                .map(result => result.value.data.data.estadisticas);
            setCount('[data-organizer-participants]', hasFailures
                ? '—'
                : statistics.reduce((total, item) => total + Number(item.inscritos || 0), 0));
            setCount('[data-organizer-attendances]', hasFailures
                ? '—'
                : statistics.reduce((total, item) => total + Number(item.asistencias || 0), 0));
            if (hasFailures) {
                list.insertAdjacentHTML('beforeend', '<p class="profile-inline-error">No se pudieron consultar todas las cifras de participantes y asistencias.</p>');
            }
        } catch (error) {
            showSectionError(list, error.response?.status === 401
                ? 'Tu sesión expiró. Inicia sesión nuevamente.'
                : 'No se pudieron cargar tus voluntariados.');
        }
    };

    const showProfile = async () => {
        if (!sessionStorage.getItem('regenera_access_token')) {
            accessMessage.hidden = false;
            accessMessage.innerHTML = 'Inicia sesión para ver tu perfil personal. <a href="/login">Iniciar sesión</a>';
            page.querySelector('[data-profile-identity]').hidden = true;
            page.querySelectorAll('[data-profile-section]').forEach(section => { section.hidden = true; });
            return;
        }

        accessMessage.hidden = true;
        profileError.hidden = true;
        page.querySelector('[data-profile-identity]').hidden = false;
        try {
            const response = await api('/api/auth/me');
            const usuario = response.data.usuario;
            const roleLabels = {
                usuario: 'Usuario',
                organizador: 'Organizador',
                admin: 'Administrador',
            };
            const avatar = page.querySelector('[data-profile-avatar]');
            page.querySelector('[data-profile-name]').textContent = usuario.nombre || 'Usuario';
            page.querySelector('[data-profile-email]').textContent = usuario.correo || '';
            page.querySelector('[data-profile-role]').textContent = roleLabels[usuario.rol] || usuario.rol || 'Cuenta';
            page.querySelector('[data-profile-state]').textContent = usuario.estado === 'activo' ? 'Cuenta activa' : (usuario.estado || 'Estado no disponible');
            page.querySelector('[data-profile-joined]').textContent = formatDate(usuario.fechaRegistro);
            if (usuario.foto) {
                avatar.innerHTML = `<img src="${escapeHtml(usuario.foto)}" alt="Foto de perfil de ${escapeHtml(usuario.nombre)}">`;
                avatar.setAttribute('aria-hidden', 'false');
                avatar.querySelector('img').addEventListener('error', () => {
                    avatar.textContent = (usuario.nombre || 'R').trim().charAt(0).toLocaleUpperCase('es-MX');
                    avatar.setAttribute('aria-hidden', 'true');
                }, { once: true });
            } else {
                avatar.textContent = (usuario.nombre || 'R').trim().charAt(0).toLocaleUpperCase('es-MX');
            }
            const verification = page.querySelector('[data-profile-verification]');
            const isVerifiedOrganizer = usuario.rol === 'organizador' && Boolean(usuario.organizadorVerificado);
            verification.hidden = usuario.rol !== 'organizador';
            verification.textContent = isVerifiedOrganizer ? 'Organizador verificado' : 'Verificación pendiente';
            verification.classList.toggle('is-verified', isVerifiedOrganizer);
            page.querySelectorAll('[data-profile-section]').forEach(section => {
                section.hidden = section.dataset.profileSection !== usuario.rol;
            });

            if (usuario.rol === 'usuario') await loadUserActivity(usuario);
            if (usuario.rol === 'organizador') await loadOrganizerActivity();
        } catch (error) {
            profileError.hidden = false;
            profileError.textContent = error.response?.status === 401
                ? 'Tu sesión expiró. Inicia sesión nuevamente para consultar tu perfil.'
                : 'No se pudo cargar tu perfil. Intenta de nuevo más tarde.';
        }
    };

    showProfile();
    document.addEventListener('regenera:auth-changed', showProfile);
}
