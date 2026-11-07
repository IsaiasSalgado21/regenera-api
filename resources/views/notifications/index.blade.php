@extends('layouts.app')

@section('title', 'Notificaciones · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
@endpush

@section('content')
<main class="page notification-center-page" data-notification-center>
    <section class="page-content notification-center">
        <header class="notification-center-header">
            <div>
                <span class="page-label">REGENERA BCS</span>
                <h1>Centro de notificaciones</h1>
                <p>Consulta avisos nuevos y anteriores; las notificaciones leídas se conservan aquí.</p>
            </div>
            <a class="section-link" href="{{ url('/perfil') }}">Preferencias de notificación</a>
        </header>
        <div class="notification-center-toolbar">
            <div class="notification-filters" role="group" aria-label="Filtrar notificaciones">
                <button type="button" class="is-active" data-center-filter="all">Todas</button>
                <button type="button" data-center-filter="unread">No leídas</button>
            </div>
            <button type="button" class="notification-mark-all" data-center-mark-all>Marcar mis notificaciones como leídas</button>
        </div>
        <p class="notification-center-status" data-center-status role="status" aria-live="polite"></p>
        <div class="notification-center-list" data-center-list aria-live="polite"></div>
        <div class="notification-admin-columns" data-admin-columns hidden>
            <section class="notification-admin-column notification-admin-column--admins">
                <header>
                    <h2>Dirigidas a administradores</h2>
                    <span data-admin-count="admins">0</span>
                </header>
                <p>Avisos enviados a perfiles de administración.</p>
                <div class="notification-center-list" data-admin-list="admins" aria-live="polite"></div>
            </section>
            <section class="notification-admin-column notification-admin-column--general">
                <header>
                    <h2>Notificaciones generales</h2>
                    <span data-admin-count="general">0</span>
                </header>
                <p>Avisos dirigidos a usuarios y organizadores.</p>
                <div class="notification-center-list" data-admin-list="general" aria-live="polite"></div>
            </section>
        </div>
    </section>
</main>
@endsection
