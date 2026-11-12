const apiBaseUrl = document.querySelector('meta[name="api-base-url"]')?.content || '/api';

export function apiUrl(path) {
    return path.startsWith('/api/') ? `${apiBaseUrl}${path.slice(4)}` : path;
}