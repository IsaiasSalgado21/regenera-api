const sections = document.querySelector('[data-personal-volunteer-sections]');
const listOrder = new WeakMap();

if (sections) {
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
        const owned = data.misVoluntariados || [];
        const enrolled = data.misInscripciones || [];
        const publishedIds = new Set(owned
            .filter(item => item.estado === 'publicado')
            .map(item => String(item.id || item._id)));

        document.querySelectorAll('[data-public-volunteer]').forEach(card => {
            if (publishedIds.has(card.dataset.publicVolunteer)) card.remove();
        });

        renderGroup('[data-owned-volunteers]', '[data-owned-volunteer-list]', owned, 'owner');
        renderGroup('[data-enrolled-volunteers]', '[data-enrolled-volunteer-list]', enrolled, 'enrolled');
        updateTimers();
    } catch (error) {
        console.error('No se pudieron cargar los voluntariados personales.', error);
    }
}

function renderGroup(sectionSelector, listSelector, items, type) {
    const section = document.querySelector(sectionSelector);
    const list = document.querySelector(listSelector);
    if (!section || !list || !items.length) return;

    section.hidden = false;
    [...items].sort(compareVolunteers).forEach(item => {
        const card = document.createElement('article');
        card.className = 'volunteer-card volunteer-owned-card';
        card.dataset.startAt = item.inicioIso || '';
        card.dataset.endAt = item.finIso || '';
        card.dataset.sortId = String(item.id || item._id || '');
        const content = document.createElement('div');
        content.className = 'volunteer-card-content';

        if (type === 'owner') {
            const status = document.createElement('span');
            status.className = `volunteer-owned-status is-${item.estado}`;
            status.textContent = item.estado === 'pendiente'
                ? 'Pendiente de aprobación'
                : item.estado === 'rechazado' ? 'Rechazado' : 'Publicado';
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
        card.append(content);
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