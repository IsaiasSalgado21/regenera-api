import '../../css/dashboard.css';

const root = document.querySelector('[data-quiz-admin]');

if (root) initializeQuizAdmin();

function initializeQuizAdmin() {
    const apiBase = document.querySelector('meta[name="api-base-url"]')?.content || '/api';
    const list = root.querySelector('[data-quiz-admin-list]');
    const form = root.querySelector('[data-quiz-admin-form]');
    const questions = root.querySelector('[data-quiz-questions]');
    const errorBox = root.querySelector('[data-quiz-admin-error]');
    const notice = root.querySelector('[data-quiz-admin-notice]');
    let activeQuizzes = [];

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
        const heading = document.createElement('div');
        heading.className = 'quiz-admin-question-heading';
        const title = document.createElement('h4');
        title.textContent = question.id ? `Pregunta · ${question.id}` : 'Pregunta nueva';
        const remove = document.createElement('button');
        remove.className = 'button button-secondary';
        remove.type = 'button';
        remove.dataset.quizRemoveQuestion = '';
        remove.textContent = 'Quitar';
        heading.append(title, remove);

        const idLabel = document.createElement('label');
        idLabel.textContent = 'Identificador';
        const idInput = document.createElement('input');
        idInput.dataset.questionId = '';
        idInput.required = true;
        idInput.maxLength = 80;
        idInput.value = question.id || '';
        idLabel.append(idInput);

        const textLabel = document.createElement('label');
        textLabel.textContent = 'Pregunta';
        const textInput = document.createElement('textarea');
        textInput.dataset.questionText = '';
        textInput.required = true;
        textInput.maxLength = 500;
        textInput.rows = 2;
        textInput.value = question.texto || '';
        textLabel.append(textInput);

        const optionsLabel = document.createElement('label');
        optionsLabel.textContent = 'Opciones (una por línea)';
        const optionsInput = document.createElement('textarea');
        optionsInput.dataset.questionOptions = '';
        optionsInput.required = true;
        optionsInput.rows = 4;
        optionsInput.maxLength = 6000;
        optionsInput.value = (question.opciones || []).join('\n');
        optionsLabel.append(optionsInput);

        const correctLabel = document.createElement('label');
        correctLabel.textContent = 'Índice de respuesta correcta (empieza en 0)';
        const correctInput = document.createElement('input');
        correctInput.dataset.questionCorrect = '';
        correctInput.type = 'number';
        correctInput.required = true;
        correctInput.min = 0;
        correctInput.step = 1;
        correctInput.value = question.respuestaCorrecta ?? 0;
        correctLabel.append(correctInput);

        const explanationLabel = document.createElement('label');
        explanationLabel.textContent = 'Explicación educativa';
        const explanationInput = document.createElement('textarea');
        explanationInput.dataset.questionExplanation = '';
        explanationInput.required = true;
        explanationInput.rows = 3;
        explanationInput.maxLength = 1200;
        explanationInput.value = question.explicacion || '';
        explanationLabel.append(explanationInput);

        article.append(heading, idLabel, textLabel, optionsLabel, correctLabel, explanationLabel);
        remove.addEventListener('click', () => article.remove());
        return article;
    }

    function renderList() {
        list.replaceChildren();
        activeQuizzes.forEach(quiz => {
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
        root.querySelector('[data-quiz-admin-empty]').hidden = activeQuizzes.length > 0;
    }

    async function loadQuizzes() {
        root.querySelector('[data-quiz-admin-loading]').hidden = false;
        errorBox.hidden = true;
        try {
            activeQuizzes = await request('/admin/cuestionarios');
            renderList();
        } catch (error) {
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

    function openEditor(quiz = null) {
        form.reset();
        questions.replaceChildren();
        form.elements.slugOriginal.value = quiz?.slug || '';
        form.elements.slug.value = quiz?.slug || '';
        form.elements.slug.readOnly = Boolean(quiz);
        form.elements.titulo.value = quiz?.titulo || '';
        form.elements.descripcion.value = quiz?.descripcion || '';
        form.elements.guia.value = quiz?.guia || '';
        form.elements.minimoAprobacion.value = quiz?.minimoAprobacion ?? 75;
        form.elements.puntosAprobacion.value = quiz?.puntosAprobacion ?? 20;
        form.elements.requiereOrganizadorVerificado.checked = Boolean(quiz?.requiereOrganizadorVerificado);
        form.elements.medallaSlug.value = quiz?.medalla?.slug || '';
        form.elements.medallaNombre.value = quiz?.medalla?.nombre || '';
        form.elements.medallaDescripcion.value = quiz?.medalla?.descripcion || '';
        form.elements.medallaIcono.value = quiz?.medalla?.icono || 'award';
        form.querySelectorAll('input[name="audiencia"]').forEach(input => {
            input.checked = (quiz?.audiencia || []).includes(input.value);
        });
        (quiz?.preguntas || []).forEach(question => questions.append(questionRow(question)));
        if (!quiz) questions.append(questionRow());
        root.querySelector('[data-quiz-form-heading]').textContent = quiz
            ? `Nueva versión de ${quiz.titulo}`
            : 'Nuevo cuestionario';
        form.hidden = false;
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
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

    form.addEventListener('submit', async event => {
        event.preventDefault();
        errorBox.hidden = true;
        const quizQuestions = [...questions.querySelectorAll('[data-question-id]')].map(idInput => {
            const row = idInput.closest('[data-quiz-question]');
            const options = row.querySelector('[data-question-options]').value
                .split('\n')
                .map(option => option.trim())
                .filter(Boolean);
            return {
                id: idInput.value.trim(),
                texto: row.querySelector('[data-question-text]').value.trim(),
                opciones: options,
                respuestaCorrecta: Number(row.querySelector('[data-question-correct]').value),
                explicacion: row.querySelector('[data-question-explanation]').value.trim(),
            };
        });
        if (!quizQuestions.length || quizQuestions.some(question => question.respuestaCorrecta >= question.opciones.length)) {
            showNotice('Cada pregunta necesita dos opciones y un índice correcto válido.', true);
            return;
        }

        const originalSlug = form.elements.slugOriginal.value;
        const payload = {
            slug: form.elements.slug.value.trim(),
            titulo: form.elements.titulo.value.trim(),
            descripcion: form.elements.descripcion.value.trim(),
            guia: form.elements.guia.value.trim(),
            audiencia: [...form.querySelectorAll('input[name="audiencia"]:checked')].map(input => input.value),
            requiereOrganizadorVerificado: form.elements.requiereOrganizadorVerificado.checked,
            minimoAprobacion: Number(form.elements.minimoAprobacion.value),
            puntosAprobacion: Number(form.elements.puntosAprobacion.value),
            preguntas: quizQuestions,
            medalla: {
                slug: form.elements.medallaSlug.value.trim(),
                nombre: form.elements.medallaNombre.value.trim(),
                descripcion: form.elements.medallaDescripcion.value.trim(),
                icono: form.elements.medallaIcono.value.trim(),
            },
        };
        const button = form.querySelector('[type="submit"]');
        button.disabled = true;
        try {
            const path = originalSlug
                ? `/admin/cuestionarios/${encodeURIComponent(originalSlug)}`
                : '/admin/cuestionarios';
            const response = await request(path, {
                method: originalSlug ? 'PUT' : 'POST',
                body: JSON.stringify(payload),
            });
            form.hidden = true;
            showNotice(response.message || 'Cuestionario guardado.');
            await loadQuizzes();
        } catch (error) {
            showNotice(error.message, true);
        } finally {
            button.disabled = false;
        }
    });

    loadQuizzes();
}