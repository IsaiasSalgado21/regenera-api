@extends('layouts.app')

@section('title', 'Mi perfil · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
@endpush

@section('content')
<main class="page profile-page" data-notification-profile>
    <section class="page-content profile-notification-settings">
        <span class="page-label">MI CUENTA</span>
        <h1>Preferencias de notificación</h1>
        <p>Configura cómo quieres recibir avisos de tus voluntariados y de tu cuenta.</p>

        <section class="profile-setting-card" data-reminder-settings>
            <h2>Recordatorios de voluntariados</h2>
            <p>Se enviarán para los voluntariados en los que tengas una inscripción activa.</p>
            <form data-reminder-form>
                <label for="reminderFrequency">Frecuencia</label>
                <select id="reminderFrequency" name="recordatorioFrecuencia">
                    <option value="none">No recibir recordatorios</option>
                    <option value="milestones">Un día y una hora antes de iniciar</option>
                    <option value="daily">Una vez al día hasta que inicie</option>
                </select>
                <button type="submit">Guardar frecuencia</button>
            </form>
        </section>

        <section class="profile-setting-card">
            <h2>Notificaciones push del navegador</h2>
            <p>Actívalas para recibir avisos aunque no tengas abierta la página. El navegador solicitará permiso solo cuando pulses el botón.</p>
            <button type="button" class="profile-push-button" data-push-toggle>Activar notificaciones push</button>
            <p class="profile-setting-status" data-profile-status role="status" aria-live="polite"></p>
        </section>

        <a class="section-link" href="{{ route('notifications.index') }}">Ir al centro de notificaciones →</a>
    </section>
</main>
@endsection
