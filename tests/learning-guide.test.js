import test from 'node:test';
import assert from 'node:assert/strict';
import { parseStudyGuide } from '../resources/js/learning-guide.js';

test('interpreta títulos, párrafos, listas y referencias seguras', () => {
    const blocks = parseStudyGuide([
        '## Conceptos clave',
        'El manglar [filtra agua](https://www.biodiversidad.gob.mx/ecosistemas/manglares.html).',
        '',
        '- Retiene sedimentos.',
        '- Da refugio.',
    ].join('\n'));

    assert.deepEqual(blocks.map(block => block.type), ['heading', 'paragraph', 'list']);
    assert.equal(blocks[0].level, 4);
    assert.deepEqual(blocks[1].children[1], {
        type: 'link',
        value: 'filtra agua',
        url: 'https://www.biodiversidad.gob.mx/ecosistemas/manglares.html',
    });
    assert.equal(blocks[2].items.length, 2);
});

test('conserva HTML y enlaces no seguros como texto sin convertirlos en elementos', () => {
    const source = '<img src=x onerror=alert(1)> [ataque](javascript:alert(1))';
    const [paragraph] = parseStudyGuide(source);

    assert.ok(paragraph.children.every(node => node.type === 'text'));
    assert.equal(paragraph.children.map(node => node.value).join(''), source);
});
