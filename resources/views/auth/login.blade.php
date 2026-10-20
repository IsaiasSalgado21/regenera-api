@extends('layouts.app')

@section('title', 'Iniciar sesión · Regenera BCS')

@push('styles')
    {{-- CSS propio del login (no requiere Vite, se sirve desde public/) --}}
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endpush

@section('content')
    <main class="auth-page">
        <div class="auth-box">

            <span class="page-label">REGENERA BCS</span>

            <h1>Iniciar sesión</h1>

            <p>
                Accede a Regenera BCS o crea una cuenta para participar,
                publicar iniciativas y conocer tu actividad.
            </p>

            <div class="auth-switch" role="tablist">
                <button type="button" class="active" data-auth-mode="login">Iniciar sesión</button>
                <button type="button" data-auth-mode="register">Crear cuenta</button>
            </div>

            <form id="loginForm" novalidate>

                <div class="auth-name-field" hidden>
                    <label for="nombre">Nombre</label>
                    <input type="text" id="nombre" autocomplete="name" placeholder="Tu nombre">
                </div>

                <label for="correo">Correo electrónico</label>
                <input type="email" id="correo" autocomplete="email" placeholder="correo@ejemplo.com" required>

                <label for="password">Contraseña</label>
                <div class="password-field">
                    <input type="password" id="password" autocomplete="current-password" placeholder="Mínimo 8 caracteres" required minlength="8">
                    <button type="button" id="togglePassword" aria-label="Mostrar contraseña" aria-pressed="false">Mostrar</button>
                </div>

                <p class="auth-message" id="authMessage" role="status" aria-live="polite"></p>

                <button type="submit" id="authSubmit">Iniciar sesión →</button>

            </form>

            <details class="demo-credentials">
                <summary>Usuarios sembrados en MongoDB</summary>
                <p><strong>Administrador:</strong> admin@regenerabcs.com · <code>Admin12345</code></p>
                <p><strong>Organizador:</strong> organizador@regenerabcs.com · <code>Organizador12345</code></p>
                <p><strong>Usuario:</strong> usuario@regenerabcs.com · <code>Usuario12345</code></p>
            </details>

            <p class="auth-demo">
                Conectado al backend Laravel + MongoDB (<code>regenera_bcs</code>).
            </p>

        </div>
    </main>
@endsection

@push('scripts')
    <script src="{{ asset('js/login.js') }}"></script>
@endpush