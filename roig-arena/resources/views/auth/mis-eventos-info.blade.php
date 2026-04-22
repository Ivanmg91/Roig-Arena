@extends('layouts.app')

@section('title', 'Mis Eventos Detallados | Roig Arena')

@section('page_styles')
    <link rel="stylesheet" href="/css/pages/eventos.css">
@endsection

@section('content')
    <div class="eventos-header">
        <h1>Mis Eventos Detallados</h1>
        <p class="muted no-margin">Aquí puedes ver tus eventos y los asientos reservados para cada uno.</p>
    </div>

    <section class="grid grid-gap-bottom">
        @forelse ($miseventos as $evento)
            <article class="card">
                <div class="event-info-grid" style="margin-bottom: 0;">
                    <section class="event-info-section">
                        <h2>{{ $evento->nombre }}</h2>

                        <div class="event-meta-item">
                            <span class="event-meta-label">Fecha</span>
                            <span class="event-meta-value">
                                {{ $evento->fecha ? $evento->fecha->format('d/m/Y') : 'Por confirmar' }}
                            </span>
                        </div>

                        <div class="event-meta-item">
                            <span class="event-meta-label">Hora</span>
                            <span class="event-meta-value">
                                {{ $evento->hora ? $evento->hora->format('H:i') : 'Por confirmar' }}
                            </span>
                        </div>

                        @if($evento->artistas->isNotEmpty())
                            <div class="event-meta-item">
                                <span class="event-meta-label">Artistas</span>
                                <span class="event-meta-value">{{ $evento->artistas->pluck('nombre')->join(', ') }}</span>
                            </div>
                        @endif
                    </section>

                    <section class="event-info-section">
                        @if($evento->poster_url)
                            <img src="{{ $evento->poster_url }}" alt="{{ $evento->nombre }}" class="event-hero-image" style="max-height: 240px; object-fit: cover; border-radius: 16px;">
                        @else
                            <div class="event-no-image" style="min-height: 240px; border-radius: 16px; display: grid; place-items: center;">
                                Sin imagen disponible
                            </div>
                        @endif
                    </section>
                </div>

                <section class="event-info-section" style="margin-top: 1.5rem;">
                    <h3>Asientos reservados</h3>

                    @if($evento->entradas->isNotEmpty())
                        <div style="display: grid; gap: .75rem;">
                            @foreach($evento->entradas as $entrada)
                                @php
                                    $asiento = $entrada->asiento;
                                    $nombreAsiento = $asiento && $asiento->sector
                                        ? $asiento->sector->nombre . ' - Fila ' . $asiento->fila . ' - Asiento ' . $asiento->numero
                                        : 'Asiento no disponible';
                                @endphp

                                <article class="card" style="margin: 0; padding: 1rem;">
                                    <strong>{{ $nombreAsiento }}</strong>
                                    <p class="muted no-margin">Entrada #{{ $entrada->id }} · Código QR: {{ $entrada->codigo_qr }}</p>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <p class="muted no-margin">No tienes asientos reservados para este evento.</p>
                    @endif
                </section>
            </article>
        @empty
            <article class="card">
                <p class="no-margin">No hay eventos disponibles.</p>
            </article>
        @endforelse
    </section>
@endsection
