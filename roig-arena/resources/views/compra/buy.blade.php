@extends('layouts.app')

@section('title', 'Comprar Entradas | ' . $evento->nombre . ' | Roig Arena')

@section('page_styles')
    <link rel="stylesheet" href="/css/pages/compra.css">
    <link rel="stylesheet" href="/css/pages/stadium.css">
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

    {{-- Modal de pago --}}
    <div id="paymentModal" class="payment-modal-overlay" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="paymentModalTitle">
        <div class="payment-modal">
            {{-- Cabecera --}}
            <div class="payment-modal-header">
                <h2 id="paymentModalTitle">Simulación de Pago</h2>
                <button id="closePaymentModal" class="payment-modal-close" aria-label="Cerrar">&times;</button>
            </div>

            {{-- Temporizador reserva --}}
            <div class="payment-timer">
                <span>Tus asientos están reservados durante: </span>
                <strong id="paymentCountdown">15:00</strong>
            </div>

            {{-- Resumen de asientos (rellenado por Js) --}}
            <div id="paymentSummary" class="payment-summary"></div>

            {{-- Formulario simulado de pago (no funcional) --}}
            <div class="payment-form">
                <h3>Datos de tarjeta</h3>
                <div class="payment-field">
                    <label for="cardNumber">Número de tarjeta</label>
                    <input type="text" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19">
                </div>
                <div class="payment-field-row">
                    <div class="payment-field">
                        <label for="cardExpiry">Caducidad</label>
                        <input type="text" id="cardExpiry" placeholder="MM/AA" maxlength="5">
                    </div>
                    <div class="payment-field">
                        <label for="cardCvv">CVV</label>
                        <input type="text" id="cardCvv" placeholder="123" maxlength="3">
                    </div>
                </div>
                <div class="payment-field">
                    <label for="cardName">Titular</label>
                    <input type="text" id="cardName" placeholder="Nombre en la tarjeta">
                </div>
            </div>

            {{-- Total y botón de pago --}}
            <div class="payment-modal-footer">
                <p class="payment-total">Total: <strong id="paymentTotal">0,00€</strong></p>
                <button id="payBtn" class="btn btn-primary payment-pay-btn">Pagar ahora</button>
            </div>
        </div>
    </div>
    
    <script src="/js/pages/compra.js"></script>
@endsection
