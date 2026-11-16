export const medalIcons = [
    { id: 'shield-check', label: 'Protección' },
    { id: 'mangrove', label: 'Manglar' },
    { id: 'recycle', label: 'Reciclaje' },
    { id: 'leader', label: 'Liderazgo' },
];

export function medalIconMarkup(iconName) {
    const symbols = { 'shield-check': 'proteccion', recycle: 'reciclaje' };
    if (Object.hasOwn(symbols, iconName) && document.body.dataset.symbolsBase) {
        const base = document.body.dataset.symbolsBase.replace(/\/+$/, '')
            .replace(/[&<>"']/g, character => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
            })[character]);
        return `<img class="ui-symbol" src="${base}/${symbols[iconName]}.png" alt="" aria-hidden="true" width="23" height="23">`;
    }
    const icons = {
        'shield-check': '<path d="M12 3 19 6v5c0 4.5-2.9 8-7 10-4.1-2-7-5.5-7-10V6l7-3Z"/><path d="m9 12 2 2 4-4"/>',
        mangrove: '<path d="M12 20V9"/><path d="M12 13 7 9m5 7 5-5"/><path d="M5 8c0-2 2-3 4-2 0-2 2-3 4-2 2-1 4 1 4 3 2 0 3 2 2 4-1 2-4 2-6 1-2 2-5 2-7 0-2 0-3-2-1-4Z"/><path d="M6 20h12"/>',
        recycle: '<path d="m7 7 2-3 3 1"/><path d="m9 4 2 4H6"/><path d="m17 8 3 1v4"/><path d="m20 9-4 2 2 3"/><path d="m8 17-1 3-4-1"/><path d="m7 20-1-4 4 1"/><path d="M8 8a5 5 0 0 1 8 1m1 4a5 5 0 0 1-4 5m-4-1a5 5 0 0 1-2-6"/>',
        leader: '<circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/><path d="m18 4 1 2 2 1-2 1-1 2-1-2-2-1 2-1 1-2Z"/>',
    };
    const locked = '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/><path d="M12 14v3"/>';
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${Object.hasOwn(icons, iconName) ? icons[iconName] : locked}</svg>`;
}
