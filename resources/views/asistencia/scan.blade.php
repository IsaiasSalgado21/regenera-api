@extends('layouts.app')

@section('title', 'Registrar asistencia · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
@endpush

@section('content')
    <section class="attendance-page" id="attendancePage">
        <span class="page-label">REGENERA BCS / VOLUNTARIADOS</span>
        <h1>Registrar asistencia</h1>
        <p>Confirma tu asistencia a la actividad. El sistema verificará tu inscripción y el horario.</p>
        <p class="attendance-message" id="attendanceMessage" role="status" aria-live="polite">
            Preparando el registro…
        </p>
        <button class="button button-primary" id="attendanceSubmit" type="button" hidden>
            Confirmar asistencia
        </button>
        <a class="button button-secondary" id="attendanceLogin" href="{{ route('login') }}" hidden>
            Iniciar sesión
        </a>
        <form class="attendance-code-form" id="attendanceCodeForm">
            <label for="attendanceCode">¿No puedes escanear el QR? Escribe el código corto</label>
            <input id="attendanceCode" name="codigo" type="text" maxlength="8" autocomplete="off" autocapitalize="characters" required>
            <button class="button button-secondary" type="submit">Registrar con código</button>
            <p class="attendance-message" id="attendanceCodeMessage" role="status" aria-live="polite"></p>
        </form>
        <a class="attendance-back-link" href="{{ url('/voluntariados') }}">Ver voluntariados</a>
    </section>
@endsection

@push('scripts')
    @vite('resources/js/asistencia.js')
@endpush
