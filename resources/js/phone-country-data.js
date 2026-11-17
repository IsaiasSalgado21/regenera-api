import { getCountries, getCountryCallingCode, parsePhoneNumberFromString } from 'libphonenumber-js';

const names = new Intl.DisplayNames(['es'], { type: 'region' });

export const countries = getCountries()
    .map(region => ({ region, name: names.of(region), code: `+${getCountryCallingCode(region)}` }))
    .sort((a, b) => a.name.localeCompare(b.name, 'es'));

export function countryForPhone(prefix, number = '') {
    const parsed = typeof number === 'string' && number.trim().startsWith('+')
        ? parsePhoneNumberFromString(number) : null;
    const matches = countries.filter(country => country.code === prefix);
    return matches.find(country => country.region === parsed?.country)
        || (matches.length === 1 ? matches[0] : null);
}
