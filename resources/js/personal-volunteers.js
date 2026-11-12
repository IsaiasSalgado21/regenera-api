const sections = document.querySelector('[data-personal-volunteer-sections]');
const listOrder = new WeakMap();
const pageNumbers = new WeakMap();
const pageSize = 10;
const searchInput = document.querySelector('[data-volunteer-search]');
const categoryFilter = document.querySelector('[data-volunteer-category]');
const placeFilter = document.querySelector('[data-volunteer-place]');
const resetFilters = document.querySelector('[data-volunteer-reset]');
const filterStatus = document.querySelector('[data-volunteer-filter-status]');

if (sections) {
    initializeVolunteerFilters();
    sortPublicVolunteers();
    updateTimers();
    window.setInterval(updateTimers, 1000);
    const token = sessionStorage.getItem('regenera_access_token');
    if (token) loadPersonalVolunteers(token);
}

async function loadPersonalVolunteers(token) {
    try {
        const response = await fetch('/api/voluntariados', {
            headers: { Accept: 'application/json', Authorization: `Bearer ${token}` },
        });
        if (!response.ok) return;

        const data = await response.json();
        const isAdmin = data.esAdmin === true;
        const owned = data.misVoluntariados || [];
        const enrolled = data.misInscripciones || [];
        sections.classList.add('has-personal-columns');
        const publishedIds = new Set(owned
            .filter(item => item.estado === 'publicado')
            .map(item => String(item.id || item._id)));

        document.querySelectorAll('[data-public-volunteer]').forEach(card => {
            if (publishedIds.has(card.dataset.publicVolunteer)) card.remove();
        });

        if (isAdmin) {
            const allEvents = data.todosVoluntariados || [];
            const ownedColumn = document.querySelector('[data-owned-volunteers]');
            const heading = ownedColumn?.querySelector('#ownedVolunteersHeading');
            const eyebrow = ownedColumn?.querySelector('.volunteer-column-heading > div > span');
            const publicColumn = document.querySelector('[data-public-column]');
            const enrolledColumn = document.querySelector('[data-enrolled-volunteers]');
            sections.classList.add('is-admin-board');
            if (heading) heading.textContent = 'Todos los voluntariados';
            if (eyebrow) eyebrow.textContent = 'ADMINISTRACIÓN · TODOS LOS ESTADOS';
            if (publicColumn) publicColumn.hidden = true;
            if (enrolledColumn) enrolledColumn.hidden = true;
            renderGroup('[data-owned-volunteers]', '[data-owned-volunteer-list]', allEvents, 'admin');
        } else {
            renderGroup('[data-owned-volunteers]', '[data-owned-volunteer-list]', owned, 'owner');
            renderGroup('[data-enrolled-volunteers]', '[data-enrolled-volunteer-list]', enrolled, 'enrolled');
        }
        updateTimers();
        renderVolunteerBoard();
    } catch (error) {
        console.error('No se pudieron cargar los voluntariados personales.', error);
    }
}

