(() => {
    const privacyBeforeMinor = new WeakMap();
    const savedBirthDates = new WeakMap();

    function cityOptions(fields) {
        return Array.from(fields.querySelectorAll('[data-city-option]'), option => option.dataset.cityOption);
    }

    function updateCity(fields) {
        const city = fields.querySelector('[name="ciudad"]');
        const other = fields.querySelector('[name="ciudadOtra"]');
        const isOther = city.value === 'Otra localidad';
        fields.querySelector('[data-other-city]').hidden = !isOther;
        other.required = isOther && !city.disabled;
        city.setCustomValidity(!city.value || cityOptions(fields).includes(city.value)
            ? '' : 'Selecciona una sugerencia o elige «Otra localidad».');
    }

    function setCity(fields, value) {
        const city = fields.querySelector('[name="ciudad"]');
        const other = fields.querySelector('[name="ciudadOtra"]');
        const known = !value || cityOptions(fields).includes(value);
        city.value = known ? value : 'Otra localidad';
        other.value = known ? '' : value;
        updateCity(fields);
    }

    function setPhone(fields, value) {
        const phone = fields.querySelector('[name="telefono"]');
        const code = fields.querySelector('[name="telefonoLada"]');
        const digits = (value || '').replace(/\D/g, '');
        if (!value) {
            code.value = '+52';
            phone.value = '';
        } else if (!value.trim().startsWith('+') && digits.length === 10) {
            code.value = '+52';
            phone.value = digits;
        } else if (value.trim().startsWith('+') && digits.length >= 7) {
            const twoDigitCodes = '20 27 30 31 32 33 34 36 39 40 41 43 44 45 46 47 48 49 51 52 53 54 55 56 57 58 60 61 62 63 64 65 66 81 82 84 86 90 91 92 93 94 95 98'.split(' ');
            const length = ['1', '7'].includes(digits[0]) ? 1 : twoDigitCodes.includes(digits.slice(0, 2)) ? 2 : 3;
            code.value = `+${digits.slice(0, length)}`;
            phone.value = digits.slice(length);
        } else {
            code.value = '';
            phone.value = value;
        }
        window.RegeneraPhoneCountries?.setCode(fields, code.value, value);
    }

    function isMinor(value) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value || '')) return false;
        const [year, month, day] = value.split('-').map(Number);
        const today = new Date();
        let age = today.getFullYear() - year;
        if (today.getMonth() + 1 < month || (today.getMonth() + 1 === month && today.getDate() < day)) age--;
        return age >= 0 && age < 18;
    }

    function update(fields, birthDate) {
        if (birthDate !== undefined) savedBirthDates.set(fields, birthDate);
        const minor = isMinor(birthDate ?? fields.querySelector('[data-birth-date]')?.value ?? savedBirthDates.get(fields));
        const tutor = fields.querySelector('[data-tutor-fields]');
        tutor.hidden = !minor;
        tutor.querySelectorAll('input').forEach(input => { input.required = minor; });
        const privacy = fields.querySelector('[name="privacidad"]');
        if (minor) {
            if (!privacyBeforeMinor.has(privacy)) privacyBeforeMinor.set(privacy, privacy.checked);
            privacy.checked = true;
        } else if (privacyBeforeMinor.has(privacy)) {
            privacy.checked = privacyBeforeMinor.get(privacy);
            privacyBeforeMinor.delete(privacy);
        }
        privacy.disabled = minor;
        fields.querySelector('[data-privacy-minor-notice]').hidden = !minor;
        fields.querySelector('[data-other-gender]').hidden = fields.querySelector('[name="genero"]').value !== 'otro';
    }

    function values(fields, birthDate) {
        const get = name => fields.querySelector(`[name="${name}"]`);
        const data = {};
        ['correoSecundario', 'ciudad', 'genero', 'generoOtro', 'telefono', 'descripcion'].forEach(name => {
            const field = get(name);
            if (field) data[name] = field.value.trim();
        });
        if (data.ciudad === 'Otra localidad') data.ciudad = get('ciudadOtra').value.trim();
        if (data.telefono) data.telefono = `${get('telefonoLada').value.trim()}${data.telefono}`;
        const birth = get('fechaNacimiento');
        if (birth) data.fechaNacimiento = birth.value;
        data.privacidad = get('privacidad').checked;
        if (isMinor(birthDate ?? birth?.value)) {
            data.tutor = {
                nombre: get('tutorNombre').value.trim(),
                correo: get('tutorCorreo').value.trim(),
                telefono: get('tutorTelefono').value.trim(),
                fechaNacimiento: get('tutorFechaNacimiento').value,
                autorizacion: get('tutorAutorizacion').checked,
            };
        }
        return data;
    }

    document.querySelectorAll('[data-profile-fields]').forEach(fields => {
        const city = fields.querySelector('[name="ciudad"]');
        const picker = fields.querySelector('[data-city-picker]');
        const list = fields.querySelector('[data-city-options]');
        const options = Array.from(fields.querySelectorAll('[data-city-option]'));
        const normalize = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        let active = -1;
        const close = () => {
            list.hidden = true;
            city.setAttribute('aria-expanded', 'false');
            city.removeAttribute('aria-activedescendant');
            options.forEach(option => option.classList.remove('is-active'));
            active = -1;
        };
        const open = (filter = '') => {
            if (city.disabled) return;
            options.forEach(option => {
                option.hidden = option.dataset.cityOption !== 'Otra localidad'
                    && !normalize(option.dataset.cityOption).includes(normalize(filter));
                option.setAttribute('aria-selected', String(option.dataset.cityOption === city.value));
                option.classList.remove('is-active');
            });
            active = -1;
            list.hidden = false;
            city.setAttribute('aria-expanded', 'true');
            city.removeAttribute('aria-activedescendant');
        };
        const choose = option => {
            city.value = option.dataset.cityOption;
            updateCity(fields);
            close();
        };
        city.addEventListener('focus', () => open());
        city.addEventListener('click', () => open());
        city.addEventListener('input', () => {
            updateCity(fields);
            open(city.value);
        });
        city.addEventListener('change', () => updateCity(fields));
        city.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                close();
            } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                if (list.hidden) open();
                const visible = options.filter(option => !option.hidden);
                if (!visible.length) return;
                active = (active + (event.key === 'ArrowDown' ? 1 : -1) + visible.length) % visible.length;
                options.forEach(option => option.classList.remove('is-active'));
                visible[active].classList.add('is-active');
                city.setAttribute('aria-activedescendant', visible[active].id);
                visible[active].scrollIntoView({ block: 'nearest' });
            } else if (event.key === 'Enter' && !list.hidden && active >= 0) {
                event.preventDefault();
                choose(options.filter(option => !option.hidden)[active]);
            }
        });
        fields.querySelector('[data-city-toggle]').addEventListener('click', () => {
            if (city.disabled) return;
            if (list.hidden) {
                city.focus();
                open();
            } else {
                close();
            }
        });
        options.forEach(option => {
            option.addEventListener('mousedown', event => event.preventDefault());
            option.addEventListener('click', () => choose(option));
        });
        picker.addEventListener('focusout', event => {
            if (!picker.contains(event.relatedTarget)) close();
        });
        fields.closest('form')?.addEventListener('reset', () => setTimeout(() => {
            close();
            updateCity(fields);
        }, 0));
        const parts = fields.querySelector('[data-birth-parts]');
        if (parts) {
            const day = parts.querySelector('[data-birth-day]');
            const month = parts.querySelector('[data-birth-month]');
            const year = parts.querySelector('[data-birth-year]');
            const birth = parts.querySelector('[data-birth-date]');
            const syncBirth = () => {
                day.setCustomValidity('');
                birth.value = '';
                if (day.value && month.value && year.value && year.validity.valid) {
                    const y = Number(year.value);
                    const m = Number(month.value);
                    const d = Number(day.value);
                    const leapYear = y % 4 === 0 && (y % 100 !== 0 || y % 400 === 0);
                    const daysInMonth = [31, leapYear ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
                    if (d > daysInMonth[m - 1]) {
                        day.setCustomValidity('Selecciona una fecha de nacimiento válida.');
                    } else {
                        birth.value = `${String(y).padStart(4, '0')}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                    }
                }
                birth.dispatchEvent(new Event('change', { bubbles: true }));
            };
            [day, month, year].forEach(field => {
                field.addEventListener('input', syncBirth);
                field.addEventListener('change', syncBirth);
            });
            fields.closest('form')?.addEventListener('reset', () => {
                setTimeout(() => {
                    privacyBeforeMinor.delete(fields.querySelector('[name="privacidad"]'));
                    day.setCustomValidity('');
                    update(fields);
                }, 0);
            });
        }
        fields.querySelector('[data-birth-date]')?.addEventListener('change', event => update(fields, event.target.value));
        fields.querySelector('[name="genero"]').addEventListener('change', () => update(fields));
        updateCity(fields);
        update(fields);
    });
    window.RegeneraProfileFields = { update, values, setCity, setPhone, updateCity };

    const form = document.querySelector('[data-complete-profile]');
    if (!form) return;
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (!form.reportValidity()) return;
        const status = form.querySelector('[data-complete-status]');
        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        status.textContent = '';
        try {
            const token = sessionStorage.getItem('regenera_access_token');
            if (!token) throw new Error('Inicia sesión para completar tus datos.');
            const base = document.querySelector('meta[name="api-base-url"]').content;
            const response = await fetch(`${base}/auth/completar-perfil`, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', Authorization: `Bearer ${token}` },
                body: JSON.stringify(values(form.querySelector('[data-profile-fields]'))),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message);
            window.location.assign(`${base.replace(/\/api\/?$/, '')}/perfil`);
        } catch (error) {
            status.textContent = error.message || 'No se pudieron guardar tus datos.';
        } finally {
            button.disabled = false;
        }
    });
})();
