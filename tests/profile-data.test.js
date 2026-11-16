import test from 'node:test';
import assert from 'node:assert/strict';
import { ownRecords, participationStatus, selectProfileMedals } from '../resources/js/profile-data.js';

test('muestra las medallas recientes por fecha y las pendientes del rol', () => {
    const medals = [
        { nombre: 'Anterior', desbloqueada: true, fechaObtencion: '2024-01-01' },
        { nombre: 'Pendiente', desbloqueada: false, disponibleParaRol: true },
        { nombre: 'Otro rol', desbloqueada: false, disponibleParaRol: false },
        { nombre: 'Reciente', desbloqueada: true, fechaObtencion: '2025-01-01' },
        { nombre: 'Sin fecha', desbloqueada: true, fechaObtencion: null },
        { nombre: 'Más reciente', desbloqueada: true, fechaObtencion: '2026-01-01' },
    ];
    const result = selectProfileMedals(medals);
    assert.deepEqual(result.recent.map(medal => medal.nombre), ['Más reciente', 'Reciente', 'Anterior']);
    assert.deepEqual(result.earned.map(medal => medal.nombre), ['Más reciente', 'Reciente', 'Anterior', 'Sin fecha']);
    assert.deepEqual(result.locked.map(medal => medal.nombre), ['Pendiente']);
});

test('solo presenta asistencia confirmada cuando existe confirmación y no hay cancelación', () => {
    const finished = { estado: 'finalizado', finalizadoPorFecha: true };
    assert.equal(participationStatus(finished, { estado: 'completada' }, null).label, 'Asistencia no confirmada');
    assert.equal(participationStatus(finished, { estado: 'inscrita' }, { confirmada: true }).label, 'Asistencia confirmada');
    assert.equal(participationStatus(finished, { estado: 'cancelada' }, { confirmada: true }).label, 'Inscripción cancelada');
    assert.equal(participationStatus({ estado: 'cancelado' }, { estado: 'inscrita' }, null).label, 'Voluntariado cancelado');
    assert.equal(participationStatus({ estado: 'publicado' }, { estado: 'inscrita' }, null).label, 'Inscripción activa');
    assert.equal(participationStatus(finished, { estado: 'desconocido' }, { confirmada: true }).label, 'Estado de inscripción no disponible');
});

test('el historial solo usa registros de la cuenta autenticada', () => {
    const records = [{ usuarioId: 'actual' }, { usuarioId: 'otro' }];
    assert.deepEqual(ownRecords(records, 'actual'), [records[0]]);
});
