@extends('layouts.app')

@section('title', 'Editor de sectores | ' . $evento->nombre)

@section('page_styles')
    <link rel="stylesheet" href="/css/pages/eventos.css">
    <link rel="stylesheet" href="/css/pages/show.css">
@endsection

@section('content')
    <section class="container py-4">
        <div class="mb-4">
            <a href="{{ route('eventos.show', $evento) }}" class="btn btn-alt btn-sm">Volver al evento</a>
        </div>

        <div class="card card--dark p-4">
            <header class="mb-4">
                <h1 class="mb-2">Editor de sectores</h1>
                <p class="mb-0 text-muted">
                    Evento: <strong>{{ $evento->nombre }}</strong> · ID: <strong>{{ $eventoId }}</strong>
                </p>
            </header>

            <div class="row g-4">
                <div class="col-12 col-lg-8">
                    <div class="p-4" style="min-height: 420px; border: 1px dashed rgba(255,255,255,.2); border-radius: 16px; background: rgba(255,255,255,.03);">
                        <p class="mb-2"><strong>Área del mapa</strong></p>
                        <p class="mb-0 text-muted">
                            Aquí irá el SVG del estadio para seleccionar el rectángulo del sector.
                        </p>
                    </div>
                </div>

                <aside class="col-12 col-lg-4">
                    <div class="p-4" style="min-height: 420px; border: 1px solid rgba(255,255,255,.1); border-radius: 16px; background: rgba(255,255,255,.02);">
                        <p class="mb-3"><strong>Sectores iniciales</strong></p>

                        @if($sectoresIniciales->isEmpty())
                            <p class="text-muted mb-0">Este evento todavía no tiene sectores asignados.</p>
                        @else
                            <ul class="list-unstyled mb-0">
                                @foreach($sectoresIniciales as $sector)
                                    <li class="mb-2">
                                        <strong>{{ $sector->nombre }}</strong>
                                        <div class="text-muted small">
                                            Filas: {{ $sector->fila_inicio ?? '—' }} - {{ $sector->fila_fin ?? '—' }} ·
                                            Columnas: {{ $sector->columna_inicio ?? '—' }} - {{ $sector->columna_fin ?? '—' }}
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </aside>
            </div>
        </div>
    </section>
@endsection