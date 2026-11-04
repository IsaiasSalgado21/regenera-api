
document.addEventListener('DOMContentLoaded', () => {
    const calendar = document.querySelector('.volunteer-calendar-card');

    if (!calendar) return;

    const previousButton = document.getElementById('calendarPrev');
    const nextButton = document.getElementById('calendarNext');
    const monthLabel = document.getElementById('calendarMonth');
    const daysContainer = document.getElementById('calendarDays');
    const selectedDateLabel = document.getElementById('calendarSelectedDate');
    if (
        !previousButton ||
        !nextButton ||
        !monthLabel ||
        !daysContainer ||
        !selectedDateLabel
    ) {
        return;
    }

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const currentMonth = new Date(
        today.getFullYear(),
        today.getMonth(),
        1
    );

    function parseDate(value) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value || '')) return null;
        const [year, month, day] = value.split('-').map(Number);
        const date = new Date(year, month - 1, day);
        date.setHours(0, 0, 0, 0);
        return date;
    }

    const eventStart = parseDate(calendar.dataset.eventDate);
    const parsedEventEnd = parseDate(calendar.dataset.eventEnd);
    const eventEnd = parsedEventEnd && eventStart && parsedEventEnd >= eventStart
        ? parsedEventEnd
        : eventStart;
    let displayedMonth = new Date(
        (eventStart || today).getFullYear(),
        (eventStart || today).getMonth(),
        1
    );

    const monthFormatter = new Intl.DateTimeFormat('es-MX', {
        month: 'long',
        year: 'numeric'
    });

    const dateFormatter = new Intl.DateTimeFormat('es-MX', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });

    function isEventDay(date) {
        return Boolean(eventStart && eventEnd && date >= eventStart && date <= eventEnd);
    }

    function renderCalendar() {
        const year = displayedMonth.getFullYear();
        const month = displayedMonth.getMonth();

        monthLabel.textContent = monthFormatter.format(displayedMonth);
        daysContainer.replaceChildren();

        // JavaScript empieza la semana en domingo.
        // Convertimos el índice para que la semana comience en lunes.
        const firstDay = new Date(year, month, 1);
        const offset = (firstDay.getDay() + 6) % 7;
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        for (let i = 0; i < offset; i++) {
            const emptyDay = document.createElement('span');
            emptyDay.setAttribute('aria-hidden', 'true');
            daysContainer.appendChild(emptyDay);
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(year, month, day);
            const dayButton = document.createElement('button');

            dayButton.type = 'button';
            dayButton.textContent = day;
            dayButton.disabled = true;

            if (date.getTime() === today.getTime()) {
                dayButton.classList.add('calendar-today');
                dayButton.setAttribute('aria-label', `${day}, hoy`);
            }

            if (isEventDay(date)) {
                dayButton.classList.add('calendar-event');
                const isStart = date.getTime() === eventStart.getTime();
                const isEnd = date.getTime() === eventEnd.getTime();
                dayButton.classList.toggle('calendar-event-start', isStart);
                dayButton.classList.toggle('calendar-event-end', isEnd);
                dayButton.setAttribute('aria-label', `${day}, ${isStart ? 'inicio del' : isEnd ? 'fin del' : 'día del'} voluntariado`);
            }
            
            const eventLegend = document.querySelector('.calendar-legend-event');
            const todayLegend = document.querySelector('.calendar-legend-today');

            function highlightLegend(element, legend, active) {
                if (!legend) return;

                const item = legend.closest('.calendar-legend-item');

                legend.classList.toggle('is-highlighted', active);

                if (item) {
                    item.classList.toggle('is-highlighted', active);
                }
            }

            if (date.getTime() === today.getTime()) {
                dayButton.addEventListener('mouseenter', () => {
                    highlightLegend(dayButton, todayLegend, true);
                });

                dayButton.addEventListener('mouseleave', () => {
                    highlightLegend(dayButton, todayLegend, false);
                });

                dayButton.addEventListener('focus', () => {
                    highlightLegend(dayButton, todayLegend, true);
                });

                dayButton.addEventListener('blur', () => {
                    highlightLegend(dayButton, todayLegend, false);
                });
            }

            if (isEventDay(date)) {
                dayButton.addEventListener('mouseenter', () => {
                    highlightLegend(dayButton, eventLegend, true);
                });

                dayButton.addEventListener('mouseleave', () => {
                    highlightLegend(dayButton, eventLegend, false);
                });

                dayButton.addEventListener('focus', () => {
                    highlightLegend(dayButton, eventLegend, true);
                });

                dayButton.addEventListener('blur', () => {
                    highlightLegend(dayButton, eventLegend, false);
                });
            }

            daysContainer.appendChild(dayButton);
        }

        previousButton.disabled = displayedMonth <= currentMonth;
    }

    previousButton.addEventListener('click', () => {
        if (displayedMonth <= currentMonth) return;

        displayedMonth = new Date(
            displayedMonth.getFullYear(),
            displayedMonth.getMonth() - 1,
            1
        );

        renderCalendar();
    });

    nextButton.addEventListener('click', () => {
        displayedMonth = new Date(
            displayedMonth.getFullYear(),
            displayedMonth.getMonth() + 1,
            1
        );

        renderCalendar();
    });

    selectedDateLabel.textContent = eventStart && eventEnd
        ? eventStart.getTime() === eventEnd.getTime()
            ? dateFormatter.format(eventStart)
            : `${dateFormatter.format(eventStart)} – ${dateFormatter.format(eventEnd)}`
        : 'Sin fecha programada';

    renderCalendar();
});
