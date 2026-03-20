@extends('layouts.app')

@section('title', $evento->nombre . ' | Roig Arena')

@section('page_styles')
    <link rel="stylesheet" href="/css/pages/eventos.css">
@endsection

@section('content')
    <article class="card section-gap">
        <span class="badge">Detalle de evento</span>
        <h1 class="event-detail-title">{{ $evento->nombre }}</h1>

        <p class="muted event-detail-description">
            {{ $evento->descripcion_larga ?: $evento->descripcion_corta }}
        </p>

        <p class="event-meta">
            Fecha: <strong>{{ optional($evento->fecha)->format('d/m/Y') }}</strong>
            · Hora: <strong>{{ optional($evento->hora)->format('H:i') }}</strong>
        </p>
    </article>

    <section class="card section-gap">
        <h2 class="title-no-top">Precios por sector</h2>

        @if ($evento->precios->isEmpty())
            <p class="muted no-margin">Este evento aun no tiene precios configurados.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>Sector</th>
                        <th>Precio</th>
                        <th>Disponible</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($evento->precios as $precio)
                        <tr>
                            <td>{{ optional($precio->sector)->nombre ?? 'Sin sector' }}</td>
                            <td>{{ number_format((float) $precio->precio, 2, ',', '.') }} EUR</td>
                            <td>{{ $precio->disponible ? 'Si' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    <a class="btn" href="{{ route('eventos.index', [], false) }}">Volver al listado</a>
@endsection
