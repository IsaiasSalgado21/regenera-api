import test from 'node:test';
import assert from 'node:assert/strict';
import { countries, countryForPhone } from '../resources/js/phone-country-data.js';

test('conserva los países que comparten lada al identificar números existentes', () => {
    assert.equal(countryForPhone('+52', '+526121234567')?.region, 'MX');
    assert.equal(countryForPhone('+1', '+14165550123')?.region, 'CA');
    assert.equal(countryForPhone('+1', '+12025550123')?.region, 'US');
    assert.equal(countryForPhone('+1'), null);
    assert.equal(countryForPhone('+800', '+80012345678'), null);
});

test('incluye países con prefijos de una, dos y tres cifras', () => {
    for (const [region, code] of [['US', '+1'], ['MX', '+52'], ['PT', '+351']]) {
        assert.ok(countries.some(country => country.region === region && country.code === code));
    }
});