function renderGroup(sectionSelector, listSelector, items, type) {
    const section = document.querySelector(sectionSelector);
    const list = document.querySelector(listSelector);
    if (!section || !list) return;

    section.hidden = false;
    list.replaceChildren();
    [...items].sort(compareVolunteers).forEach(item => {
        const card = document.createElement('article');
        card.className = 'volunteer-card volunteer-owned-card';
        card.dataset.volunteerItem = '';
        card.dataset.startAt = item.inicioIso || '';
        card.dataset.endAt = item.finIso || '';
        card.dataset.sortId = String(item.id || item._id || '');
        card.dataset.category = item.categoria || '';
        card.dataset.placeId = String(item.lugarId || '');
        card.dataset.search = [item.titulo, item.descripcion, item.organizadorNombre, item.lugar?.nombre, item.categoria].join(' ');
        const content = document.createElement('div');
        content.className = 'volunteer-card-content';

        if (type === 'owner' || type === 'admin') {
            const status = document.createElement('span');
            status.className = `volunteer-owned-status is-${item.estado}`;
            status.textContent = ({
                pendiente: 'Pendiente de aprobación',
                rechazado: 'Requiere cambios',
                publicado: 'Publicado',
                cancelado: 'Cancelado',
                finalizado: 'Finalizado',
            })[item.estado] || 'Sin estado';
            content.append(status);
        }

        const title = document.createElement('h3');
        title.textContent = item.titulo || 'Voluntariado sin título';
        const organizer = document.createElement('p');
        organizer.className = 'volunteer-author';
        organizer.textContent = `Organiza: ${item.organizadorNombre || 'No disponible'}`;
        const description = document.createElement('p');
        description.textContent = item.descripcion || 'Sin descripción disponible.';
        const details = document.createElement('div');
        details.className = 'volunteer-info';
        const date = document.createElement('span');
        date.textContent = item.fechaInicio
            ? `Inicio: ${new Date(item.fechaInicio).toLocaleString('es-MX')}`
            : 'Inicio pendiente';
        const capacity = document.createElement('span');
        capacity.textContent = type === 'enrolled'
            ? `Inscripción: ${item.estadoInscripcion || 'activa'}`
            : `Cupo: ${Number(item.cupo) || 0}`;
        details.append(date, capacity);

        const timer = document.createElement('p');
        timer.className = 'personal-volunteer-timer';
        timer.dataset.volunteerTimer = '';
        timer.dataset.startAt = item.inicioIso || '';
        timer.dataset.endAt = item.finIso || '';

        content.append(title, organizer, description, details, timer);
        if (['publicado', 'cancelado', 'finalizado'].includes(item.estado)) {
            const link = document.createElement('a');
            link.className = 'volunteer-link';
            link.href = `/voluntariados/${encodeURIComponent(item.id || item._id)}`;
            link.textContent = 'Ver voluntariado';
            content.append(link);
        }
        if (['publicado', 'cancelado', 'finalizado'].includes(item.estado)) {
            const detailLink = document.createElement('a');
            detailLink.className = 'volunteer-owned-card-link';
            detailLink.href = `/voluntariados/${encodeURIComponent(item.id || item._id)}`;
            detailLink.setAttribute('aria-label', `Ver voluntariado: ${item.titulo || 'Voluntariado'}`);
            detailLink.append(content);
            card.append(detailLink);
        } else {
            card.append(content);
        }
        const mapLink = document.createElement('a');
        mapLink.className = 'volunteer-map-edge-link';
        mapLink.href = `/mapa?voluntariado=${encodeURIComponent(item.id || item._id)}`;
        mapLink.setAttribute('aria-label', `Ver ubicación en el mapa: ${item.titulo || 'Voluntariado'}`);
        mapLink.title = 'Ver ubicación en el mapa';
        const symbolsBase = document.body.dataset.symbolsBase;
        if (symbolsBase) {
            const mapIcon = document.createElement('img');
            mapIcon.className = 'ui-symbol';
            mapIcon.src = `${symbolsBase.replace(/\/+$/, '')}/ubicacion.png`;
            mapIcon.alt = '';
            mapIcon.setAttribute('aria-hidden', 'true');
            mapLink.append(mapIcon);
        } else {
            mapLink.textContent = '⌖';
        }
        card.append(mapLink);
        list.append(card);
    });
}

function parseEventDate(value) {
    if (!value) return null;
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function volunteerPriority(start, end, now = Date.now()) {
    if (!start || !end) return 4;
    if (now >= start && now <= end) return 0;
    if (now < start) return start - now <= 48 * 60 * 60 * 1000 ? 1 : 2;
    return 3;
}

function compareVolunteers(first, second) {
    const now = Date.now();
    const firstStart = parseEventDate(first.inicioIso || first.dataset?.startAt)?.getTime();
    const firstEnd = parseEventDate(first.finIso || first.dataset?.endAt)?.getTime();
    const secondStart = parseEventDate(second.inicioIso || second.dataset?.startAt)?.getTime();
    const secondEnd = parseEventDate(second.finIso || second.dataset?.endAt)?.getTime();
    const firstPriority = volunteerPriority(firstStart, firstEnd, now);
    const secondPriority = volunteerPriority(secondStart, secondEnd, now);
    if (firstPriority !== secondPriority) return firstPriority - secondPriority;
    if (firstPriority === 0 || firstPriority === 3) return (secondEnd || 0) - (firstEnd || 0);
    return (firstStart || Number.MAX_SAFE_INTEGER) - (secondStart || Number.MAX_SAFE_INTEGER);
}

function sortPublicVolunteers() {
    const list = document.querySelector('[data-public-volunteer-list]');
    if (!list) return;
    sortVolunteerCards(list, [...list.querySelectorAll('[data-public-volunteer]')]);
}

function sortPersonalVolunteers() {
    document.querySelectorAll('[data-owned-volunteer-list], [data-enrolled-volunteer-list]').forEach(list => {
        sortVolunteerCards(list, [...list.querySelectorAll('.volunteer-card')]);
    });
}

function sortVolunteerCards(list, cards) {
    const ordered = cards.sort(compareVolunteers);
    const signature = ordered.map(card => card.dataset.publicVolunteer || card.dataset.sortId).join('|');
    if (listOrder.get(list) === signature) return;
    listOrder.set(list, signature);
    ordered.forEach(card => list.append(card));
}

function countdownLabel(milliseconds) {
    const seconds = Math.max(0, Math.floor(milliseconds / 1000));
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const remainingSeconds = seconds % 60;
    return `${days}d ${hours}h ${minutes}m ${remainingSeconds}s`;
}

function updateTimers() {
    const now = new Date();
    document.querySelectorAll('[data-volunteer-timer]').forEach(timer => {
        const start = parseEventDate(timer.dataset.startAt);
        const end = parseEventDate(timer.dataset.endAt);
        if (!start || !end) {
            timer.textContent = 'Horario pendiente';
            timer.classList.remove('is-ongoing', 'is-upcoming', 'is-past');
        } else if (now < start) {
            timer.textContent = `Inicia en ${countdownLabel(start - now)}`;
            timer.classList.remove('is-ongoing', 'is-past');
            timer.classList.add('is-upcoming');
        } else if (now < end) {
            timer.textContent = `En curso · termina en ${countdownLabel(end - now)}`;
            timer.classList.remove('is-upcoming', 'is-past');
            timer.classList.add('is-ongoing');
        } else {
            timer.textContent = 'Ya pasó';
            timer.classList.remove('is-ongoing', 'is-upcoming');
            timer.classList.add('is-past');
        }
        const card = timer.closest('.volunteer-card');
        card?.classList.toggle('is-past-volunteer', Boolean(end && now >= end));
    });
    sortPublicVolunteers();
    sortPersonalVolunteers();
}

function initializeVolunteerFilters() {
    [searchInput, categoryFilter, placeFilter].filter(Boolean).forEach(input => {
        input.addEventListener(input === searchInput ? 'input' : 'change', applyVolunteerFilters);
    });
    resetFilters?.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        if (categoryFilter) categoryFilter.value = '';
        if (placeFilter) placeFilter.value = '';
        applyVolunteerFilters();
        searchInput?.focus();
    });
    document.querySelectorAll('[data-volunteer-pagination]').forEach(pagination => {
        pagination.addEventListener('click', event => {
            const button = event.target.closest('[data-page-step]');
            if (!button || button.disabled) return;
            const column = pagination.closest('[data-volunteer-column]');
            if (!column) return;
            pageNumbers.set(column, (pageNumbers.get(column) || 1) + Number(button.dataset.pageStep));
            renderVolunteerBoard();
        });
    });
    renderVolunteerBoard();
}

