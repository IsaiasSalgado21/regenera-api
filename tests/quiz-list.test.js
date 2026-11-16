import test from 'node:test';
import assert from 'node:assert/strict';
import { quizPage } from '../resources/js/quiz-list.js';

test('busca por nombre sin distinguir acentos ni mayúsculas y pagina los resultados', () => {
    const quizzes = Array.from({ length: 8 }, (_, index) => ({ titulo: `Conservación ${index + 1}` }));
    quizzes.push({ titulo: 'Seguridad' });
    const first = quizPage(quizzes, ' CONSERVACION ', 1);
    const second = quizPage(quizzes, 'conservación', 2);
    assert.equal(first.total, 8);
    assert.equal(first.pages, 2);
    assert.equal(first.items.length, 6);
    assert.deepEqual(second.items.map(quiz => quiz.titulo), ['Conservación 7', 'Conservación 8']);
});

test('ajusta páginas fuera de rango y muestra un resultado vacío al buscar sin coincidencias', () => {
    const quizzes = [{ titulo: 'Manglar' }, { titulo: 'Playa' }];
    assert.equal(quizPage(quizzes, '', 9).page, 1);
    assert.deepEqual(quizPage(quizzes, 'desierto', 2), { items: [], total: 0, page: 1, pages: 1 });
});
