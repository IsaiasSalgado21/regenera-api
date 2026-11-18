import '../../css/dashboard.css';
import '../../css/quiz-list.css';
import { createQuizPager } from '../quiz-list';

const root = document.querySelector('[data-quiz-admin]');

if (root) initializeQuizAdmin();

function initializeQuizAdmin() {
    const apiBase = document.querySelector('meta[name="api-base-url"]')?.content || '/api';
    const list = root.querySelector('[data-quiz-admin-list]');
    const form = root.querySelector('[data-quiz-admin-form]');
    const questions = root.querySelector('[data-quiz-questions]');
    const errorBox = root.querySelector('[data-quiz-admin-error]');
    const notice = root.querySelector('[data-quiz-admin-notice]');
    const identifiers = root.querySelector('[data-quiz-identifiers]');
    const identifierStatus = root.querySelector('[data-quiz-identifier-status]');
    const submitButton = root.querySelector('[data-quiz-submit]');
    const formError = root.querySelector('[data-quiz-form-error]');
    const guideCounter = root.querySelector('[data-guide-counter]');
    let activeQuizzes = [];
    let medals = [];
    let identifierEdited = false;
    const pager = createQuizPager(
        root.querySelector('[data-quiz-admin-search]'),
        root.querySelector('[data-quiz-admin-pagination]'),
        'Buscar cuestionarios por nombre',
        renderList,
    );

    const token = () => sessionStorage.getItem('regenera_access_token');
    const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
    }[character]));

    async function request(path, options = {}) {
        const response = await fetch(`${apiBase}${path}`, {
            ...options,
            headers: {
                Accept: 'application/json',
                ...(options.body ? { 'Content-Type': 'application/json' } : {}),
                ...(token() ? { Authorization: `Bearer ${token()}` } : {}),
            },
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const error = new Error(data.message || 'No se pudo completar la solicitud.');
            error.status = response.status;
            error.data = data;
            throw error;
        }
        return data.data;
    }

    function showNotice(message, isError = false) {
        notice.hidden = false;
        notice.textContent = message;
        notice.classList.toggle('is-error', isError);
    }

    function questionRow(question = {}) {
        const article = document.createElement('article');
        article.className = 'quiz-admin-question';
        article.dataset.quizQuestion = '';
        article.dataset.questionId = question.id || `pregunta-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`;
        article.innerHTML = `
            <div class="quiz-admin-question-heading">
                <h4>Pregunta</h4>
                <div class="quiz-question-actions">
                    <button class="button button-secondary" type="button" data-move-up aria-label="Mover pregunta hacia arriba">↑</button>
                    <button class="button button-secondary" type="button" data-move-down aria-label="Mover pregunta hacia abajo">↓</button>
                    <button class="button button-secondary" type="button" data-remove-question>Quitar</button>
                </div>
            </div>
            <label>Pregunta<textarea data-question-text required maxlength="500" rows="2"></textarea></label>
            <div class="dashboard-form-row">
                <label>Tipo de pregunta<select data-question-type>
                    <option value="unica">◉ Una respuesta</option>
                    <option value="multiple">☑ Varias respuestas</option>
                    <option value="verdadero_falso">◐ Verdadero o falso</option>
                    <option value="corta">✎ Respuesta corta</option>
                </select></label>
                <label>Importancia<select data-question-weight>
                    <option value="1">1 · Normal</option><option value="2">2</option><option value="3">3</option>
                    <option value="4">4</option><option value="5">5 · Mayor importancia</option>
                </select></label>
            </div>
            <p class="quiz-admin-help" data-options-help></p>
            <div class="quiz-answer-list" data-answer-list></div>
            <button class="button button-secondary" type="button" data-add-answer>Añadir opción</button>
            <label>Explicación después de responder<textarea data-question-explanation required rows="3" maxlength="1200"></textarea></label>`;
        article.querySelector('[data-question-text]').value = question.texto || '';
        article.querySelector('[data-question-explanation]').value = question.explicacion || '';
        const typeSelect = article.querySelector('[data-question-type]');
        const weightSelect = article.querySelector('[data-question-weight]');
        const answers = article.querySelector('[data-answer-list]');
        const addButton = article.querySelector('[data-add-answer]');
        const cache = {};
        typeSelect.value = question.tipo || 'unica';
        weightSelect.value = String(question.peso || 1);
        let currentType = typeSelect.value;

        function addAnswer(text = '', checked = false) {
            const row = document.createElement('div');
            row.className = 'quiz-answer-row';
            const choice = document.createElement('input');
            choice.type = currentType === 'multiple' ? 'checkbox' : 'radio';
            choice.name = `correct-${article.dataset.questionId}`;
            choice.checked = checked;
            choice.hidden = currentType === 'corta';
            choice.setAttribute('aria-label', 'Marcar como respuesta correcta');
            const input = document.createElement('input');
            input.type = 'text';
            input.value = text;
            input.required = true;
            input.maxLength = 300;
            input.readOnly = currentType === 'verdadero_falso';
            input.setAttribute('aria-label', currentType === 'corta' ? 'Respuesta aceptada' : 'Opción de respuesta');
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'button button-secondary';
            remove.textContent = 'Quitar';
            remove.setAttribute('aria-label', 'Quitar respuesta');
            remove.hidden = currentType === 'verdadero_falso';
            remove.addEventListener('click', () => row.remove());
            row.append(choice, input, remove);
            answers.append(row);
        }

        function values() {
            return [...answers.children].map(row => ({
                text: row.querySelector('input[type="text"]').value,
                checked: row.querySelector('input[type="radio"], input[type="checkbox"]').checked,
            }));
        }
        function renderAnswers(saved) {
            answers.replaceChildren();
            article.querySelector('[data-options-help]').textContent = currentType === 'corta'
                ? 'Agrega las respuestas que se aceptarán. Se ignorarán mayúsculas, acentos y espacios adicionales.'
                : 'Escribe las opciones y marca las respuestas correctas.';
            addButton.hidden = currentType === 'verdadero_falso';
            addButton.textContent = currentType === 'corta' ? 'Añadir respuesta aceptada' : 'Añadir opción';
            const initial = saved || (currentType === 'verdadero_falso'
                ? [{ text: 'Verdadero', checked: false }, { text: 'Falso', checked: false }]
                : currentType === 'corta' ? [{ text: '', checked: false }]
                    : [{ text: '', checked: false }, { text: '', checked: false }]);
            initial.forEach(item => addAnswer(item.text, item.checked));
        }
        const initialAnswers = currentType === 'corta'
            ? (question.respuestasAceptadas || []).map(text => ({ text, checked: false }))
            : (question.opciones || []).map((text, index) => ({
                text,
                checked: currentType === 'multiple'
                    ? (question.respuestasCorrectas || []).includes(index)
                    : question.respuestaCorrecta === index,
            }));
        renderAnswers(initialAnswers.length ? initialAnswers : null);
        typeSelect.addEventListener('change', () => {
            cache[currentType] = values();
            currentType = typeSelect.value;
            renderAnswers(cache[currentType]);
        });
        addButton.addEventListener('click', () => {
            if (answers.children.length < 20) addAnswer();
        });
        article.querySelector('[data-remove-question]').addEventListener('click', () => article.remove());
        article.querySelector('[data-move-up]').addEventListener('click', () => {
            if (article.previousElementSibling) questions.insertBefore(article, article.previousElementSibling);
        });
        article.querySelector('[data-move-down]').addEventListener('click', () => {
            if (article.nextElementSibling) questions.insertBefore(article.nextElementSibling, article);
        });
        return article;
    }

    function renderList(result) {
        list.replaceChildren();
        result.items.forEach(quiz => {
            const card = document.createElement('article');
            card.className = 'dashboard-card';
            const meta = document.createElement('div');
            meta.className = 'dashboard-card-meta';
            meta.innerHTML = `<span class="status-pill status-${quiz.activo ? 'publicado' : 'archivado'}">${quiz.activo ? 'Activo' : 'Desactivado'}</span><span>Versión ${Number(quiz.version) || 1}</span><span>${(quiz.audiencia || []).map(escapeHtml).join(', ')}</span>`;
            const body = document.createElement('div');
            body.className = 'dashboard-card-body';
            const title = document.createElement('h3');
            title.textContent = quiz.titulo;
            const description = document.createElement('p');
            description.textContent = quiz.descripcion;
            body.append(title, description);
            const actions = document.createElement('div');
            actions.className = 'dashboard-card-actions';
            const edit = document.createElement('button');
            edit.className = 'button button-secondary';
            edit.type = 'button';
            edit.textContent = 'Editar';
            edit.addEventListener('click', () => openEditor(quiz));
            actions.append(edit);
            if (quiz.activo) {
                const deactivate = document.createElement('button');
                deactivate.className = 'button button-danger';
                deactivate.type = 'button';
                deactivate.textContent = 'Desactivar';
                deactivate.addEventListener('click', () => void deactivateQuiz(quiz));
                actions.append(deactivate);
            }
            card.append(meta, body, actions);
            list.append(card);
        });
        const empty = root.querySelector('[data-quiz-admin-empty]');
        empty.hidden = result.total > 0;
        empty.textContent = activeQuizzes.length ? 'No se encontraron cuestionarios con ese nombre.' : 'No hay cuestionarios registrados.';
    }

    async function loadQuizzes() {
        root.querySelector('[data-quiz-admin-loading]').hidden = false;
        errorBox.hidden = true;
        try {
            [activeQuizzes, medals] = await Promise.all([
                request('/admin/cuestionarios'),
                request('/admin/medallas'),
            ]);
            root.querySelector('[data-quiz-create]').hidden = false;
            identifiers.replaceChildren(...[...new Set(activeQuizzes.map(quiz => quiz.slug))]
                .sort((a, b) => a.localeCompare(b))
                .map(slug => {
                    const option = document.createElement('option');
                    option.value = slug;
                    return option;
                }));
            pager.setItems(activeQuizzes);
            const params = new URLSearchParams(location.search);
            const requestedSlug = params.get('editar');
            if (requestedSlug) {
                const quiz = activeQuizzes.find(item => item.slug === requestedSlug);
                if (quiz) {
                    openEditor(quiz);
                    params.delete('editar');
                    const query = params.toString();
                    history.replaceState(null, '', `${location.pathname}${query ? `?${query}` : ''}`);
                }
            }
        } catch (error) {
            root.querySelector('[data-quiz-create]').hidden = true;
            errorBox.hidden = false;
            errorBox.textContent = error.message;
            if (error.status === 401 || error.status === 403) {
                root.querySelector('[data-quiz-admin-access]').hidden = false;
                root.querySelector('[data-quiz-admin-access]').textContent = 'Esta sección es exclusiva para administradores.';
            }
        } finally {
            root.querySelector('[data-quiz-admin-loading]').hidden = true;
        }
    }

    function updateIdentifierState() {
        const slug = form.elements.slug.value.trim();
        const existing = activeQuizzes.find(quiz => quiz.slug === slug);
        const isVersion = Boolean(form.elements.slugOriginal.value || existing);
        identifierStatus.hidden = !isVersion;
        identifierStatus.textContent = isVersion
            ? 'Este identificador ya existe. Al guardar se creará una nueva versión y la versión actual dejará de estar activa.'
            : '';
        submitButton.textContent = isVersion ? 'Guardar nueva versión' : 'Crear cuestionario';
        if (isVersion) {
            form.elements.slug.removeAttribute('pattern');
        } else {
            form.elements.slug.pattern = '[a-z0-9]+(?:-[a-z0-9]+)*';
        }
        root.querySelector('[data-quiz-medal-attendance]').hidden = slug !== 'restauracion-entorno';
        root.querySelector('[data-quiz-medal-verification]').hidden = !form.elements.requiereOrganizadorVerificado.checked;
    }

    function openEditor(quiz = null) {
        form.reset();
        identifierEdited = Boolean(quiz);
        formError.hidden = true;
        questions.replaceChildren();
        form.elements.slugOriginal.value = quiz?.slug || '';
        form.elements.slug.value = quiz?.slug || '';
        form.elements.slug.readOnly = Boolean(quiz);
        form.elements.titulo.value = quiz?.titulo || '';
        form.elements.descripcion.value = quiz?.descripcion || '';
        form.elements.guia.value = quiz?.guia || '';
        updateGuideCounter();
        form.elements.minimoAprobacion.value = quiz?.minimoAprobacion ?? 75;
        form.elements.puntosAprobacion.value = quiz?.puntosAprobacion ?? 20;
        form.elements.requiereOrganizadorVerificado.checked = Boolean(quiz?.requiereOrganizadorVerificado);
        renderMedalOptions(quiz?.medalla?.slug || '');
        updateMedalDescription();
        form.querySelectorAll('input[name="audiencia"]').forEach(input => {
            input.checked = (quiz?.audiencia || []).includes(input.value);
        });
        (quiz?.preguntas || []).forEach(question => questions.append(questionRow(question)));
        if (!quiz) questions.append(questionRow());
        updateIdentifierState();
        root.querySelector('[data-quiz-regenerate-slug]').hidden = Boolean(quiz);
        root.querySelector('#quiz-identifier-help').textContent = quiz
            ? 'El identificador permanece fijo para conservar las versiones y los intentos anteriores.'
            : 'Se genera desde el título. También puedes escribir uno nuevo o seleccionar uno existente para crear una versión.';
        root.querySelector('[data-quiz-form-heading]').textContent = quiz
            ? `Nueva versión de ${quiz.titulo}`
            : 'Nuevo cuestionario';
        form.hidden = false;
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function updateGuideCounter() {
        guideCounter.textContent = `${form.elements.guia.value.length.toLocaleString('es-MX')} / 2.000 caracteres`;
    }

    function renderMedalOptions(selectedMedal) {
        form.elements.medallaSlug.replaceChildren(new Option('Selecciona una medalla', ''));
        medals.filter(medal => medal.activo || medal.slug === selectedMedal).forEach(medal => {
            form.elements.medallaSlug.add(new Option(`${medal.nombre}${medal.activo ? '' : ' · Desactivada'}`, medal.slug));
        });
        form.elements.medallaSlug.value = selectedMedal;
    }

    async function deactivateQuiz(quiz) {
        if (!window.confirm(`¿Desactivar “${quiz.titulo}”? Los intentos históricos se conservarán.`)) return;
        try {
            const response = await request(`/admin/cuestionarios/${encodeURIComponent(quiz.slug)}`, { method: 'DELETE' });
            showNotice(response.message);
            await loadQuizzes();
        } catch (error) {
            showNotice(error.message, true);
        }
    }

    root.querySelector('[data-quiz-create]').addEventListener('click', () => openEditor());
    root.querySelector('[data-quiz-add-question]').addEventListener('click', () => questions.append(questionRow()));
    root.querySelectorAll('[data-quiz-cancel]').forEach(button => button.addEventListener('click', () => { form.hidden = true; }));
    const normalizeIdentifier = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80).replace(/-+$/g, '');
    function availableIdentifier(value) {
        const base = normalizeIdentifier(value);
        if (!base) return '';
        const existing = new Set(activeQuizzes.map(quiz => quiz.slug));
        let candidate = base;
        let suffix = 2;
        while (existing.has(candidate)) {
            const ending = `-${suffix++}`;
            candidate = `${base.slice(0, 80 - ending.length).replace(/-+$/g, '')}${ending}`;
        }
        return candidate;
    }
    form.elements.titulo.addEventListener('input', () => {
        if (!identifierEdited) {
            form.elements.slug.value = availableIdentifier(form.elements.titulo.value);
            updateIdentifierState();
        }
    });
    form.elements.slug.addEventListener('input', () => {
        identifierEdited = form.elements.slug.value.trim() !== '';
        if (!identifierEdited) form.elements.slug.value = availableIdentifier(form.elements.titulo.value);
        updateIdentifierState();
    });
    root.querySelector('[data-quiz-regenerate-slug]').addEventListener('click', () => {
        identifierEdited = false;
        form.elements.slug.value = availableIdentifier(form.elements.titulo.value);
        updateIdentifierState();
    });
    form.elements.slug.addEventListener('blur', () => {
        if (!activeQuizzes.some(quiz => quiz.slug === form.elements.slug.value.trim())) {
            form.elements.slug.value = normalizeIdentifier(form.elements.slug.value);
        }
        updateIdentifierState();
    });
    form.elements.requiereOrganizadorVerificado.addEventListener('change', updateIdentifierState);
    form.elements.guia.addEventListener('input', updateGuideCounter);

    function updateMedalDescription() {
        const medal = medals.find(item => item.slug === form.elements.medallaSlug.value);
        root.querySelector('[data-quiz-medal-description]').textContent = medal
            ? `${medal.descripcion}${medal.activo ? '' : ' Esta medalla está desactivada y no se otorgará en nuevos intentos.'}`
            : 'Crea una medalla si todavía no aparece en el catálogo.';
    }
    form.elements.medallaSlug.addEventListener('change', updateMedalDescription);
    window.addEventListener('focus', async () => {
        if (form.hidden) return;
        try {
            medals = await request('/admin/medallas');
            renderMedalOptions(form.elements.medallaSlug.value);
            updateMedalDescription();
        } catch (error) {
            showNotice(`No se pudo actualizar el catálogo de medallas: ${error.message}`, true);
        }
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        errorBox.hidden = true;
        formError.hidden = true;
        const quizQuestions = [...questions.querySelectorAll('[data-quiz-question]')].map(row => {
            const type = row.querySelector('[data-question-type]').value;
            const answerRows = [...row.querySelector('[data-answer-list]').children];
            const options = answerRows.map(answer => answer.querySelector('input[type="text"]').value.trim());
            const correct = answerRows.flatMap((answer, index) => answer.querySelector('input[type="radio"], input[type="checkbox"]').checked ? [index] : []);
            const result = {
                id: row.dataset.questionId,
                texto: row.querySelector('[data-question-text]').value.trim(),
                tipo: type,
                peso: Number(row.querySelector('[data-question-weight]').value),
                explicacion: row.querySelector('[data-question-explanation]').value.trim(),
            };
            if (type === 'corta') result.respuestasAceptadas = options;
            else {
                result.opciones = options;
                if (type === 'multiple') result.respuestasCorrectas = correct;
                else result.respuestaCorrecta = correct[0] ?? null;
            }
            return result;
        });
        if (!quizQuestions.length || quizQuestions.some(question => question.tipo === 'corta'
            ? !question.respuestasAceptadas.length || question.respuestasAceptadas.some(answer => !answer)
            : question.opciones.length < 2 || question.opciones.some(option => !option)
                || (question.tipo === 'multiple' ? !question.respuestasCorrectas.length : question.respuestaCorrecta === null))) {
            formError.textContent = 'Completa cada pregunta y sus respuestas, y marca al menos una respuesta correcta.';
            formError.hidden = false;
            return;
        }
        if (new Set(quizQuestions.map(question => question.id)).size !== quizQuestions.length) {
            formError.textContent = 'Cada pregunta debe tener un identificador distinto dentro del cuestionario.';
            formError.hidden = false;
            return;
        }

        const originalSlug = form.elements.slugOriginal.value;
        const selectedSlug = form.elements.slug.value.trim();
        const existing = activeQuizzes.find(quiz => quiz.slug === selectedSlug);
        if (!originalSlug && existing && !window.confirm(
            `“${selectedSlug}” ya existe. Se guardará una nueva versión y la versión actual dejará de estar activa. ¿Continuar?`,
        )) return;
        const versionSlug = originalSlug || existing?.slug;
        const payload = {
            slug: selectedSlug,
            titulo: form.elements.titulo.value.trim(),
            descripcion: form.elements.descripcion.value.trim(),
            guia: form.elements.guia.value.trim(),
            audiencia: [...form.querySelectorAll('input[name="audiencia"]:checked')].map(input => input.value),
            requiereOrganizadorVerificado: form.elements.requiereOrganizadorVerificado.checked,
            minimoAprobacion: Number(form.elements.minimoAprobacion.value),
            puntosAprobacion: Number(form.elements.puntosAprobacion.value),
            preguntas: quizQuestions,
            medallaSlug: form.elements.medallaSlug.value,
        };
        submitButton.disabled = true;
        try {
            const path = versionSlug
                ? `/admin/cuestionarios/${encodeURIComponent(versionSlug)}`
                : '/admin/cuestionarios';
            const response = await request(path, {
                method: versionSlug ? 'PUT' : 'POST',
                body: JSON.stringify(payload),
            });
            form.hidden = true;
            showNotice(response.message || 'Cuestionario guardado.');
            await loadQuizzes();
        } catch (error) {
            formError.textContent = Object.values(error.data?.errors || {}).flat()[0] || error.message;
            formError.hidden = false;
        } finally {
            submitButton.disabled = false;
        }
    });

    loadQuizzes();
}