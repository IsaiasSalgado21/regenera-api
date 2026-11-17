(() => {
    function isMinor(value) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value || '')) return false;
        const [year, month, day] = value.split('-').map(Number);
        const today = new Date();
        let age = today.getFullYear() - year;
        if (today.getMonth() + 1 < month || (today.getMonth() + 1 === month && today.getDate() < day)) age--;
        return age < 18;
    }

    function update(fields, birthDate) {
        const minor = isMinor(birthDate ?? fields.querySelector('[data-birth-date]')?.value);
        const tutor = fields.querySelector('[data-tutor-fields]');
        tutor.hidden = !minor;
        tutor.querySelectorAll('input').forEach(input => { input.required = minor; });
        const privacy = fields.querySelector('[name="privacidad"]');
        if (minor) privacy.checked = true;
        privacy.disabled = minor;
    }

    function values(fields, birthDate) {
        const get = name => fields.querySelector(`[name="${name}"]`);
        const data = {};
        ['correoSecundario', 'ciudad', 'genero', 'generoOtro', 'telefono', 'descripcion'].forEach(name => {
            data[name] = get(name).value.trim();
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
        fields.querySelector('[data-birth-date]')?.addEventListener('change', event => update(fields, event.target.value));
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
