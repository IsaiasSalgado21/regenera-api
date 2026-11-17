@extends('layouts.app')
@section('title', 'Completar datos · Regenera BCS')
@section('content')
<main class="profile-page">
    <div class="profile-shell">
        <section class="profile-panel profile-account-settings">
            <h1>Completa tus datos</h1>
            <p>Necesitamos estos datos para continuar. Tu correo secundario y los datos del tutor son privados.</p>
            <form data-complete-profile>
                @include('auth.profile-fields', ['profilePrefix' => 'complete', 'profileBirth' => true, 'profileBirthParts' => true, 'profileDescription' => false])
                <button type="submit">Guardar y continuar</button>
                <p data-complete-status role="status" aria-live="polite"></p>
            </form>
        </section>
    </div>
</main>
@endsection
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/profile.css') }}">
@endpush
