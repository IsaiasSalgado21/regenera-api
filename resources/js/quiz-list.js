export function quizPage(items, query, page, pageSize = 6) {
    const normalize = value => String(value ?? '').normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase().trim();
    const needle = normalize(query);
    const filtered = items.filter(item => normalize(item.titulo).includes(needle));
    const pages = Math.max(1, Math.ceil(filtered.length / pageSize));
    const current = Math.min(Math.max(1, page), pages);
    return {
        items: filtered.slice((current - 1) * pageSize, current * pageSize),
        total: filtered.length,
        page: current,
        pages,
    };
}

export function createQuizPager(searchMount, paginationMount, label, render) {
    const searchLabel = document.createElement('label');
    searchLabel.className = 'quiz-list-search';
    const caption = document.createElement('span');
    caption.textContent = label;
    const input = document.createElement('input');
    input.type = 'search';
    input.placeholder = 'Escribe el nombre del cuestionario';
    input.autocomplete = 'off';
    searchLabel.append(caption, input);
    searchMount.append(searchLabel);

    const nav = document.createElement('nav');
    nav.className = 'quiz-list-pagination';
    nav.hidden = true;
    nav.setAttribute('aria-label', `Páginas: ${label.toLocaleLowerCase()}`);
    const previous = document.createElement('button');
    previous.type = 'button';
    previous.textContent = 'Anterior';
    const status = document.createElement('span');
    status.setAttribute('aria-live', 'polite');
    const next = document.createElement('button');
    next.type = 'button';
    next.textContent = 'Siguiente';
    nav.append(previous, status, next);
    paginationMount.append(nav);

    let items = [];
    let page = 1;
    function update() {
        const result = quizPage(items, input.value, page);
        page = result.page;
        nav.hidden = result.pages <= 1;
        previous.disabled = page === 1;
        next.disabled = page === result.pages;
        status.textContent = `Página ${page} de ${result.pages} · ${result.total} resultados`;
        render(result);
    }
    input.addEventListener('input', () => { page = 1; update(); });
    previous.addEventListener('click', () => { page--; update(); });
    next.addEventListener('click', () => { page++; update(); });
    return {
        setItems(value) {
            items = value;
            update();
        },
    };
}
