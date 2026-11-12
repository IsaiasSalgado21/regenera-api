<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="api-base-url" content="{{ url('/api') }}">
    <script>
        (() => {
            const savedTheme = document.cookie
                .split('; ')
                .find(cookie => cookie.startsWith('regenera_theme='))
                ?.split('=')[1];
            const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches;

            document.documentElement.dataset.theme = savedTheme === 'dark'
                || (savedTheme !== 'light' && prefersDark)
                ? 'dark'
                : 'light';
        })();
    </script>

    <title>@yield('title', 'Regenera BCS')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap"
        rel="stylesheet">

    {{-- Estilos compilados con Vite --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
        <link rel="stylesheet" href="{{ asset('css/login.css') }}">
        <link rel="stylesheet" href="{{ asset('css/theme.css') }}">
    </head>

    <body>

        {{-- =========================
             TOPBAR
        ========================== --}}
        <header class="topbar">

            <a href="{{ url('/') }}" class="logo">
                <span class="logo-mark">
                    <img src="{{ asset('img/path152221.png') }}" alt="Logo Regenera BCS">
                </span>
                <span>
                    Regenera<span>BCS</span>
                </span>
            </a>

            <nav class="nav">
                <a href="{{ url('/') }}">Inicio</a>
                <a href="{{ url('/flora') }}">Flora y fauna</a>
                <a href="{{ url('/voluntariados') }}">Voluntariados</a>
                <a href="{{ route('learning.index') }}">Aprendizaje</a>
                <a href="{{ route('mapa') }}">Mapa</a>
                <a href="{{ url('/articulos') }}">Artículos</a>
                <button type="button" class="notification-nav-button" data-notification-toggle
                    aria-label="Abrir notificaciones" aria-expanded="false" hidden>
                    <span aria-hidden="true">♧</span> Notificaciones
                    <span class="notification-nav-badge" data-notification-badge hidden>0</span>
                </button>

                @auth
                    <a href="{{ url('/perfil') }}" class="user-nav" data-auth-user>{{ Auth::user()->nombre }}@if (Auth::user()->rol === 'organizador' && (bool) Auth::user()->organizadorVerificado) <span class="verified-organizer-mark" aria-label="Organizador verificado" title="Organizador verificado">✓</span>@endif</a>
                    <a href="{{ url('/logout') }}" class="user-nav" data-auth-logout>Salir</a>
                @else
                    <a href="{{ route('login') }}" class="user-nav" data-auth-login>Iniciar sesión</a>
                @endauth
            </nav>

            <button class="theme-toggle" type="button" data-theme-toggle aria-label="Cambiar a modo oscuro" aria-pressed="false" title="Cambiar tema">
                <span aria-hidden="true" data-theme-icon>☾</span>
            </button>
            <button class="menu-button" id="menuButton" aria-label="Abrir menú">☰</button>
        </header>

        <section class="auth-modal" data-auth-modal hidden aria-hidden="true">
            <button class="auth-modal-backdrop" type="button" data-auth-close aria-label="Cerrar acceso"></button>
            <div class="auth-modal-dialog" role="dialog" aria-modal="true" aria-label="Acceder a Regenera BCS" tabindex="-1">
                <button class="auth-modal-close" type="button" data-auth-close aria-label="Cerrar">×</button>
                @include('auth.form', ['authPrefix' => 'modal'])
            </div>
        </section>

        {{-- =========================
             MENÚ MÓVIL
        ========================== --}}
        <nav class="mobile-menu" id="mobileMenu">
            <a href="{{ url('/') }}">Inicio</a>
            <a href="{{ url('/flora') }}">Flora y fauna</a>
            <a href="{{ url('/voluntariados') }}">Voluntariados</a>
            <a href="{{ route('learning.index') }}">Aprendizaje</a>
            <a href="{{ route('mapa') }}">Mapa</a>
            <a href="{{ url('/articulos') }}">Artículos</a>
            <a href="{{ route('notifications.index') }}" class="user-nav" data-auth-required hidden>Centro de notificaciones</a>

            @auth
                <a href="{{ url('/perfil') }}" class="user-nav" data-auth-user>{{ Auth::user()->nombre }}@if (Auth::user()->rol === 'organizador' && (bool) Auth::user()->organizadorVerificado) <span class="verified-organizer-mark" aria-label="Organizador verificado" title="Organizador verificado">✓</span>@endif</a>
                <a href="{{ url('/logout') }}" class="user-nav" data-auth-logout>Salir</a>
            @else
                <a href="{{ route('login') }}" class="user-nav" data-auth-login>Iniciar sesión</a>
            @endauth
            <button type="button" class="notification-nav-button" data-notification-toggle
                aria-label="Abrir notificaciones" aria-expanded="false" hidden>
                <span aria-hidden="true">♧</span> Notificaciones
                <span class="notification-nav-badge" data-notification-badge hidden>0</span>
            </button>
        </nav>

        @include('components.notifications-drawer')

        <main>
            @yield('content')
        </main>

        @stack('scripts')

        {{-- Script global del sitio (menú móvil) --}}
        @if (file_exists(public_path('js/site.js')))
            <script src="{{ asset('js/site.js') }}?v={{ filemtime(public_path('js/site.js')) }}"></script>
        @endif
        @if (file_exists(public_path('js/login.js')))
            <script src="{{ asset('js/login.js') }}?v={{ filemtime(public_path('js/login.js')) }}"></script>
        @endif

    </body>

    </html>