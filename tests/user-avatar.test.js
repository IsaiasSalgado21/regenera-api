import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

function setup() {
    let onError;
    const document = {
        createElement(tag) {
            return {
                tag, children: [],
                setAttribute() {},
                append(child) { this.children.push(child); },
            };
        },
        addEventListener(type, handler, capture) {
            if (type === 'error' && capture) onError = handler;
        },
    };
    const window = {};
    runInNewContext(readFileSync(new URL('../public/js/user-avatar.js', import.meta.url), 'utf8'), { document, window });
    return { avatar: window.RegeneraAvatar, onError };
}

test('muestra la imagen guardada o una inicial si no existe', () => {
    const { avatar } = setup();
    const withPhoto = avatar.create('Ana', '/imagenes/avatar.png');
    assert.equal(withPhoto.children[0].textContent, 'A');
    assert.equal(withPhoto.children[1].src, '/imagenes/avatar.png');
    assert.equal(withPhoto.children[1].alt, '');
    const withoutPhoto = avatar.create('  María', null);
    assert.equal(withoutPhoto.children[0].textContent, 'M');
    assert.equal(withoutPhoto.children.length, 1);
    assert.equal(avatar.create('', null).children[0].textContent, '?');
});

test('una imagen rota deja visible la inicial sin afectar otras imágenes', () => {
    const { onError } = setup();
    let removed = false;
    onError({ target: { matches: selector => selector === '.user-avatar > img', remove: () => { removed = true; } } });
    assert.equal(removed, true);
    removed = false;
    onError({ target: { matches: () => false, remove: () => { removed = true; } } });
    assert.equal(removed, false);
});
