
document.addEventListener('DOMContentLoaded', () => {
    const calendar = document.querySelector('.volunteer-calendar-card');

    if (!calendar) return;

    const previousButton = document.getElementById('calendarPrev');
    const nextButton = document.getElementById('calendarNext');
    const monthLabel = document.getElementById('calendarMonth');
    const daysContainer = document.getElementById('calendarDays');
    const selectedDateLabel = document.getElementById('calendarSelectedDate');
    const dayButton = document.createElement('button');
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

    const rawEventDate = calendar.dataset.eventDate;
    let eventDate = null;

    if (/^\d{4}-\d{2}-\d{2}$/.test(rawEventDate || '')) {
        const [year, month, day] = rawEventDate.split('-').map(Number);

        eventDate = new Date(year, month - 1, day);
        eventDate.setHours(0, 0, 0, 0);
    }

    let displayedMonth = eventDate
        ? new Date(eventDate.getFullYear(), eventDate.getMonth(), 1)
        : new Date(currentMonth);

    // Nunca comenzar en un mes anterior al actual.
    if (displayedMonth < currentMonth) {
        displayedMonth = new Date(currentMonth);
    }

    const monthFormatter = new Intl.DateTimeFormat('es-MX', {
        month: 'long',
        year: 'numeric'
    });

    const dateFormatter = new Intl.DateTimeFormat('es-MX', {
        day: 'numeric',
        month: 'long',
        year: 'numeric'
    });

    function sameDate(a, b) {
        return Boolean(
            a &&
            b &&
            a.getFullYear() === b.getFullYear() &&
            a.getMonth() === b.getMonth() &&
            a.getDate() === b.getDate()
        );
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

            if (sameDate(date, today)) {
                dayButton.classList.add('calendar-today');
                dayButton.setAttribute('aria-label', `${day}, hoy`);
            }

            if (sameDate(date, eventDate)) {
                dayButton.classList.add('calendar-event');
                dayButton.setAttribute(
                    'aria-label',
                    `${day}, fecha del voluntariado`
                );
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

            if (sameDate(date, today)) {
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

            if (sameDate(date, eventDate)) {
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

        // Bloquear navegación hacia meses anteriores al actual.
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

    selectedDateLabel.textContent = eventDate
        ? dateFormatter.format(eventDate)
        : 'Sin fecha programada';

    renderCalendar();
});
