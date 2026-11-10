@extends('layouts.app')

@section('title', 'Acceder a Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
@endpush

@section('content')
    <main class="auth-page auth-standalone">
        @include('auth.form', ['authPrefix' => 'page'])
    </main>
@endsection
