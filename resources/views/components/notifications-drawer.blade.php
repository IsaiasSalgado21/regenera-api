<div class="notification-drawer-backdrop" data-notification-close hidden></div>
<aside class="notification-drawer" data-notification-drawer aria-labelledby="notificationDrawerTitle"
    aria-modal="true" role="dialog" hidden tabindex="-1">
    <div class="notification-drawer-header">
        <div>
            <p class="notification-drawer-eyebrow">REGENERA BCS</p>
            <h2 id="notificationDrawerTitle">Notificaciones</h2>
        </div>
        <button type="button" class="notification-drawer-close" data-notification-close
            aria-label="Cerrar notificaciones">×</button>
    </div>
    <div class="notification-drawer-actions">
        <div class="notification-filters" role="group" aria-label="Filtrar notificaciones">
            <button type="button" class="is-active" data-notification-filter="all">Todas</button>
            <button type="button" data-notification-filter="unread">No leídas</button>
        </div>
        <button type="button" class="notification-mark-all" data-notification-mark-all>
            Marcar mis notificaciones como leídas
        </button>
    </div>
    <a class="notification-center-link" href="{{ route('notifications.index') }}">Ver todas las notificaciones</a>
    <p class="notification-drawer-status" data-notification-status role="status" aria-live="polite"></p>
    <div class="notification-drawer-list" data-notification-list></div>
</aside>
