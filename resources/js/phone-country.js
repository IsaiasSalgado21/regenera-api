import { countries, countryForPhone } from './phone-country-data';

const flags = import.meta.glob('../../node_modules/flag-icons/flags/4x3/*.svg', {
    eager: true,
    query: '?url',
    import: 'default',
});
const pickers = new WeakMap();
const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
const flagRegion = region => ['AC', 'TA'].includes(region) ? 'gb' : region.toLowerCase();

function flagImage(region) {
    const url = flags[`../../node_modules/flag-icons/flags/4x3/${flagRegion(region)}.svg`];
    if (!url) throw new Error(`No se encontró la bandera de ${region}.`);
    return `url("${url}")`;
}

function initialize(fields) {
    const root = fields.querySelector('[data-phone-country]');
    const code = fields.querySelector('[name="telefonoLada"]');
    const phone = fields.querySelector('[name="telefono"]');
    const toggle = root.querySelector('[data-phone-country-toggle]');
    const flag = root.querySelector('[data-phone-country-flag]');
    const value = root.querySelector('[data-phone-country-value]');
    const menu = root.querySelector('[data-phone-country-menu]');
    const search = root.querySelector('[data-phone-country-search]');
    const list = root.querySelector('[data-phone-country-options]');
    const empty = root.querySelector('[data-phone-country-empty]');
    const options = [];
    let selected = null;

    const validate = () => {
        phone.setCustomValidity(phone.value.trim() && !code.value ? 'Selecciona la lada del país.' : '');
    };
    const updateDisplay = () => {
        flag.className = 'profile-phone-flag';
        flag.style.backgroundImage = selected ? flagImage(selected.region) : '';
        flag.textContent = selected ? '' : '🌐';
        value.textContent = code.value || 'Elegir';
        toggle.setAttribute('aria-label', selected
            ? `Lada: ${selected.name} ${selected.code}`
            : code.value ? `Prefijo guardado ${code.value}` : 'Selecciona una lada');
        toggle.title = toggle.getAttribute('aria-label');
        options.forEach(({ button, country }) => {
            button.setAttribute('aria-selected', String(selected?.region === country.region));
        });
        validate();
    };
    const close = () => {
        menu.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    };
    const filter = () => {
        const query = normalize(search.value.trim());
        let visible = 0;
        options.forEach(({ button, country }) => {
            button.hidden = !normalize(`${country.name} ${country.code}`).includes(query);
            if (!button.hidden) visible++;
        });
        empty.hidden = visible > 0;
    };
    const choose = country => {
        selected = country;
        code.value = country.code;
        updateDisplay();
        close();
        toggle.focus();
    };
    const fragment = document.createDocumentFragment();
    countries.forEach(country => {
        const button = document.createElement('button');
        button.type = 'button';
        button.tabIndex = -1;
        button.className = 'profile-phone-country-option';
        button.setAttribute('role', 'option');
        button.setAttribute('aria-selected', 'false');
        const countryFlag = document.createElement('span');
        countryFlag.className = 'profile-phone-flag';
        countryFlag.style.backgroundImage = flagImage(country.region);
        countryFlag.setAttribute('aria-hidden', 'true');
        const label = document.createElement('span');
        label.textContent = `${country.name} ${country.code}`;
        button.append(countryFlag, label);
        button.addEventListener('click', () => choose(country));
        options.push({ button, country });
        fragment.appendChild(button);
    });
    list.appendChild(fragment);
    toggle.addEventListener('click', () => {
        if (code.disabled) return;
        if (!menu.hidden) {
            close();
            return;
        }
        search.value = '';
        filter();
        menu.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        search.focus();
    });
    search.addEventListener('input', filter);
    menu.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            event.preventDefault();
            close();
            toggle.focus();
        } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            const visible = options.filter(option => !option.button.hidden);
            if (!visible.length) return;
            event.preventDefault();
            const index = visible.findIndex(option => option.button === document.activeElement);
            const next = index < 0
                ? (event.key === 'ArrowDown' ? 0 : visible.length - 1)
                : (index + (event.key === 'ArrowDown' ? 1 : -1) + visible.length) % visible.length;
            visible[next].button.focus();
        } else if (event.key === 'Enter' && event.target === search) {
            const visible = options.filter(option => !option.button.hidden);
            if (visible.length === 1) {
                event.preventDefault();
                choose(visible[0].country);
            }
        }
    });
    document.addEventListener('pointerdown', event => {
        if (!root.contains(event.target)) close();
    });
    root.addEventListener('focusout', event => {
        if (!root.contains(event.relatedTarget)) close();
    });
    phone.addEventListener('input', validate);
    fields.closest('form')?.addEventListener('reset', () => setTimeout(() => {
        close();
        setCode(fields, code.value);
    }, 0));
    pickers.set(fields, {
        setCode(prefix, number) {
            selected = countryForPhone(prefix, number);
            code.value = prefix;
            updateDisplay();
        },
    });
    setCode(fields, code.value);
}

function setCode(fields, code, number = '') {
    pickers.get(fields)?.setCode(code, number);
}

document.querySelectorAll('[data-profile-fields]').forEach(initialize);
window.RegeneraPhoneCountries = { setCode };
