function queryName(layer, name) {
    if (layer === 'lugares' && name === 'tipo') return 'lugar_tipo';
    if (layer === 'flora-fauna' && name === 'tipo') return 'flora_tipo';
    return name;
}

function readForm(form) {
    return Object.fromEntries(
        Array.from(new FormData(form).entries()).filter(([, value]) => value !== '')
    );
}

export function initializeMapFilters(onChange) {
    const forms = new Map();
    document.querySelectorAll('[data-map-filters]').forEach(form => {
        forms.set(form.dataset.mapFilters, form);
    });

    const params = new URLSearchParams(window.location.search);
    forms.forEach((form, layer) => {
        Array.from(form.elements).forEach(field => {
            if (!field.name) return;
            const value = params.get(queryName(layer, field.name));
            if (value === null) return;

            if (
                field.name === 'lugar_id'
                && field instanceof HTMLSelectElement
                && !Array.from(field.options).some(option => option.value === value)
            ) {
                field.add(new Option(`Lugar del enlace: ${value}`, value));
            }
            field.value = value;
        });
    });

    function syncUrl() {
        const query = new URLSearchParams();
        forms.forEach((form, layer) => {
            Object.entries(readForm(form)).forEach(([name, value]) => {
                query.set(queryName(layer, name), value);
            });
        });

        const search = query.toString();
        window.history.replaceState(
            null,
            '',
            `${window.location.pathname}${search ? `?${search}` : ''}${window.location.hash}`
        );
    }

    function updateLayer(layer) {
        syncUrl();
        onChange(layer, readForm(forms.get(layer)));
    }

    forms.forEach((form, layer) => {
        let debounceTimer;
        form.addEventListener('submit', event => {
            event.preventDefault();
            window.clearTimeout(debounceTimer);
            updateLayer(layer);
        });

        form.addEventListener('change', event => {
            if (event.target.matches('input[type="date"]')) {
                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(() => updateLayer(layer), 350);
                return;
            }

            updateLayer(layer);
        });

        form.addEventListener('input', event => {
            if (!event.target.matches('input[type="date"]')) return;
            window.clearTimeout(debounceTimer);
            debounceTimer = window.setTimeout(() => updateLayer(layer), 350);
        });
    });

    document.querySelectorAll('[data-layer-clear]').forEach(button => {
        button.addEventListener('click', () => {
            const layer = button.dataset.layerClear;
            const form = forms.get(layer);
            if (!form) return;

            form.reset();
            updateLayer(layer);
        });
    });

    document.querySelectorAll('[data-layer-retry]').forEach(button => {
        button.addEventListener('click', () => updateLayer(button.dataset.layerRetry));
    });

    return {
        getFilters: layer => readForm(forms.get(layer)),
    };
}
