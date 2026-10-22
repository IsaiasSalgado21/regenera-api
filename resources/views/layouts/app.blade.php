<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Regenera BCS')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap"
        rel="stylesheet">

    {{-- Estilos compilados con Vite --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @stack('styles')
    </head>

    <body>

        {{-- =========================
             TOPBAR
        ========================== --}}
        <header class="topbar">

            <a href="{{ url('/') }}" class="logo">
                <span class="logo-mark">
                    <img src="{{ asset('img/logo.svg') }}" alt="Logo Regenera BCS">
                </span>
                <span>
                    Regenera<span>BCS</span>
                </span>
            </a>

            <nav class="nav">
                <a href="{{ url('/') }}">Inicio</a>
                <a href="{{ url('/flora') }}">Flora y fauna</a>
                <a href="{{ url('/voluntariados') }}">Voluntariados</a>
                <a href="{{ url('/articulos') }}">Artículos</a>

                @auth
                    <a href="{{ url('/perfil') }}" class="user-nav">{{ Auth::user()->nombre }}</a>
                    <a href="{{ url('/logout') }}" class="user-nav">Salir</a>
                @else
                    <a href="{{ route('login') }}" class="user-nav">Iniciar sesión</a>
                @endauth
            </nav>

            <button class="menu-button" id="menuButton" aria-label="Abrir menú">☰</button>
        </header>

        {{-- =========================
             MENÚ MÓVIL
        ========================== --}}
        <nav class="mobile-menu" id="mobileMenu">
            <a href="{{ url('/') }}">Inicio</a>
            <a href="{{ url('/flora') }}">Flora y fauna</a>
            <a href="{{ url('/voluntariados') }}">Voluntariados</a>
            <a href="{{ url('/articulos') }}">Artículos</a>

            @auth
                <a href="{{ url('/perfil') }}" class="user-nav">{{ Auth::user()->nombre }}</a>
                <a href="{{ url('/logout') }}" class="user-nav">Salir</a>
            @else
                <a href="{{ route('login') }}" class="user-nav">Iniciar sesión</a>
            @endauth
        </nav>

        <main>
            @yield('content')
        </main>

        @stack('scripts')

        {{-- Script global del sitio (menú móvil) --}}
        @if (file_exists(public_path('js/site.js')))
            <script src="{{ asset('js/site.js') }}"></script>
        @endif

    </body>

    </html>