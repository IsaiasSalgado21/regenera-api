@extends('layouts.app')

@section('title', 'Mapa · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/mapa.css') }}">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

@section('content')
<main class="page map-page">
    <section class="page-hero">
        <div class="page-hero-overlay"></div>
        <div class="page-hero-content">
            <span class="page-label">EXPLORA BCS</span>
            <h1>Mapa ambiental.</h1>
            <p>Ubica espacios naturales, especies y próximas actividades ambientales.</p>
        </div>
    </section>

    <section class="page-content map-content">
        <div class="map-toolbar">
            <div class="map-filters" aria-label="Filtrar marcadores del mapa">
                <label><input type="checkbox" value="lugares" checked> Lugares</label>
                <label><input type="checkbox" value="flora" checked> Flora</label>
                <label><input type="checkbox" value="fauna" checked> Fauna</label>
                <label><input type="checkbox" value="actividades" checked> Actividades</label>
            </div>

            <label class="map-municipality-filter">
                Municipio
                <select id="mapMunicipality">
                    <option value="">Todos</option>
                    @foreach ($municipios as $municipio)
                        <option value="{{ $municipio }}">{{ $municipio }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <p class="map-status" id="mapStatus" role="status" aria-live="polite"></p>
        <div id="environmentMap"
             class="environment-map"
             role="region"
             aria-label="Mapa interactivo de lugares, especies y actividades">
        </div>
        <p class="map-attribution-note">
            Los registros de flora y fauna se muestran según sus coordenadas registradas o los lugares relacionados.
        </p>
    </section>
</main>

<footer class="page-footer">
    <div class="footer-logo">Regenera<span>BCS</span></div>
    <p>Participación ambiental · Baja California Sur</p>
</footer>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>
    <script>
        window.regeneraMapMarkers = @json($marcadores, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
    </script>
    <script src="{{ asset('js/mapa.js') }}"></script>
@endpush
