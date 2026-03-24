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
            <h2>{{ $evento->nombre }}</h2>

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
                <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--color-border);">
                    <p class="event-description">{{ $evento->descripcion_corta }}</p>
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
        <section class="card section-gap">
            <div class="section-divider">
                <span>SOBRE EL EVENTO</span>
            </div>
            <p class="event-description">{{ $evento->descripcion_larga }}</p>
        </section>
    @endif

    <!-- Precios y disponibilidad -->
    @if($evento->sectoresDisponibles()->count() > 0)
        <section class="card section-gap">
            <div class="section-divider">
                <span>PRECIOS</span>
            </div>
            <p class="muted" style="margin-top: 0;">Selecciona tu sector y compra tu entrada</p>

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
                            <td style="font-weight: 600; color: var(--color-accent); text-shadow: 0 2px 8px #F5300340;">
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
            <div class="cta-section" style="margin-top: 2rem;">
                <a href="{{ route('compra.show', ['evento' => $evento->id], false) }}" class="btn btn-primary">
                    Comprar Entradas
                </a>
                <a href="{{ route('eventos.index', [], false) }}" class="btn" style="background: transparent; border: 1px solid var(--color-accent); color: var(--color-accent);">
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
