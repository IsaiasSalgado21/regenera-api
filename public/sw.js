self.addEventListener('push', event => {
    let payload = {};
    try {
        payload = event.data ? event.data.json() : {};
    } catch (error) {
        console.error('Push payload is not valid JSON.', error);
    }

    const title = payload.title || 'Regenera BCS';
    const options = {
        body: payload.body || 'Tienes una nueva notificación.',
        data: { url: payload.url || '/notificaciones' },
        icon: '/img/logo.svg',
        badge: '/img/logo.svg',
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    const target = new URL(event.notification.data?.url || '/notificaciones', self.location.origin);
    const safeUrl = target.origin === self.location.origin ? target.href : `${self.location.origin}/notificaciones`;
    event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clients => {
        const existing = clients.find(client => new URL(client.url).origin === self.location.origin);
        if (existing) {
            existing.navigate(safeUrl);
            return existing.focus();
        }
        return self.clients.openWindow(safeUrl);
    }));
});