function normalizeFilterValue(value) {
    return String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase().trim();
}

function renderVolunteerBoard() {
    if (!sections) return;
    const query = normalizeFilterValue(searchInput?.value);
    const category = normalizeFilterValue(categoryFilter?.value);
    const place = String(placeFilter?.value || '');
    let matchingTotal = 0;
    let displayedTotal = 0;

    sections.querySelectorAll('[data-volunteer-column]').forEach(column => {
        const cards = [...column.querySelectorAll('[data-volunteer-item]')];
        const matching = cards.filter(card => {
            const textMatches = !query || normalizeFilterValue(card.dataset.search).includes(query);
            const categoryMatches = !category || normalizeFilterValue(card.dataset.category) === category;
            const placeMatches = !place || card.dataset.placeId === place;
            return textMatches && categoryMatches && placeMatches;
        });
        const totalPages = Math.max(1, Math.ceil(matching.length / pageSize));
        const currentPage = Math.min(pageNumbers.get(column) || 1, totalPages);
        pageNumbers.set(column, currentPage);
        const start = (currentPage - 1) * pageSize;
        const visibleCards = new Set(matching.slice(start, start + pageSize));
        cards.forEach(card => { card.hidden = !visibleCards.has(card); });

        const count = column.querySelector('[data-column-count]');
        const empty = column.querySelector('[data-column-empty]');
        const pagination = column.querySelector('[data-volunteer-pagination]');
        if (count) count.textContent = String(matching.length);
        if (empty) empty.hidden = matching.length > 0;
        if (pagination) {
            pagination.hidden = matching.length <= pageSize;
            const label = pagination.querySelector('[data-page-label]');
            const previous = pagination.querySelector('[data-page-step="-1"]');
            const next = pagination.querySelector('[data-page-step="1"]');
            if (label) label.textContent = `${currentPage} / ${totalPages}`;
            if (previous) previous.disabled = currentPage <= 1;
            if (next) next.disabled = currentPage >= totalPages;
        }
        matchingTotal += matching.length;
        displayedTotal += visibleCards.size;
    });

    if (filterStatus) {
        filterStatus.textContent = query || category || place
            ? `${displayedTotal} visibles de ${matchingTotal} resultados en tus columnas.`
            : `${matchingTotal} voluntariados disponibles y personales.`;
    }
    if (resetFilters) resetFilters.disabled = !query && !category && !place;
}

function applyVolunteerFilters() {
    sections?.querySelectorAll('[data-volunteer-column]').forEach(column => pageNumbers.set(column, 1));
    renderVolunteerBoard();
}