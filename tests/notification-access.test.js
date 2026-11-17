import test from 'node:test';
import assert from 'node:assert/strict';
import { loadForCompleteProfile } from '../resources/js/notification-access.js';

test('no consulta notificaciones sin sesión ni con perfil incompleto', async () => {
    let userRequests = 0;
    let notificationRequests = 0;
    const getUser = async () => {
        userRequests++;
        return { perfilCompleto: false };
    };
    const getNotifications = async () => { notificationRequests++; };

    assert.equal(await loadForCompleteProfile(null, () => null, getUser, getNotifications), null);
    assert.equal(await loadForCompleteProfile('token', () => 'token', getUser, getNotifications), null);
    assert.equal(userRequests, 1);
    assert.equal(notificationRequests, 0);
});

test('consulta notificaciones si el perfil está completo y la sesión no cambió', async () => {
    let requests = 0;
    const getNotifications = async token => {
        requests++;
        return { token };
    };
    const getUser = async () => ({ perfilCompleto: true });

    assert.deepEqual(await loadForCompleteProfile('actual', () => 'actual', getUser, getNotifications), { token: 'actual' });
    assert.equal(await loadForCompleteProfile('anterior', () => 'nueva', getUser, getNotifications), null);
    assert.equal(requests, 1);
});

test('propaga errores al verificar la sesión', async () => {
    await assert.rejects(
        loadForCompleteProfile('token', () => 'token', async () => { throw new Error('Sin conexión'); }, () => {}),
        /Sin conexión/,
    );
});
