@extends('layouts.app')

@section('title', 'Comprar Entradas | ' . $evento->nombre . ' | Roig Arena')

@section('page_styles')
    <link rel="stylesheet" href="/css/pages/compra.css">
@endsection

@section('content')
    <!-- Encabezado de compra -->
    <div class="compra-header">
        <h1>{{ $evento->nombre }}</h1>
        <p>
            @if($evento->fecha)
                {{ $evento->fecha->format('d/m/Y') }}
                @if($evento->hora)
                    · {{ $evento->hora->format('H:i') }}
                @endif
            @endif
        </p>
    </div>

    <!-- Contenedor principal -->
    <div class="seatmap-container">
        <!-- LADO IZQUIERDO: MAPA DE ASIENTOS -->
        <div class="seatmap-area">
            <h2>Selecciona tus asientos</h2>
            
            <!-- Leyenda -->
            <div class="legend">
                <span class="legend-item">
                    <div class="seat seat-available"></div> Disponible
                </span>
                <span class="legend-item">
                    <div class="seat seat-reserved"></div> Reservado
                </span>
                <span class="legend-item">
                    <div class="seat seat-selected"></div> Seleccionado
                </span>
            </div>

            <!-- Vista del Estadio por Sector -->
            <div class="stadium-layout">
                <div class="stadium-view" id="stadiumView">
                    <!-- JavaScript generará los sectores aquí -->
                </div>
            </div>
            <div id="sectorSeats" class="sector-seats"></div>
        </div>

        <!-- LADO DERECHO: CARRITO FLOTANTE -->
        <aside class="checkout-sidebar">
            <div class="checkout-header">
                <h3>Tu Carrito</h3>
                <span class="seat-count" id="seatCount">0 asientos</span>
            </div>

            <div class="checkout-content">
                <!-- Resumen de selección -->
                <div class="selection-summary" id="selectionSummary">
                    <p class="empty-state">Selecciona asientos para comenzar</p>
                </div>

                <!-- Desglose de precios -->
                <div class="price-breakdown" id="priceBreakdown">
                    <!-- Se generará dinámicamente -->
                </div>

                <!-- Total -->
                <div class="total-section">
                    <p class="total-label">Total a pagar:</p>
                    <p class="total-amount" id="totalAmount">0,00€</p>
                </div>

                <!-- Botones de acción -->
                <div class="checkout-actions">
                    <button class="btn btn-primary" id="confirmBtn" disabled>
                        Confirmar Compra
                    </button>
                    <a href="{{ route('eventos.show', ['evento' => $evento->id], false) }}" class="btn btn-secondary">
                        Volver
                    </a>
                </div>
            </div>
        </aside>
    </div>
    
    <div id="eventoData" data-evento-id="{{ $evento->id }}"></div>
    <script src="/js/pages/compra.js"></script> 
@endsection
