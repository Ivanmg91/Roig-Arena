@extends('layouts.app')

@section('title', 'Eventos | Roig Arena')

@section('page_styles')
    <link rel="stylesheet" href="/css/pages/eventos.css">
@endsection

@section('content')
    <div class="eventos-header">
        <h1>Eventos
            @auth
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.eventos.create', [], false) }}" class="btn btn-primary" aria-label="+">+</a>
                @endif
            @endauth
        </h1>
        <p class="muted no-margin">Próximos conciertos y espectáculos en el Roig Arena.</p>
    </div>

    <section class="grid grid-gap-bottom">
        @forelse ($eventos as $evento)
                <article class="event-card-wrapper">
                    <a href="{{ route('eventos.show', ['evento' => $evento], false) }}" class="event-card">
                        <img src="{{ $evento->poster_url }}" alt="{{ $evento->nombre }}" class="event-card-image">
                        <div class="event-card-body">
                            <h2 class="event-card-title">{{ $evento->nombre }}</h2>
                            <p class="event-meta">
                                <strong>{{ $evento->fecha?->format('d/m/Y') }}</strong>
                                @if($evento->hora) · {{ $evento->hora?->format('H:i') }} @endif
                            </p>
                        </div>
                    </a>
                    @auth
                        @if(auth()->user()->isAdmin())
                            <button type="button" class="event-card-trash" aria-label="Eliminar evento">
                                🗑️
                            </button>
                        @endif
                    @endauth
                </article>
        @empty
            <article class="card">
                <p class="no-margin">No hay eventos disponibles.</p>
            </article>
        @endforelse
    </section>

    <section class="card">
        {{ $eventos->links() }}
    </section>
@endsection
