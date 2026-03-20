@extends('layouts.app')

@section('title', 'Eventos | Roig Arena')

@section('page_styles')
    <link rel="stylesheet" href="/css/pages/eventos.css">
@endsection

@section('content')
    <section class="card section-gap">
        <h1 class="title-no-top">Listado de eventos</h1>
        <p class="muted no-margin">
            Esta vista recibe los datos desde <strong>PaginaController::eventosIndex</strong>.
        </p>
    </section>

    <section class="grid grid-gap-bottom">
        @forelse ($eventos as $evento)
                <a href="{{ route('eventos.show', ['evento' => $evento], false) }}" class="card card-link event-card">
                    <img src="{{ $evento->poster_url }}" alt="{{ $evento->nombre }}" class="event-card-image">
                    <h2 class="event-card-title">{{ $evento->nombre }}</h2>
                    {{-- <p class="muted">{{ $evento->descripcion_corta }}</p> --}}
                    <p class="event-meta">
                        <strong>{{ optional($evento->fecha)->format('d/m/Y') }}</strong>
                        ·
                        {{ optional($evento->hora)->format('H:i') }}
                    </p>

                    {{-- <span class="btn">Ir al detalle</span> --}}
                </a>
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
