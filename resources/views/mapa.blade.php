@extends('layouts.app')

@section('title', 'Mapa · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/mapa.css') }}">
@endpush

@section('content')
<main class="page map-page">
    <section class="page-hero">
        <div class="page-hero-overlay"></div>
        <div class="page-hero-content">
            <span class="page-label">EXPLORA BCS</span>
            <h1>Mapa ambiental.</h1>
            <p>Consulta voluntariados, espacios naturales y registros de flora y fauna.</p>
        </div>
    </section>

    <section class="page-content map-content">
        <div class="map-layout">
            <div class="map-main">
                <section class="map-filter-card" aria-labelledby="volunteerFiltersTitle">
                    <div class="map-layer-heading">
                        <label class="map-layer-toggle">
                            <input type="checkbox" data-layer-toggle="voluntariados" checked>
                            <span id="volunteerFiltersTitle">Voluntariados</span>
                        </label>
                        <span class="map-layer-count" data-layer-count="voluntariados">Cargando…</span>
                    </div>
                    <form class="map-filter-form" data-map-filters="voluntariados">
                        <label>
                            Categoría
                            <select name="categoria" data-tooltip="Filtra voluntariados por su categoría.">
                                <option value="">Todas</option>
                                <option value="limpieza">Limpieza</option>
                                <option value="restauracion">Restauración</option>
                                <option value="conservacion">Conservación</option>
                                <option value="reforestacion">Reforestación</option>
                                <option value="monitoreo">Monitoreo</option>
                                <option value="educacion-ambiental">Educación ambiental</option>
                            </select>
                        </label>
                        <label>
                            Desde
                            <input type="date" name="fecha_desde" data-tooltip="Fecha inicial de las actividades.">
                        </label>
                        <label>
                            Hasta
                            <input type="date" name="fecha_hasta" data-tooltip="Fecha final de las actividades.">
                        </label>
                        <label>
                            Lugar
                            <select name="lugar_id" data-map-place-filter data-tooltip="Muestra actividades de un lugar específico.">
                                <option value="">Todos los lugares</option>
                            </select>
                        </label>
                        <label>
                            Disponibilidad
                            <select name="disponibles" data-tooltip="Filtra actividades con cupo o que ya están llenas.">
                                <option value="">Todos</option>
                                <option value="1">Con cupo</option>
                                <option value="0">Llenos</option>
                            </select>
                        </label>
                        <button type="submit" class="map-filter-button">Aplicar</button>
                        <button type="button" class="map-clear-button" data-layer-clear="voluntariados">Limpiar filtros</button>
                    </form>
                    <div class="map-layer-feedback" data-layer-feedback="voluntariados" role="status" aria-live="polite">
                        <button type="button" data-layer-retry="voluntariados" hidden>Reintentar</button>
                    </div>
                </section>

                <section class="map-filter-card" aria-labelledby="placesFiltersTitle">
                    <div class="map-layer-heading">
                        <label class="map-layer-toggle">
                            <input type="checkbox" data-layer-toggle="lugares" checked>
                            <span id="placesFiltersTitle">Lugares</span>
                        </label>
                        <span class="map-layer-count" data-layer-count="lugares">Cargando…</span>
                    </div>
                    <form class="map-filter-form" data-map-filters="lugares">
                        <label>
                            Tipo
                            <select name="tipo" data-tooltip="Filtra por tipo de lugar.">
                                <option value="">Todos</option>
                                <option value="playa">Playa</option>
                                <option value="parque">Parque</option>
                                <option value="reserva">Reserva</option>
                                <option value="zona_urbana">Zona urbana</option>
                                <option value="otro">Otro</option>
                            </select>
                        </label>
                        <button type="submit" class="map-filter-button">Aplicar</button>
                        <button type="button" class="map-clear-button" data-layer-clear="lugares">Limpiar filtros</button>
                    </form>
                    <div class="map-layer-feedback" data-layer-feedback="lugares" role="status" aria-live="polite">
                        <button type="button" data-layer-retry="lugares" hidden>Reintentar</button>
                    </div>
                </section>

                <section class="map-filter-card" aria-labelledby="speciesFiltersTitle">
                    <div class="map-layer-heading">
                        <label class="map-layer-toggle">
                            <input type="checkbox" data-layer-toggle="flora-fauna" checked>
                            <span id="speciesFiltersTitle">Flora y fauna</span>
                        </label>
                        <span class="map-layer-count" data-layer-count="flora-fauna">Cargando…</span>
                    </div>
                    <form class="map-filter-form" data-map-filters="flora-fauna">
                        <label>
                            Tipo
                            <select name="tipo" data-tooltip="Filtra registros de flora o fauna.">
                                <option value="">Flora y fauna</option>
                                <option value="flora">Flora</option>
                                <option value="fauna">Fauna</option>
                            </select>
                        </label>
                        <label>
                            Estado de conservación
                            <select name="estado_conservacion" data-tooltip="Filtra por estado de conservación.">
                                <option value="">Todos</option>
                                <option value="en_peligro">En peligro</option>
                                <option value="en_peligro_critico">En peligro crítico</option>
                                <option value="vulnerable">Vulnerable</option>
                            </select>
                        </label>
                        <button type="submit" class="map-filter-button">Aplicar</button>
                        <button type="button" class="map-clear-button" data-layer-clear="flora-fauna">Limpiar filtros</button>
                    </form>
                    <div class="map-layer-feedback" data-layer-feedback="flora-fauna" role="status" aria-live="polite">
                        <button type="button" data-layer-retry="flora-fauna" hidden>Reintentar</button>
                    </div>
                </section>

                <div id="environmentMap"
                     class="environment-map"
                     role="region"
                     aria-label="Mapa interactivo de voluntariados, lugares y especies">
                </div>
            </div>

            <aside id="mapInfoPanel" class="map-info-panel" aria-label="Información del elemento seleccionado" tabindex="-1">
                <p class="map-panel-placeholder">Selecciona un marcador para consultar su información.</p>
            </aside>
        </div>
    </section>
</main>

<footer class="page-footer">
    <div class="footer-logo">Regenera<span>BCS</span></div>
    <p>Participación ambiental · Baja California Sur</p>
</footer>
@endsection

@push('scripts')
    @vite('resources/js/map/map-entry.js')
@endpush
