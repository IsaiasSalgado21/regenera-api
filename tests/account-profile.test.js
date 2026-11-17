import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const window = {};
runInNewContext(readFileSync(new URL('../public/js/account-profile.js', import.meta.url), 'utf8'), {
    window,
    document: { querySelectorAll: () => [], querySelector: () => null },
    Date,
    WeakMap,
});

function profileFields() {
    const fields = {
        correoSecundario: { value: ' secundario@example.com ' },
        ciudad: { value: ' La Paz ' },
        genero: { value: 'mujer' },
        generoOtro: { value: 'Otro' },
        telefono: { value: '' },
        privacidad: { checked: false, disabled: false },
        tutorNombre: { value: 'Tutor' },
        tutorCorreo: { value: 'tutor@example.com' },
        tutorTelefono: { value: '123' },
        tutorFechaNacimiento: { value: '1980-01-01' },
        tutorAutorizacion: { checked: true },
    };
    const tutorInputs = Array.from({ length: 5 }, () => ({ required: false }));
    const tutor = { hidden: true, querySelectorAll: () => tutorInputs };
    const notice = { hidden: true };
    const otherGender = { hidden: true };
    const root = {
        querySelector(selector) {
            const name = selector.match(/^\[name="(.+)"\]$/)?.[1];
            if (name) return fields[name] || null;
            return {
                '[data-tutor-fields]': tutor,
                '[data-privacy-minor-notice]': notice,
                '[data-other-gender]': otherGender,
            }[selector] || null;
        },
    };
    return { root, fields, tutor, tutorInputs, notice, otherGender };
}

test('mantiene la privacidad de menores al cambiar el género en configuración', () => {
    const { root, fields, tutor, tutorInputs, notice, otherGender } = profileFields();
    const birth = `${new Date().getFullYear() - 15}-01-01`;
    window.RegeneraProfileFields.update(root, birth);
    fields.genero.value = 'otro';
    window.RegeneraProfileFields.update(root);
    assert.equal(fields.privacidad.checked, true);
    assert.equal(fields.privacidad.disabled, true);
    assert.equal(notice.hidden, false);
    assert.equal(tutor.hidden, false);
    assert.ok(tutorInputs.every(input => input.required));
    assert.equal(otherGender.hidden, false);
    window.RegeneraProfileFields.update(root, '1990-01-01');
    assert.equal(fields.privacidad.checked, false);
    assert.equal(fields.privacidad.disabled, false);
    assert.equal(notice.hidden, true);
});

test('construye el perfil sin descripción en el registro y conserva los datos del tutor', () => {
    const { root } = profileFields();
    const birth = `${new Date().getFullYear() - 15}-01-01`;
    const data = window.RegeneraProfileFields.values(root, birth);
    assert.equal(data.correoSecundario, 'secundario@example.com');
    assert.equal(data.ciudad, 'La Paz');
    assert.equal(Object.hasOwn(data, 'descripcion'), false);
    assert.equal(data.tutor.autorizacion, true);
});
