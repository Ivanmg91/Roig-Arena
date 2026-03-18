@extends('layouts.app')

@section('title', 'Eventos | Roig Arena')

@section('content')
    <section class="card section-gap">
        <h1 class="title-no-top">Listado de eventos</h1>
        <p class="muted no-margin">
            Esta vista recibe los datos desde <strong>PaginaController::eventosIndex</strong>.
        </p>
    </section>

    <section class="grid grid-gap-bottom">
        @forelse ($eventos as $evento)
            <article class="card">
                <h2 class="event-card-title">{{ $evento->nombre }}</h2>
                <p class="muted">{{ $evento->descripcion_corta }}</p>
                <p class="event-meta">
                    <strong>{{ optional($evento->fecha)->format('d/m/Y') }}</strong>
                    ·
                    {{ optional($evento->hora)->format('H:i') }}
                </p>

                <a class="btn" href="{{ route('eventos.show', ['evento' => $evento], false) }}">Ir al detalle</a>
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