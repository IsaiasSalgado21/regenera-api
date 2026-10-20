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

    {{-- Estilos compilados con Vite (login.css + login.js) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>

<body>

    <header class="topbar">
        <a href="{{ url('/') }}" class="logo">
            <span class="logo-mark">
                <img src="{{ asset('img/logo.svg') }}" alt="Logo Regenera BCS">
            </span>
            <span>
                Regenera<span>BCS</span>
            </span>
        </a>
    </header>

    <main>
        @yield('content')
    </main>

    @stack('scripts')

    </body>

</html>