import '../css/learning.css';

const page = document.querySelector('[data-learning-page]');

if (page) initializeLearning();

function initializeLearning() {
    const apiBase = document.querySelector('meta[name="api-base-url"]')?.content || '/api';
    const catalog = page.querySelector('[data-learning-catalog]');
    const player = page.querySelector('[data-learning-player]');
    const access = page.querySelector('[data-learning-access]');
    const errorBox = page.querySelector('[data-learning-error]');
    const status = page.querySelector('[data-learning-catalog-status]');
    const formatNumber = value => new Intl.NumberFormat('es-MX', { maximumFractionDigits: 2 }).format(Number(value) || 0);
    let activeAttempt = null;
    let allCourses = [];

    const token = () => sessionStorage.getItem('regenera_access_token');

    async function request(path, options = {}) {
        const response = await fetch(`${apiBase}${path}`, {
            ...options,
            headers: {
                Accept: 'application/json',
                ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                ...(token() ? { Authorization: `Bearer ${token()}` } : {}),
                ...options.headers,
            },
        });
        const body = await response.json().catch(() => ({}));
        if (!response.ok) {
            const failure = new Error(body.message || 'No se pudo completar la solicitud.');
            failure.status = response.status;
            failure.data = body;
            throw failure;
        }
        return body.data;
    }

    function showError(message) {
        errorBox.textContent = message;
        errorBox.hidden = false;
    }

    function clearError() {
        errorBox.hidden = true;
        errorBox.textContent = '';
    }

    function appendText(parent, tag, className, text) {
        const element = document.createElement(tag);
        if (className) element.className = className;
        element.textContent = text;
        parent.append(element);
        return element;
    }

    function renderCatalog() {
        catalog.replaceChildren();
        allCourses.forEach(course => {
            const card = document.createElement('article');
            card.className = `learning-course${course.estado === 'aprobado' ? ' is-approved' : ''}`;
            appendText(card, 'span', 'learning-course-state', course.estado === 'aprobado'
                ? 'Aprobado'
                : course.estado === 'no_aprobado' ? 'Reintento disponible' : 'Pendiente');
            appendText(card, 'h3', '', course.titulo);
            appendText(card, 'p', 'learning-course-description', course.descripcion);
            if (course.guia) {
                const guide = document.createElement('details');
                guide.className = 'learning-course-guide';
                appendText(guide, 'summary', '', 'Guía de estudio');
                appendText(guide, 'p', '', course.guia);
                card.append(guide);
            }

            const meta = document.createElement('p');
            meta.className = 'learning-course-meta';
            meta.textContent = `${course.totalPreguntas} preguntas · mínimo ${formatNumber(course.minimoAprobacion)}% · ${course.intentos} ${course.intentos === 1 ? 'intento' : 'intentos'}`;
            card.append(meta);

            if (course.medalla) {
                appendText(card, 'p', 'learning-course-medal', `Medalla: ${course.medalla.nombre}`);
            }
            if (course.bloqueo) {
                appendText(card, 'p', 'learning-course-lock', course.bloqueo);
            }

            let activitySelect = null;
            if (course.slug === 'restauracion-entorno' && course.actividadesElegibles?.length) {
                const label = document.createElement('label');
                label.className = 'learning-activity-select';
                label.append(document.createTextNode('Actividad con asistencia confirmada'));
                activitySelect = document.createElement('select');
                activitySelect.setAttribute('aria-label', 'Actividad relacionada con el cuestionario');
                course.actividadesElegibles.forEach(activity => {
                    const option = new Option(`${activity.titulo}${activity.aprobado ? ' · Aprobado' : ''}`, activity.id);
                    option.disabled = activity.aprobado;
                    activitySelect.add(option);
                });
                const nextActivity = course.actividadesElegibles.find(activity => !activity.aprobado);
                if (nextActivity) activitySelect.value = nextActivity.id;
                label.append(activitySelect);
                card.append(label);
            }

            const start = document.createElement('button');
            start.type = 'button';
            start.className = 'learning-start-button';
            start.textContent = course.estado === 'no_aprobado' ? 'Repetir cuestionario' : 'Iniciar cuestionario';
            start.disabled = !course.disponible;
            start.addEventListener('click', () => void startCourse(course, activitySelect?.value));
            card.append(start);
            catalog.append(card);
        });
        status.textContent = allCourses.length ? `${allCourses.length} cuestionarios disponibles según tu perfil.` : 'No hay cuestionarios disponibles para tu rol.';
    }

    async function loadCatalog(autoLaunch = true) {
        status.textContent = 'Cargando cuestionarios…';
        try {
            allCourses = await request('/cuestionarios');
            renderCatalog();
            const target = new URLSearchParams(location.search).get('cuestionario');
            if (autoLaunch && target) {
                const course = allCourses.find(item => item.slug === target && item.disponible);
                if (course) await startCourse(course, new URLSearchParams(location.search).get('actividadId') || undefined);
            }
        } catch (error) {
            if (error.status === 401) {
                access.hidden = false;
                const redirect = encodeURIComponent(`${location.pathname}${location.search}`);
                access.innerHTML = `Inicia sesión para consultar tu progreso y responder cuestionarios. <a href="/login?redirect=${redirect}">Iniciar sesión</a>`;
                catalog.replaceChildren();
                status.textContent = '';
                return;
            }
            showError(error.message);
            status.textContent = 'No se pudieron cargar los cuestionarios.';
        }
    }

    async function loadProgress() {
        try {
            const progress = await request('/gamificacion/progreso');
            page.querySelector('[data-learning-points]').textContent = formatNumber(progress.puntos);
            page.querySelector('[data-learning-approved]').textContent = `${formatNumber(progress.cuestionariosAprobados)} / ${formatNumber(progress.cuestionariosAsignados)}`;
            page.querySelector('[data-learning-medals]').textContent = formatNumber(progress.medallas.filter(medal => medal.desbloqueada).length);
            page.querySelector('[data-learning-ecosystems]').textContent = formatNumber(progress.ecosistemasBCSAprendidos);
            page.querySelector('[data-learning-circular]').textContent = progress.conocimientoEconomiaCircular === null
                ? 'Pendiente'
                : `${formatNumber(progress.conocimientoEconomiaCircular)}%`;
        } catch (error) {
            if (error.status !== 401) console.error('No se pudo consultar el progreso educativo.', error);
        }
    }

    async function startCourse(course, activityId) {
        clearError();
        const body = course.slug === 'restauracion-entorno' ? { actividadId } : {};
        try {
            activeAttempt = await request(`/cuestionarios/${encodeURIComponent(course.slug)}/intentos`, {
                method: 'POST',
                body: JSON.stringify(body),
            });
            activeAttempt.respuestasPrevias ||= [];
            activeAttempt.respondidas = new Set(activeAttempt.respuestasPrevias.map(answer => answer.preguntaId));
            activeAttempt.currentQuestionId = activeAttempt.siguientePreguntaId
                || activeAttempt.preguntas.find(question => !activeAttempt.respondidas.has(question.id))?.id;
            renderQuestion();
        } catch (error) {
            showError(error.message);
        }
    }

    function renderQuestion() {
        if (!activeAttempt?.currentQuestionId) return;
        const course = activeAttempt.cuestionario;
        const question = activeAttempt.preguntas.find(item => item.id === activeAttempt.currentQuestionId);
        if (!question) return;

        player.hidden = false;
        page.querySelector('[data-learning-player-title]').textContent = course.titulo;
        page.querySelector('[data-learning-player-step]').textContent = `PREGUNTA ${activeAttempt.respondidas.size + 1} DE ${course.totalPreguntas}`;
        page.querySelector('[data-learning-question]').textContent = question.texto;
        page.querySelector('[data-learning-feedback]').hidden = true;
        page.querySelector('[data-learning-score]').hidden = true;
        page.querySelector('[data-learning-next]').hidden = true;
        page.querySelector('[data-learning-return]').hidden = true;
        page.querySelector('[data-learning-answer-form]').hidden = false;
        page.querySelector('[data-learning-answer-form]').reset();

        const options = page.querySelector('[data-learning-options]');
        options.replaceChildren();
        question.opciones.forEach((text, index) => {
            const label = document.createElement('label');
            label.className = 'learning-option';
            const input = document.createElement('input');
            input.type = 'radio';
            input.name = 'opcion';
            input.value = String(index);
            input.required = true;
            const option = document.createElement('span');
            option.textContent = text;
            label.append(input, option);
            options.append(label);
        });
    }

    page.querySelector('[data-learning-answer-form]').addEventListener('submit', async event => {
        event.preventDefault();
        const form = event.currentTarget;
        if (!activeAttempt) return;
        const selected = form.querySelector('input[name="opcion"]:checked');
        if (!selected) return;
        const submit = form.querySelector('[type="submit"]');
        submit.disabled = true;
        try {
            const result = await request(`/cuestionarios/intentos/${encodeURIComponent(activeAttempt.intentoId)}/respuestas`, {
                method: 'POST',
                body: JSON.stringify({ preguntaId: activeAttempt.currentQuestionId, opcion: Number(selected.value) }),
            });
            activeAttempt.respondidas.add(result.preguntaId);
            form.hidden = true;
            const feedback = page.querySelector('[data-learning-feedback]');
            feedback.hidden = false;
            page.querySelector('[data-learning-feedback-title]').textContent = result.retroalimentacion.correcta
                ? 'Respuesta correcta'
                : 'Respuesta incorrecta';
            page.querySelector('[data-learning-correct-answer]').textContent = `Respuesta correcta: ${result.retroalimentacion.respuestaCorrecta}`;
            page.querySelector('[data-learning-explanation]').textContent = result.retroalimentacion.explicacion;

            if (result.finalizado) {
                const score = page.querySelector('[data-learning-score]');
                score.hidden = false;
                score.textContent = `Resultado: ${formatNumber(result.puntuacion)}% · ${result.estado === 'aprobado' ? 'Aprobado' : 'No aprobado'}`;
                if (result.estado === 'aprobado') {
                    const returnPath = safeReturnPath(new URLSearchParams(location.search).get('return'));
                    const returnLink = page.querySelector('[data-learning-return]');
                    if (returnPath && activeAttempt.cuestionario.slug === 'seguridad-limpieza') {
                        returnLink.href = returnPath;
                        returnLink.textContent = 'Volver a la actividad';
                        returnLink.hidden = false;
                    }
                    await loadProgress();
                }
                await loadCatalog(false);
            } else {
                page.querySelector('[data-learning-next]').hidden = false;
            }
        } catch (error) {
            showError(error.message);
        } finally {
            submit.disabled = false;
        }
    });

    page.querySelector('[data-learning-next]').addEventListener('click', () => {
        const next = activeAttempt.preguntas.find(question => !activeAttempt.respondidas.has(question.id));
        if (!next) return;
        activeAttempt.currentQuestionId = next.id;
        renderQuestion();
    });

    function closePlayer() {
        player.hidden = true;
        activeAttempt = null;
        clearError();
    }

    page.querySelector('[data-learning-close]').addEventListener('click', closePlayer);
    page.querySelector('[data-learning-back]').addEventListener('click', closePlayer);

    function safeReturnPath(value) {
        return value && value.startsWith('/') && !value.startsWith('//') ? value : null;
    }

    void loadProgress();
    void loadCatalog();
}