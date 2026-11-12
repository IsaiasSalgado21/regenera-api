@extends('layouts.app')

@section('title', 'Artículos · Regenera BCS')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contenido.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages-detail.css') }}">
@endpush

@section('content')
<main class="article-library" data-article-library>
    <header class="article-library-header">
        <div>
            <span class="article-library-eyebrow">LECTURAS · BAJA CALIFORNIA SUR</span>
            <h1>Artículos</h1>
            <p>Ideas, conocimiento local e historias de conservación.</p>
        </div>
        <a class="article-submit-link" href="{{ route('articulos.create') }}">Proponer un artículo <span aria-hidden="true">↗</span></a>
    </header>

    <section class="article-library-filters" aria-label="Buscar artículos">
        <label class="article-search-field">
            <span>Buscar</span>
            <input type="search" data-article-search placeholder="Título, texto o autor" autocomplete="off">
        </label>
        <label>
            <span>Tema</span>
            <select data-article-category>
                <option value="">Todos los temas</option>
                @foreach ($categorias as $categoria)
                    <option value="{{ $categoria }}">{{ $categoria }}</option>
                @endforeach
            </select>
        </label>
        <button type="button" data-article-reset disabled>Limpiar</button>
        <p data-article-result-count role="status" aria-live="polite"></p>
    </section>

    <div class="article-library-grid" data-article-list>
        @foreach ($articulos as $articulo)
            <article class="article-library-card" data-article-item
                data-category="{{ $articulo['categoria'] }}"
                data-search="{{ $articulo['titulo'] }} {{ $articulo['contenidoBusqueda'] }} {{ $articulo['autorNombre'] }} {{ $articulo['categoria'] }}">
                <a class="article-library-card-link" href="{{ url('/articulos/' . $articulo['id']) }}" aria-label="Leer artículo: {{ $articulo['titulo'] }}">
                    @if ($articulo['imagen'])
                        <img class="article-library-image" src="{{ $articulo['imagen'] }}" alt="" loading="lazy" decoding="async">
                    @else
                        <div class="article-library-image article-library-image--empty" aria-hidden="true"><span>LECTURA</span></div>
                    @endif
                    <div class="article-library-copy">
                        <span class="article-library-category">{{ $articulo['categoria'] ?: 'Artículo' }}</span>
                        <h2>{{ $articulo['titulo'] }}</h2>
                        <p>{{ $articulo['resumen'] }}</p>
                        <div class="article-library-byline">
                            <span>{{ $articulo['autorNombre'] }}</span>
                            @if ($articulo['fecha'])
                                <time datetime="{{ $articulo['fechaIso'] }}">{{ $articulo['fecha'] }}</time>
                            @endif
                        </div>
                        <span class="article-library-read-link">Leer artículo <span aria-hidden="true">→</span></span>
                    </div>
                </a>
            </article>
        @endforeach
    </div>
    <p class="article-library-empty" data-article-empty @if ($articulos->count()) hidden @endif>No hay artículos publicados todavía.</p>
    <nav class="article-library-pagination" data-article-pagination aria-label="Páginas de artículos">
        <button type="button" data-article-page="-1" aria-label="Página anterior">←</button>
        <span data-article-page-label></span>
        <button type="button" data-article-page="1" aria-label="Página siguiente">→</button>
    </nav>
</main>

<footer class="page-footer">
    <div class="footer-logo">Regenera<span>BCS</span></div>
    <p>Turismo responsable · Baja California Sur</p>
</footer>

@endsection

@push('scripts')
    <script>
        (() => {
            const library = document.querySelector('[data-article-library]');
            if (!library) return;

            const search = library.querySelector('[data-article-search]');
            const category = library.querySelector('[data-article-category]');
            const reset = library.querySelector('[data-article-reset]');
            const count = library.querySelector('[data-article-result-count]');
            const empty = library.querySelector('[data-article-empty]');
            const pagination = library.querySelector('[data-article-pagination]');
            const pageLabel = library.querySelector('[data-article-page-label]');
            const cards = [...library.querySelectorAll('[data-article-item]')];
            const pageSize = 10;
            let currentPage = 1;

            const normalize = value => String(value ?? '').normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase().trim();

            function render() {
                const query = normalize(search.value);
                const selectedCategory = normalize(category.value);
                const results = cards.filter(card =>
                    (!query || normalize(card.dataset.search).includes(query))
                    && (!selectedCategory || normalize(card.dataset.category) === selectedCategory),
                );
                const pageCount = Math.max(1, Math.ceil(results.length / pageSize));
                currentPage = Math.min(currentPage, pageCount);
                const visible = new Set(results.slice((currentPage - 1) * pageSize, currentPage * pageSize));
                cards.forEach(card => { card.hidden = !visible.has(card); });
                count.textContent = `${results.length} ${results.length === 1 ? 'artículo' : 'artículos'}`;
                empty.hidden = results.length > 0;
                pagination.hidden = results.length <= pageSize;
                pageLabel.textContent = `${currentPage} / ${pageCount}`;
                pagination.querySelector('[data-article-page="-1"]').disabled = currentPage <= 1;
                pagination.querySelector('[data-article-page="1"]').disabled = currentPage >= pageCount;
                reset.disabled = !query && !selectedCategory;
            }

            search.addEventListener('input', () => { currentPage = 1; render(); });
            category.addEventListener('change', () => { currentPage = 1; render(); });
            reset.addEventListener('click', () => {
                search.value = '';
                category.value = '';
                currentPage = 1;
                render();
                search.focus();
            });
            pagination.addEventListener('click', event => {
                const button = event.target.closest('[data-article-page]');
                if (!button || button.disabled) return;
                currentPage += Number(button.dataset.articlePage);
                render();
            });
            render();
        })();
    </script>
@endpush