@extends('layouts.app')

@section('title', $evento->nombre . ' | Roig Arena')

@section('page_styles')
    <link rel="stylesheet" href="/css/pages/eventos.css">
    <link rel="stylesheet" href="/css/pages/show.css">
@endsection

@section('content')
    <!-- Imagen hero del evento -->
    <div class="event-hero">
        @if($evento->poster_ancho_url)
            <img src="{{ $evento->poster_ancho_url }}" alt="{{ $evento->nombre }}" class="event-hero-image">
        @else
            <div class="event-hero-image event-no-image">
                Sin imagen disponible
            </div>
        @endif
        <div class="event-hero-overlay"></div>
    </div>

    <!-- Información principal del evento -->
    <div class="event-info-grid">
        <!-- Información del evento -->
        <section class="event-info-section">
            <div class="event-title-editor" data-event-title-editor data-update-url="{{ route('admin.eventos.update', ['id' => $evento->id], false) }}">
                <h2 class="event-title-display" data-event-title-display>{{ $evento->nombre }}</h2>

                @auth
                    @if(auth()->user()->isAdmin())
                        <button type="button" class="event-title-edit-button" data-event-title-toggle aria-label="Editar nombre del evento">
                            ✎
                        </button>

                        <form class="event-title-form" data-event-title-form hidden>
                            @csrf
                            @method('PATCH')
                            <input
                                type="text"
                                name="nombre"
                                value="{{ $evento->nombre }}"
                                maxlength="255"
                                required
                                class="event-title-input"
                                data-event-title-input
                                aria-label="Nombre del evento"
                            >
                        </form>
                    @endif
                @endauth
            </div>

            @if($evento->fecha || $evento->hora)
                <div class="event-meta-item">
                    <span class="event-meta-label">Fecha</span>
                    <span class="event-meta-value">
                        @if($evento->fecha)
                            {{ $evento->fecha->format('d/m/Y') }}
                        @else
                            Por confirmar
                        @endif
                    </span>
                </div>

                <div class="event-meta-item">
                    <span class="event-meta-label">Hora</span>
                    <span class="event-meta-value">
                        @if($evento->hora)
                            {{ $evento->hora->format('H:i') }}
                        @else
                            Por confirmar
                        @endif
                    </span>
                </div>
            @endif

            @if($evento->descripcion_corta)
                <div class="event-description-block" data-description-editor data-update-url="{{ route('admin.eventos.update', ['id' => $evento->id], false) }}">
                    <p class="event-description" data-description-display>{{ $evento->descripcion_corta }}</p>
                    @auth
                        @if(auth()->user()->isAdmin())
                            <button type="button" class="event-title-edit-button" data-description-toggle aria-label="Editar descripción corta del evento">
                                ✎
                            </button>

                            <form class="event-description-form" data-description-form hidden>
                                @csrf
                                @method('PATCH')
                                <input
                                    type="text"
                                    name="descripcion_corta"
                                    value="{{ $evento->descripcion_corta }}"
                                    maxlength="255"
                                    required
                                    class="event-description-input"
                                    data-description-input
                                    aria-label="Descripción corta del evento"
                                >
                            </form>
                        @endif
                    @endauth
                </div>
            @endif
        </section>

        <!-- Artistas -->
        <section class="event-info-section">
            <h2>Artistas</h2>

            @forelse($evento->artistas as $artista)
                <div class="artist-card">
                    <div class="artist-card-header">
                        @if($artista->imagen_url)
                            <img src="{{ $artista->imagen_url }}" alt="{{ $artista->nombre }}" class="artist-image">
                        @endif
                        <div>
                            <p class="artist-name">{{ $artista->nombre }}</p>
                            @if($artista->descripcion)
                                <p class="artist-description">{{ $artista->descripcion }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <p class="muted">No hay artistas asignados a este evento.</p>
            @endforelse
        </section>
    </div>

    <!-- Descripción detallada -->
    @if($evento->descripcion_larga)
            <section class="card section-gap" class="event-description_long-block" data-description_long-editor data-update-url="{{ route('admin.eventos.update', ['id' => $evento->id], false) }}">
                <div class="section-divider">
                    <span>SOBRE EL EVENTO</span>
                </div>
                <p class="event-description_long" data-description_long-display>{{ $evento->descripcion_larga }}</p>
                @auth
                        @if(auth()->user()->isAdmin())
                            <button type="button" class="event-title-edit-button" data-description_long-toggle aria-label="Editar descripción corta del evento">
                                ✎
                            </button>

                            <form class="event-description_long-form" data-description_long-form hidden>
                                @csrf
                                @method('PATCH')
                                <input
                                    type="text"
                                    name="descripcion_larga"
                                    value="{{ $evento->descripcion_larga }}"
                                    maxlength="255"
                                    required
                                    class="event-description_long-input"
                                    data-description_long-input
                                    aria-label="Descripción corta del evento"
                                >
                            </form>
                        @endif
                    @endauth
            </section>
    @endif

    <!-- Precios y disponibilidad -->
    @if($evento->sectoresDisponibles()->count() > 0)
        <section class="card section-gap">
            <div class="section-divider">
                <span>PRECIOS</span>
            </div>
            <p class="muted event-prices-intro">Selecciona tu sector y compra tu entrada</p>

            <table class="pricing-table">
                <thead>
                    <tr>
                        <th>Sector</th>
                        <th>Precio</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($evento->sectoresDisponibles() as $sector)
                        @php
                            $precio = $evento->precioDelSector($sector->id);
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $sector->nombre }}</strong>
                                @if($sector->descripcion)
                                    <br>
                                    <span class="muted">{{ $sector->descripcion }}</span>
                                @endif
                            </td>
                            <td class="price-highlight">
                                {{ number_format($precio->precio, 2, ',', '.') }}€
                            </td>
                            <td>
                                <span class="badge">Disponible</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Botón de compra -->
            <div class="cta-section">
                <a href="{{ route('compra.buy', ['evento' => $evento->id], false) }}" class="btn btn-primary">
                    Comprar Entradas
                </a>
                <a href="{{ route('eventos.index', [], false) }}" class="btn btn-alt">
                    Volver
                </a>
            </div>
        </section>
    @else
        <section class="card section-gap">
            <div class="section-divider">
                <span>ESTADO</span>
            </div>
            <p class="muted">No hay entradas disponibles para este evento en este momento.</p>
            <a href="{{ route('eventos.index', [], false) }}" class="btn">
                Volver al listado
            </a>
        </section>
    @endif

@endsection

@section('page_scripts')
    @auth
        @if(auth()->user()->isAdmin())
            <script src="/js/pages/updateShow.js"></script>
        @endif
    @endauth
@endsection