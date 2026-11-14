import axios from 'axios';
import { initializeAvatarCreator, renderAvatarMarkup, selectAvatarReward } from './avatar-creator';

const page = document.querySelector('[data-personal-profile]');

if (page) {
    const apiBaseUrl = document.querySelector('meta[name="api-base-url"]')?.content || '/api';
    const appBaseUrl = apiBaseUrl.replace(/\/api\/?$/, '');
    const apiUrl = path => path.startsWith('/api/') ? `${apiBaseUrl}${path.slice(4)}` : path;
    const headers = () => {
        const token = sessionStorage.getItem('regenera_access_token');
        return {
            Accept: 'application/json',
            ...(token ? { Authorization: `Bearer ${token}` } : {}),
        };
    };
    const accessMessage = page.querySelector('[data-profile-access]');
    const profileError = page.querySelector('[data-profile-error]');
    const gamificationSection = page.querySelector('[data-gamification-profile]');
    const avatarDialog = page.querySelector('[data-avatar-dialog]');
    let gamificationHistory = [];
    let pendingAvatarReward = null;
    const api = (path) => axios.get(apiUrl(path), { headers: headers() });
    const appUrl = path => `${appBaseUrl}${path}`;

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

    const renderProfileAvatar = usuario => {
        const avatar = page.querySelector('[data-profile-avatar]');
        if (usuario.foto) {
            avatar.innerHTML = `<img src="${escapeHtml(usuario.foto)}" alt="Avatar de ${escapeHtml(usuario.nombre)}">`;
            avatar.setAttribute('aria-hidden', 'false');
            avatar.querySelector('img').addEventListener('error', () => {
                avatar.innerHTML = renderAvatarMarkup(usuario.avatarConfig || {});
            }, { once: true });
            return;
        }

        avatar.innerHTML = renderAvatarMarkup(usuario.avatarConfig || {});
        avatar.setAttribute('aria-hidden', 'false');
    };

    const initializeProfileAvatar = (usuario, progress) => {
        const earnedMedals = (progress?.medallasObtenidas || [])
            .filter(medal => medal.desbloqueada)
            .map(medal => medal.slug);

        initializeAvatarCreator({
            root: avatarDialog,
            container: avatarDialog.querySelector('#avatar-container'),
            configuration: usuario.avatarConfig || {},
            earnedMedals,
            onSave: async ({ configuration, image }) => {
                const formData = new FormData();
                formData.append('avatarConfig', JSON.stringify(configuration));
                formData.append('avatar', image, 'avatar.png');
                let response;
                try {
                    response = await axios.post(apiUrl('/api/auth/avatar'), formData, { headers: headers() });
                } catch (error) {
                    const validationMessage = Object.values(error.response?.data?.errors || {}).flat()[0];
                    throw new Error(validationMessage || error.response?.data?.message || error.message);
                }
                usuario.avatarConfig = response.data.usuario.avatarConfig;
                usuario.foto = response.data.usuario.foto;
                renderProfileAvatar(usuario);
                if (pendingAvatarReward && configuration.accessory === pendingAvatarReward.item) {
                    markAvatarRewardSeen(pendingAvatarReward.medal, usuario.id);
                    pendingAvatarReward = null;
                }
            },
        });

        const rewardCatalog = [
            { medal: 'voluntario-seguro', item: 'hat', name: 'Gorra de explorador' },
            { medal: 'guardian-manglar', item: 'mangrove-leaf', name: 'Insignia de manglar' },
            { medal: 'agente-circular', item: 'recycling-badge', name: 'Insignia circular' },
            { medal: 'lider-responsable', item: 'organizer-vest', name: 'Chaleco de organizador' },
        ];
        const earned = new Set(earnedMedals);
        const availableReward = rewardCatalog.find(reward => earned.has(reward.medal)
            && usuario.avatarConfig?.accessory !== reward.item
            && !hasSeenAvatarReward(reward.medal, usuario.id));
        const rewardPanel = avatarDialog.querySelector('[data-avatar-reward]');
        rewardPanel.hidden = !availableReward;
        if (availableReward) {
            rewardPanel.dataset.rewardItem = availableReward.item;
            rewardPanel.dataset.rewardMedal = availableReward.medal;
            rewardPanel.dataset.userId = usuario.id;
            avatarDialog.querySelector('[data-avatar-reward-title]').textContent = `Nueva recompensa: ${availableReward.name}`;
            avatarDialog.querySelector('[data-avatar-reward-copy]').textContent = 'La desbloqueaste con una medalla. Puedes probarla ahora y guardar el cambio, o volver después.';
        }

        const publicLink = page.querySelector('[data-profile-public-link]');
        publicLink.href = appUrl(`/perfil/${encodeURIComponent(usuario.id)}`);
        publicLink.hidden = false;
    };

    const avatarRewardSeenKey = userId => `regenera_avatar_rewards_seen_${userId}`;
    const hasSeenAvatarReward = (medal, userId) => {
        try {
            return JSON.parse(localStorage.getItem(avatarRewardSeenKey(userId)) || '[]').includes(medal);
        } catch {
            return false;
        }
    };
    const markAvatarRewardSeen = (medal, userId) => {
        const key = avatarRewardSeenKey(userId);
        try {
            const seen = JSON.parse(localStorage.getItem(key) || '[]');
            if (!seen.includes(medal)) localStorage.setItem(key, JSON.stringify([...seen, medal]));
        } catch {
            return;
        }
    };

    const medalSvg = iconName => {
        const symbols = { 'shield-check': 'proteccion', recycle: 'reciclaje' };
        if (Object.hasOwn(symbols, iconName) && document.body.dataset.symbolsBase) {
            const base = escapeHtml(document.body.dataset.symbolsBase.replace(/\/+$/, ''));
            return `<img class="ui-symbol" src="${base}/${symbols[iconName]}.png" alt="" aria-hidden="true" width="23" height="23">`;
        }
        const icons = {
            'shield-check': '<path d="M12 3 19 6v5c0 4.5-2.9 8-7 10-4.1-2-7-5.5-7-10V6l7-3Z"/><path d="m9 12 2 2 4-4"/>',
            mangrove: '<path d="M12 20V9"/><path d="M12 13 7 9m5 7 5-5"/><path d="M5 8c0-2 2-3 4-2 0-2 2-3 4-2 2-1 4 1 4 3 2 0 3 2 2 4-1 2-4 2-6 1-2 2-5 2-7 0-2 0-3-2-1-4Z"/><path d="M6 20h12"/>',
            recycle: '<path d="m7 7 2-3 3 1"/><path d="m9 4 2 4H6"/><path d="m17 8 3 1v4"/><path d="m20 9-4 2 2 3"/><path d="m8 17-1 3-4-1"/><path d="m7 20-1-4 4 1"/><path d="M8 8a5 5 0 0 1 8 1m1 4a5 5 0 0 1-4 5m-4-1a5 5 0 0 1-2-6"/>',
            leader: '<circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/><path d="m18 4 1 2 2 1-2 1-1 2-1-2-2-1 2-1 1-2Z"/>',
        };
        const locked = '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/><path d="M12 14v3"/>';
        return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${icons[iconName] || locked}</svg>`;
    };

    const renderGamificationTimeline = (items) => {
        const timeline = page.querySelector('[data-gamification-timeline]');
        const rows = [...items].filter(item => item.fecha).sort((first, second) => new Date(second.fecha) - new Date(first.fecha));
        timeline.innerHTML = rows.length
            ? rows.map(item => `<article class="profile-timeline-item is-${escapeHtml(item.tipo)}">
                <span class="profile-timeline-mark" aria-hidden="true"></span>
                <div><h4>${escapeHtml(item.motivo || item.titulo || 'Actividad')}</h4>
                <p>${escapeHtml(formatDateTime(item.fecha))}${item.detalle ? ` · ${escapeHtml(item.detalle)}` : ''}</p></div>
            </article>`).join('')
            : '<p class="profile-muted">Aún no tienes logros o participaciones registradas.</p>';
    };

    const renderGamification = (progress, usuario) => {
        gamificationHistory = progress.historial || [];
        const quizHistory = (progress.historialCuestionarios || []).map(attempt => ({
            tipo: 'cuestionario',
            motivo: `${attempt.estado === 'aprobado' ? 'Cuestionario aprobado' : attempt.estado === 'no_aprobado' ? 'Cuestionario no aprobado' : 'Cuestionario en progreso'}: ${attempt.cuestionarioSlug}`,
            fecha: attempt.fechaUltimoIntento || attempt.fechaAprobacion,
            detalle: attempt.puntuacion === null ? null : `${attempt.puntuacion}%`,
        }));
        gamificationHistory = [...gamificationHistory, ...quizHistory];
        gamificationSection.hidden = false;
        setCount('[data-gamification-points]', new Intl.NumberFormat('es-MX').format(progress.puntos || 0));
        setCount('[data-gamification-quizzes]', `${progress.cuestionariosAprobados || 0} / ${progress.cuestionariosAsignados || 0}`);
        setCount('[data-gamification-security]', progress.seguridadValidada ? 'Validada' : 'Pendiente');
        setCount('[data-gamification-activities]', progress.actividadesRealizadas || 0);
        setCount('[data-gamification-hours]', `${new Intl.NumberFormat('es-MX', { maximumFractionDigits: 2 }).format(progress.horasParticipacion || 0)} h`);
        setCount('[data-gamification-ecosystems]', progress.ecosistemasBCSAprendidos || 0);
        setCount('[data-gamification-circular-knowledge]', progress.conocimientoEconomiaCircular === null
            ? 'Pendiente'
            : `${new Intl.NumberFormat('es-MX', { maximumFractionDigits: 2 }).format(progress.conocimientoEconomiaCircular)}%`);
        setCount('[data-gamification-level]', `Nivel ${progress.nivel || 1}`);

        const medals = progress.medallas || [];
        const unlockedCount = medals.filter(medal => medal.desbloqueada).length;
        setCount('[data-gamification-medal-count]', `${unlockedCount} / ${medals.length}`);
        page.querySelector('[data-gamification-medals]').innerHTML = medals.map(medal => `<article class="profile-medal ${medal.desbloqueada ? 'is-unlocked' : 'is-locked'}">
            <span class="profile-medal-icon">${medalSvg(medal.desbloqueada ? medal.icono : 'locked')}</span>
            <span class="profile-medal-state">${medal.desbloqueada ? 'Obtenida' : 'Por desbloquear'}</span>
            <h4>${escapeHtml(medal.nombre)}</h4>
            <p>${escapeHtml(medal.descripcion)}</p>
            <small>${escapeHtml(medal.requisito)}</small>
            ${medal.fechaObtencion ? `<time datetime="${escapeHtml(medal.fechaObtencion)}">${escapeHtml(formatDate(medal.fechaObtencion))}</time>` : ''}
        </article>`).join('');

        const nextMedal = progress.siguienteMedalla;
        const nextMedalCard = page.querySelector('[data-next-medal]');
        const completeNote = page.querySelector('[data-next-medal-complete]');
        nextMedalCard.hidden = !nextMedal;
        completeNote.hidden = Boolean(nextMedal);
        if (nextMedal) {
            page.querySelector('[data-next-medal-name]').textContent = nextMedal.nombre;
            page.querySelector('[data-next-medal-description]').textContent = nextMedal.descripcion;
            const percentage = nextMedal.progreso?.porcentaje;
            const safePercentage = Number.isFinite(percentage) ? Math.min(100, Math.max(0, percentage)) : 0;
            page.querySelector('[data-next-medal-bar]').style.width = `${safePercentage}%`;
            page.querySelector('[data-next-medal-progress]').textContent = nextMedal.progreso
                ? `Cuestionario: ${safePercentage}%`
                : (nextMedal.observacion || 'Progreso no disponible.');
            page.querySelector('[data-next-medal-requirements]').innerHTML = (nextMedal.requisitos || []).map(requirement => `
                <li class="${requirement.cumplido === true ? 'is-complete' : ''}">
                    <span aria-hidden="true">${requirement.cumplido === true ? '✓' : requirement.cumplido === false ? '○' : '—'}</span>
                    ${escapeHtml(requirement.descripcion)}
                </li>`).join('');
            page.querySelector('[data-next-medal-link]').href = appUrl(`/aprendizaje?cuestionario=${encodeURIComponent(nextMedal.cuestionarioSlug)}`);
        }

        const courses = progress.cuestionarios || [];
        page.querySelector('[data-gamification-courses]').innerHTML = courses.length
            ? courses.map(course => `<a class="profile-learning-item" href="${appUrl(`/aprendizaje?cuestionario=${encodeURIComponent(course.slug)}`)}">
                <span><strong>${escapeHtml(course.titulo)}</strong><small>${escapeHtml(course.estado === 'aprobado' ? `Aprobado · ${course.puntuacion ?? 0}%` : course.bloqueo || course.estado)}</small></span>
                <span aria-hidden="true">${course.estado === 'aprobado' && document.body.dataset.symbolsBase
                    ? `<img class="ui-symbol" src="${escapeHtml(document.body.dataset.symbolsBase.replace(/\/+$/, ''))}/confirmado.png" alt="" aria-hidden="true" width="20" height="20">`
                    : course.estado === 'aprobado' ? '✓' : '→'}</span>
            </a>`).join('')
            : '<p class="profile-muted">No hay cuestionarios asignados a tu perfil.</p>';

        const certification = page.querySelector('[data-organizer-certification]');
        certification.hidden = usuario.rol !== 'organizador';
        if (usuario.rol === 'organizador') {
            const status = progress.certificacionOrganizador?.certificado
                ? `Certificación de organizador aprobada${progress.certificacionOrganizador.fechaAprobacion ? ` · ${formatDate(progress.certificacionOrganizador.fechaAprobacion)}` : ''}`
                : 'Certificación de organizador pendiente';
            certification.textContent = status;
            certification.classList.toggle('is-certified', Boolean(progress.certificacionOrganizador?.certificado));
        }
        renderGamificationTimeline(gamificationHistory);
    };

    const loadGamification = async usuario => {
        try {
            const response = await api('/api/gamificacion/progreso');
            renderGamification(response.data.data, usuario);
            return response.data.data;
        } catch (error) {
            gamificationSection.hidden = false;
            page.querySelector('[data-gamification-error]').hidden = false;
            page.querySelector('[data-gamification-error]').textContent = error.response?.data?.message
                || 'No se pudo cargar tu progreso guardado.';
            return null;
        }
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
                ? `<a href="${appUrl(`/voluntariados/${encodeURIComponent(volunteer.id)}`)}">${escapeHtml(volunteer.titulo || 'Voluntariado')}</a>`
                : escapeHtml(volunteer.titulo || 'Voluntariado');
            const requestButton = status === 'No asistió'
                ? `<button type="button" class="profile-request-attendance-button" data-request-attendance="${escapeHtml(volunteer.id)}">Solicitar revisión al propietario</button>`
                : '';
        return `<article class="profile-list-item">
            <div class="profile-list-copy">
                    <h4>${title}</h4>
                <p>Organiza: ${escapeHtml(volunteer.organizadorNombre || 'No disponible')}</p>
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
                const response = await axios.post(apiUrl('/api/asistencias/solicitudes'), {
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
            const activityTimeline = historyItems.map(item => ({
                tipo: 'actividad',
                motivo: item.attendance?.confirmada ? 'Asistencia confirmada' : item.enrollment.estado === 'cancelada' ? 'Inscripción cancelada' : 'Actividad inscrita',
                fecha: item.volunteer.fechaInicio,
                detalle: item.volunteer.titulo,
            }));
            renderGamificationTimeline([...gamificationHistory, ...activityTimeline]);
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
                    <div><h4><a href="${appUrl(`/articulos/${encodeURIComponent(article.id)}`)}">${escapeHtml(article.titulo)}</a></h4>
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
                        <h4><a href="${appUrl(`/voluntariados/${encodeURIComponent(volunteer.id)}`)}">${escapeHtml(volunteer.titulo || 'Voluntariado')}</a></h4>
                        <p>Organiza: ${escapeHtml(volunteer.organizadorNombre || 'No disponible')}</p>
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
            accessMessage.innerHTML = `Inicia sesión para ver tu perfil personal. <a href="${appUrl('/login')}">Iniciar sesión</a>`;
            page.querySelector('[data-profile-identity]').hidden = true;
            page.querySelectorAll('[data-profile-section]').forEach(section => { section.hidden = true; });
            gamificationSection.hidden = true;
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
            page.querySelector('[data-profile-name]').textContent = usuario.nombre || 'Usuario';
            page.querySelector('[data-profile-email]').textContent = usuario.correo || '';
            page.querySelector('[data-profile-role]').textContent = roleLabels[usuario.rol] || usuario.rol || 'Cuenta';
            page.querySelector('[data-profile-state]').textContent = usuario.estado === 'activo' ? 'Cuenta activa' : (usuario.estado || 'Estado no disponible');
            page.querySelector('[data-profile-joined]').textContent = formatDate(usuario.fechaRegistro);
            renderProfileAvatar(usuario);
            const verification = page.querySelector('[data-profile-verification]');
            const isVerifiedOrganizer = usuario.rol === 'organizador' && Boolean(usuario.organizadorVerificado);
            verification.hidden = usuario.rol !== 'organizador';
            verification.textContent = isVerifiedOrganizer ? 'Organizador verificado' : 'Verificación pendiente';
            verification.classList.toggle('is-verified', isVerifiedOrganizer);
            page.querySelectorAll('[data-profile-section]').forEach(section => {
                section.hidden = section.dataset.profileSection !== usuario.rol;
            });

            const progress = await loadGamification(usuario);
            initializeProfileAvatar(usuario, progress);
            if (usuario.rol === 'usuario') await loadUserActivity(usuario);
            if (usuario.rol === 'organizador') await loadOrganizerActivity();
        } catch (error) {
            profileError.hidden = false;
            profileError.textContent = error.response?.status === 401
                ? 'Tu sesión expiró. Inicia sesión nuevamente para consultar tu perfil.'
                : 'No se pudo cargar tu perfil. Intenta de nuevo más tarde.';
        }
    };

    page.addEventListener('click', event => {
        if (event.target.closest('[data-avatar-open]')) avatarDialog.showModal();
        if (event.target.closest('[data-avatar-close]')) avatarDialog.close();
        if (event.target.closest('[data-avatar-reward-equip]')) {
            const reward = avatarDialog.querySelector('[data-avatar-reward]');
            pendingAvatarReward = { item: reward.dataset.rewardItem, medal: reward.dataset.rewardMedal };
            if (selectAvatarReward(reward.dataset.rewardItem)) {
                reward.hidden = true;
                avatarDialog.querySelector('[data-avatar-status]').textContent = 'Cosmético equipado. Guarda el avatar para conservar el cambio.';
            }
        }
        if (event.target.closest('[data-avatar-reward-dismiss]')) {
            const reward = avatarDialog.querySelector('[data-avatar-reward]');
            markAvatarRewardSeen(reward.dataset.rewardMedal, reward.dataset.userId);
            reward.hidden = true;
        }
    });
    avatarDialog.addEventListener('click', event => {
        if (event.target === avatarDialog) avatarDialog.close();
    });
    avatarDialog.addEventListener('cancel', event => {
        event.preventDefault();
        avatarDialog.close();
    });
    avatarDialog.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            event.preventDefault();
            avatarDialog.close();
        }
    });

    showProfile();
    document.addEventListener('regenera:auth-changed', showProfile);
}
