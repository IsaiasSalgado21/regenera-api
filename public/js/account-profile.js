(() => {
    const privacyBeforeMinor = new WeakMap();
    const savedBirthDates = new WeakMap();

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
        update(fields);
    });
    window.RegeneraProfileFields = { update, values };

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
