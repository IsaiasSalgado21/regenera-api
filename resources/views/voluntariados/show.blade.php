@extends('layouts.app')

@section('title', $voluntariado->titulo . ' · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contenido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/voluntariados.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages-detail.css') }}">
@endpush

@section('content')
<main class="page volunteer-detail-page">
    <section class="page-hero">
        @if ($voluntariado->imagen)
            <div class="page-hero-background" style="background-image: url('{{ $voluntariado->imagen }}')"></div>
        @else
            <div class="page-hero-background volunteer-detail-placeholder"></div>
        @endif
        <div class="page-hero-overlay"></div>
        <div class="page-hero-content volunteer-detail-hero-content">
            @if ($voluntariado->categoria)
                <span class="volunteer-detail-category">{{ ucfirst(str_replace('-', ' ', $voluntariado->categoria)) }}</span>
            @endif
            <span class="page-label">PARTICIPA</span>
            <h1>{{ $voluntariado->titulo }}</h1>
            <p class="volunteer-author">Organiza: {{ $organizadorNombre ?: 'Organizador' }}</p>
            <p>{{ \Illuminate\Support\Str::limit($voluntariado->descripcion, 180) }}</p>
        </div>
    </section>

    <section class="page-content volunteer-detail-content">
        <a href="{{ url('/voluntariados') }}" class="volunteer-detail-back">← Volver a voluntariados</a>

        @if (count($voluntariado->imagenes ?? []))
            <section class="volunteer-gallery" data-volunteer-gallery aria-label="Galería del voluntariado">
                <div class="volunteer-gallery-main">
                    <button type="button" class="volunteer-gallery-image" data-gallery-open aria-label="Abrir imagen en grande">
                        <img src="{{ $voluntariado->imagenes[0] }}" alt="Imagen 1 de {{ $voluntariado->titulo }}" data-gallery-main>
                        <span class="volunteer-gallery-zoom" aria-hidden="true">⤢ Ver en grande</span>
                    </button>
                    @if (count($voluntariado->imagenes) > 1)
                        <button type="button" class="volunteer-gallery-arrow is-previous" data-gallery-step="-1" aria-label="Imagen anterior">‹</button>
                        <button type="button" class="volunteer-gallery-arrow is-next" data-gallery-step="1" aria-label="Imagen siguiente">›</button>
                        <span class="volunteer-gallery-count" data-gallery-count>1 / {{ count($voluntariado->imagenes) }}</span>
                    @endif
                </div>
                @if (count($voluntariado->imagenes) > 1)
                    <div class="volunteer-gallery-thumbnails" role="group" aria-label="Seleccionar imagen">
                        @foreach ($voluntariado->imagenes as $index => $image)
                            <button type="button" class="volunteer-gallery-thumb {{ $index === 0 ? 'is-active' : '' }}"
                                data-gallery-index="{{ $index }}"
                                aria-label="Mostrar imagen {{ $index + 1 }}"
                                aria-pressed="{{ $index === 0 ? 'true' : 'false' }}">
                                <img src="{{ $image }}" alt="" loading="lazy">
                            </button>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        <div class="volunteer-detail-grid">
            <article class="volunteer-detail-main">
                <header class="volunteer-detail-section-heading">
                    <span>UNA ACCIÓN QUE SUMA</span>
                    <h2>Detalles de la actividad</h2>
                </header>
                <section class="volunteer-detail-description">
                    <h3>Sobre esta actividad</h3>
                    <p>{{ $voluntariado->descripcion }}</p>
                </section>

                @if (is_array($voluntariado->requisitos) && count($voluntariado->requisitos))
                    <section class="volunteer-detail-list-section">
                        <h3><span aria-hidden="true">✓</span> Requisitos para participar</h3>
                        <ul class="volunteer-detail-chips">
                            @foreach ($voluntariado->requisitos as $requisito)
                                <li><span aria-hidden="true">✓</span>{{ $requisito }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                @if (is_array($voluntariado->materiales) && count($voluntariado->materiales))
                    <section class="volunteer-detail-list-section">
                        <h3><span aria-hidden="true">🎒</span> Qué llevar</h3>
                        <ul class="volunteer-detail-chips volunteer-detail-chips--materials">
                            @foreach ($voluntariado->materiales as $material)
                                <li><span aria-hidden="true">✦</span>{{ $material }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </article>

            
        <section class="volunteer-detail-card"
            data-volunteer-id="{{ (string) $voluntariado->_id }}"
            data-organizer-id="{{ (string) $voluntariado->organizadorId }}"
            data-volunteer-state="{{ $voluntariado->estado }}"
            data-event-start-at="{{ $inicioFechaHora?->toIso8601String() ?? '' }}"
            data-event-end-at="{{ $finFechaHora?->toIso8601String() ?? '' }}">

            <span class="volunteer-detail-card-kicker">ÚNETE A LA ACCIÓN</span>
            <h2>Tu participación importa.</h2>

            <div class="volunteer-owner-actions" data-volunteer-owner-actions hidden aria-label="Administrar voluntariado">
                <a class="button button-secondary" data-volunteer-owner-edit hidden>
                    Editar voluntariado
                </a>
                <button class="button button-secondary" type="button" data-volunteer-management-action="cancel" hidden>
                    Cancelar voluntariado
                </button>
                <button class="button button-secondary" type="button" data-volunteer-management-action="delete" hidden>
                    Eliminar voluntariado
                </button>
            </div>
            <p data-volunteer-management-status role="status" aria-live="polite"></p>

            <div class="volunteer-detail-facts">

                {{-- Fecha --}}
                @if ($voluntariado->fechaInicio)
                    <div class="volunteer-detail-fact">
                        <span class="volunteer-detail-fact-icon">📅</span>
                        <span>
                            <small>Fecha</small>
                            <strong>
                                {{ $voluntariado->fechaInicio->format('d/m/Y') }}
                                @if ($voluntariado->fechaFin &&
                                    $voluntariado->fechaFin->format('Y-m-d') !==
                                    $voluntariado->fechaInicio->format('Y-m-d'))
                                    – {{ $voluntariado->fechaFin->format('d/m/Y') }}
                                @endif
                            </strong>
                        </span>
                    </div>
                @endif

                {{-- Horario --}}
                @if ($voluntariado->horaInicio)
                    <div class="volunteer-detail-fact">
                        <span class="volunteer-detail-fact-icon">🕒</span>
                        <span>
                            <small>Horario de la actividad</small>
                            <strong>Inicio · {{ $voluntariado->horaInicio }}</strong>
                            @if ($voluntariado->horaFin)
                                <strong>Fin · {{ $voluntariado->horaFin }}</strong>
                            @endif
                        </span>
                    </div>
                @endif

                {{-- Lugar --}}
                @if ($lugar)
                    <div class="volunteer-detail-fact">
                        <span class="volunteer-detail-fact-icon">📍</span>
                        <span>
                            <small>Lugar</small>
                            <strong>{{ $lugar->nombre }}</strong>
                            @if ($lugar->municipio)
                                <small>{{ $lugar->municipio }}</small>
                            @endif
                        </span>
                    </div>
                @endif

                {{-- Mapa integrado --}}
                <div class="volunteer-inline-map">
                    <div class="volunteer-inline-map-heading">
                        <span>Ubicación del evento</span>
                        <span aria-hidden="true">↗</span>
                    </div>

                    @if ($ubicacion)
                        <div id="volunteerDetailMap"
                            class="volunteer-detail-map"
                            data-latitude="{{ $ubicacion['latitud'] }}"
                            data-longitude="{{ $ubicacion['longitud'] }}"
                            data-location-name="{{ $lugar?->nombre ?? $voluntariado->titulo }}"
                            data-location-address="{{ $lugar?->direccion ?? '' }}"
                            role="application"
                            aria-label="Mapa de ubicación del voluntariado">
                        </div>
                    @else
                        <p class="volunteer-map-unavailable">
                            Ubicación del mapa pendiente de configurar.
                        </p>
                    @endif
                </div>

                @if ($voluntariado->estado)
                    <span class="volunteer-detail-status volunteer-detail-status--{{ $voluntariado->estado }}">
                        <span aria-hidden="true">●</span>
                        {{ ucfirst($voluntariado->estado) }}
                    </span>
                @endif

            </div>

            @if ($voluntariadoCancelado)
                <p class="volunteer-cta-note" role="status">Este voluntariado fue cancelado.</p>
            @elseif ($voluntariadoFinalizado)
                <p class="volunteer-cta-note" role="status">Este voluntariado ya finalizó.</p>
            @elseif ($isOwner || $isAdmin)
                <p class="volunteer-cta-note" role="status">{{ $isOwner ? 'Organizas este voluntariado; no puedes inscribirte.' : 'La administración no puede inscribirse a voluntariados.' }}</p>
            @else
                {{-- Acciones de inscripción --}}
                <div class="volunteer-enrollment-actions" data-enrollment-actions>
                    <a href="{{ route('login', ['redirect' => request()->getPathInfo() . '?participar=1']) }}"
                        class="volunteer-cta"
                        data-voluntariado-id="{{ (string) $voluntariado->_id }}">
                        <span aria-hidden="true">🙌</span>
                        Quiero participar
                        <span class="volunteer-cta-arrow" aria-hidden="true">→</span>
                    </a>

                    <button type="button" class="volunteer-cancel-cta"
                        data-enrollment-cancel hidden>
                        Cancelar participación
                    </button>
                </div>

                <p class="volunteer-cta-note" data-owner-enrollment-note hidden></p>

                <p class="volunteer-cta-note" data-enrollment-note>
                    Súmate y forma parte del cambio en Baja California Sur.
                </p>

                <p id="enrollmentMessage"
                    class="volunteer-enrollment-message"
                    role="status"
                    aria-live="polite"></p>

                <section class="volunteer-attendance-tools" data-participant-attendance hidden aria-labelledby="participantAttendanceHeading">
                    <h3 id="participantAttendanceHeading">Registrar asistencia</h3>
                    <p data-participant-attendance-status role="status" aria-live="polite">La actividad está en curso.</p>
                    <form data-attendance-code-form>
                        <label for="detailAttendanceCode">Código corto</label>
                        <div class="volunteer-enrollment-actions">
                            <input id="detailAttendanceCode" name="codigo" type="text" maxlength="8" autocapitalize="characters" autocomplete="off" required>
                            <button class="button button-primary" type="submit">Registrar</button>
                        </div>
                    </form>
                    <div class="volunteer-enrollment-actions">
                        <button class="button button-secondary" type="button" data-start-attendance-camera>Escanear con cámara</button>
                        <label class="button button-secondary" for="attendanceQrPhoto">Tomar foto del QR</label>
                        <input id="attendanceQrPhoto" type="file" accept="image/*" capture="environment" data-attendance-qr-photo hidden>
                    </div>
                    <div data-attendance-camera-container hidden>
                        <video data-attendance-camera playsinline muted></video>
                        <button class="button button-secondary" type="button" data-stop-attendance-camera>Detener cámara</button>
                    </div>
                </section>

            @endif

            <section class="volunteer-attendance-result" data-participant-attendance-result hidden aria-live="polite">
                <h3 data-participant-attendance-result-title></h3>
                <p data-participant-attendance-result-message></p>
                <button class="button button-secondary" type="button" data-request-attendance-review hidden>
                    Solicitar revisión al propietario
                </button>
            </section>

        </section>
        </div>

        @php
            $totalCupo = (int) ($voluntariado->cupo ?? 0);
            $totalInscritos = (int) ($inscritosCount ?? 0);
            $disponibles = max(0, $totalCupo - $totalInscritos);
            $ocupacion = $totalCupo > 0 ? min(100, ($totalInscritos / $totalCupo) * 100) : 0;
        @endphp
        <section class="volunteer-activity-card" aria-labelledby="volunteer-activity-title">
            <header class="volunteer-activity-card-heading">
                <div><span>SEGUIMIENTO</span><h2 id="volunteer-activity-title">Actividad del voluntariado</h2></div>
                <span>{{ ucfirst($voluntariado->estado) }}</span>
            </header>
            <div class="volunteer-capacity" data-volunteer-capacity>
                <div class="volunteer-capacity-heading">
                    <span class="volunteer-detail-fact-icon">👥</span>
                    <span>
                        <small>Disponibilidad de lugares</small>
                        <strong><span data-capacity-available>{{ $disponibles }}</span> espacios disponibles</strong>
                    </span>
                </div>
                <div class="volunteer-capacity-bar"><span data-capacity-progress style="width: {{ $ocupacion }}%"></span></div>
                <div class="volunteer-capacity-stats">
                    <div><strong data-capacity-enrolled>{{ $totalInscritos }}</strong><small>Inscritos</small></div>
                    <div><strong data-capacity-available-count>{{ $disponibles }}</strong><small>Disponibles</small></div>
                    <div><strong>{{ $totalCupo }}</strong><small>Cupo total</small></div>
                </div>
            </div>
            <section class="volunteer-owner-activity" data-owner-volunteer-activity hidden aria-labelledby="ownerActivityHeading">
                <h3 id="ownerActivityHeading">Participantes y resultados</h3>
                <p data-owner-activity-status role="status" aria-live="polite">Cargando participantes…</p>
                <div class="volunteer-owner-activity-stats" data-owner-activity-stats></div>
                <div class="volunteer-owner-participants" data-owner-activity-participants></div>
            </section>
        </section>

        <section class="volunteer-impact" aria-labelledby="volunteer-impact-title">
            <header class="volunteer-impact-heading">
                <span>HUELLA POSITIVA</span>
                <h2 id="volunteer-impact-title">Impacto de esta actividad</h2>
                <p>Resultados reportados por el equipo organizador. Cada gráfica tiene su propia unidad y escala.</p>
            </header>

                <form class="volunteer-impact-form" data-volunteer-impact-form hidden>
                    <label>Tipo de impacto
                        <select name="tipo" required>
                            <option value="">Selecciona un resultado</option>
                            @foreach ($impactoCatalogo as $tipo => $metric)
                                <option value="{{ $tipo }}">{{ $metric['etiqueta'] }} ({{ $metric['unidad'] }})</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Cantidad
                        <input name="cantidad" type="number" min="0.01" max="1000000000" step="0.01" required>
                    </label>
                    <label>Nota <span>Opcional</span>
                        <input name="nota" type="text" maxlength="500" placeholder="Contexto del resultado">
                    </label>
                    <button class="button button-primary" type="submit" data-impact-submit>Registrar impacto</button>
                    <p class="volunteer-impact-form-status" data-impact-status role="status" aria-live="polite"></p>
                </form>

            @if (count($impacto['totales']))
                <div class="volunteer-impact-grid">
                    @foreach ($impacto['totales'] as $total)
                        @php
                            $metricRecords = array_values(array_filter(
                                $impacto['registros'],
                                fn ($record) => $record['tipo'] === $total['tipo'],
                            ));
                            $chartRecords = array_reverse(array_slice($metricRecords, 0, 6));
                            $maxAmount = max(array_column($chartRecords, 'cantidad') ?: [0]);
                        @endphp
                        <article class="volunteer-impact-card">
                            <div class="volunteer-impact-card-heading">
                                <span class="volunteer-impact-label">{{ $total['etiqueta'] }}</span>
                                <strong class="volunteer-impact-total">
                                    {{ number_format($total['cantidad'], 2, '.', ',') }}
                                    <small>{{ $total['unidad'] }}</small>
                                </strong>
                            </div>

                            @if (count($chartRecords))
                                <div class="volunteer-impact-chart"
                                    role="img"
                                    aria-label="{{ $total['etiqueta'] }}: {{ count($chartRecords) }} registros recientes. Escala independiente en {{ $total['unidad'] }}.">
                                    @foreach ($chartRecords as $record)
                                        @php
                                            $barHeight = $maxAmount > 0
                                                ? max(8, ($record['cantidad'] / $maxAmount) * 100)
                                                : 8;
                                        @endphp
                                        <div class="volunteer-impact-column">
                                            <span class="volunteer-impact-value">{{ number_format($record['cantidad'], 2, '.', ',') }}</span>
                                            <div class="volunteer-impact-bar-track">
                                                <span class="volunteer-impact-bar volunteer-impact-bar--{{ $total['tipo'] }}"
                                                    style="height: {{ $barHeight }}%"></span>
                                            </div>
                                            <time class="volunteer-impact-date"
                                                datetime="{{ $record['registradoEn'] ?? '' }}">
                                                {{ $record['registradoEn']
                                                    ? \Illuminate\Support\Carbon::parse($record['registradoEn'])->timezone('America/Mazatlan')->format('d M')
                                                    : 's/f' }}
                                            </time>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="volunteer-impact-chart-note">
                                    Últimos {{ count($chartRecords) }} registros · escala en {{ $total['unidad'] }}
                                </p>
                            @else
                                <p class="volunteer-impact-chart-note">Aún no hay registros individuales para graficar.</p>
                            @endif
                        </article>
                    @endforeach
                </div>

                @if (count($impacto['registros']))
                    <details class="volunteer-impact-history">
                        <summary>Ver registros recientes ({{ min(8, count($impacto['registros'])) }})</summary>
                        <ul>
                            @foreach (array_slice($impacto['registros'], 0, 8) as $record)
                                <li>
                                    <span>
                                        <strong>{{ $record['etiqueta'] }}</strong>
                                        · {{ number_format($record['cantidad'], 2, '.', ',') }} {{ $record['unidad'] }}
                                        @if ($record['nota'])
                                            <small>{{ $record['nota'] }}</small>
                                        @endif
                                    </span>
                                    @if ($record['registradoEn'])
                                        <time datetime="{{ $record['registradoEn'] }}">
                                            {{ \Illuminate\Support\Carbon::parse($record['registradoEn'])->timezone('America/Mazatlan')->format('d/m/Y') }}
                                        </time>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            @else
                <div class="volunteer-impact-empty">
                    <span aria-hidden="true">✦</span>
                    <p>Aún no se han registrado resultados. El impacto se actualizará cuando el equipo reporte los logros de la actividad.</p>
                </div>
            @endif
        </section>

        <div class="detail-back">
            <a href="{{ url('/voluntariados') }}" class="section-link">← Volver a voluntariados</a>
        </div>
    </section>
</main>

<dialog class="volunteer-lightbox" data-gallery-lightbox aria-label="Visor de imágenes">
    <button type="button" class="volunteer-lightbox-close" data-gallery-close aria-label="Cerrar imagen">×</button>
    <button type="button" class="volunteer-lightbox-arrow is-previous" data-lightbox-step="-1" aria-label="Imagen anterior">‹</button>
    <figure>
        <img src="{{ $voluntariado->imagenes[0] ?? '' }}" alt="Imagen del voluntariado" data-lightbox-image>
        <figcaption data-lightbox-caption>1 / {{ count($voluntariado->imagenes ?? []) }}</figcaption>
    </figure>
    <button type="button" class="volunteer-lightbox-arrow is-next" data-lightbox-step="1" aria-label="Imagen siguiente">›</button>
</dialog>

<dialog class="volunteer-cancel-dialog" data-enrollment-cancel-dialog aria-labelledby="cancelEnrollmentTitle" aria-describedby="cancelEnrollmentDescription">
    <div class="volunteer-cancel-dialog-content">
        <span class="volunteer-cancel-dialog-icon" aria-hidden="true">!</span>
        <h2 id="cancelEnrollmentTitle">Cancelar participación</h2>
        <p id="cancelEnrollmentDescription">¿Quieres cancelar tu lugar en este voluntariado? El cupo quedará disponible para otra persona.</p>
        <p class="volunteer-cancel-dialog-status" data-enrollment-cancel-status role="status" aria-live="polite"></p>
        <div class="volunteer-cancel-dialog-actions">
            <button type="button" class="button button-secondary" data-enrollment-cancel-dismiss>Conservar mi lugar</button>
            <button type="button" class="button button-danger" data-enrollment-cancel-confirm>Cancelar participación</button>
        </div>
    </div>
</dialog>

<footer class="page-footer">
    <div class="footer-logo">Regenera<span>BCS</span></div>
    <p>Participación ambiental · Baja California Sur</p>
</footer>
@endsection

@push('scripts')
    @vite('resources/js/voluntariado-detail.js')
    <script src="{{ asset('js/voluntariado-calendar.js') }}"></script>
@endpush
