@extends('layouts.app')

@section('title', 'Comprar Entradas | ' . $evento->nombre . ' | Roig Arena')

@section('page_styles')
    <link rel="stylesheet" href="/css/pages/eventos.css">
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
    <div class="compra-content">
        
        <!-- Columna principal: Selección de entradas -->
        <div class="compra-main">
            <div class="info-message">
                📍 Selecciona el tipo de entrada y la cantidad que deseas comprar. Verás el total en el resumen.
            </div>

            @forelse($sectoresDisponibles as $sector)
                @php
                    $precio = $evento->precioDelSector($sector->id);
                @endphp
                <div class="sector-group">
                    <div class="sector-title">{{ $sector->nombre }}</div>
                    @if($sector->descripcion)
                        <p class="sector-description">{{ $sector->descripcion }}</p>
                    @endif
                    
                    <div class="precio-display">
                        <span class="precio-label">Precio por entrada</span>
                        <span class="precio-value">{{ number_format($precio->precio, 2, ',', '.') }}€</span>
                    </div>

                    <div class="cantidad-selector">
                        <button class="cantidad-btn" onclick="disminuir({{ $sector->id }})">−</button>
                        <div class="cantidad-display" id="cantidad-{{ $sector->id }}">0</div>
                        <button class="cantidad-btn" onclick="aumentar({{ $sector->id }})">+</button>
                    </div>
                </div>
            @empty
                <div class="info-message">
                    ❌ No hay entradas disponibles para este evento en este momento.
                </div>
            @endforelse
        </div>

        <!-- Columna lateral: Resumen de compra -->
        <div class="compra-sidebar">
            <h2 style="margin-top: 0; color: var(--color-accent); font-size: 1.2rem; text-shadow: 0 2px 8px #F5300340;">
                Resumen de compra
            </h2>

            <div id="resumen-items">
                <!-- Se llenará dinámicamente con JavaScript -->
            </div>

            <div class="resumen-total">
                <span>Total</span>
                <span id="total-precio" style="font-size: 1.5rem;">0,00€</span>
            </div>

            <div class="acciones">
                <button class="btn btn-primary btn-full" onclick="confirmarCompra()">
                    Proceder a pago
                </button>
                <a href="{{ route('eventos.show', ['evento' => $evento->id], false) }}" class="btn btn-full" style="background: transparent; border: 1px solid var(--color-accent); color: var(--color-accent); text-align: center;">
                    Atrás
                </a>
            </div>
        </div>
    </div>

    <script>
        // Datos de sectores y precios
        const sectores = {
            @foreach($sectoresDisponibles as $sector)
                @php $precio = $evento->precioDelSector($sector->id); @endphp
                {{ $sector->id }}: { nombre: "{{ $sector->nombre }}", precio: {{ $precio->precio }} },
            @endforeach
        };

        // Estado de cantidades seleccionadas
        const cantidades = {};
        Object.keys(sectores).forEach(id => cantidades[id] = 0);

        function aumentar(sectorId) {
            cantidades[sectorId]++;
            actualizarUI();
        }

        function disminuir(sectorId) {
            if (cantidades[sectorId] > 0) {
                cantidades[sectorId]--;
                actualizarUI();
            }
        }

        function actualizarUI() {
            // Actualizar cantidades visibles
            Object.keys(cantidades).forEach(id => {
                document.getElementById(`cantidad-${id}`).textContent = cantidades[id];
            });

            // Actualizar resumen
            const resumenItems = document.getElementById('resumen-items');
            resumenItems.innerHTML = '';
            let total = 0;

            Object.entries(cantidades).forEach(([id, cantidad]) => {
                if (cantidad > 0) {
                    const sector = sectores[id];
                    const subtotal = cantidad * sector.precio;
                    total += subtotal;

                    const item = document.createElement('div');
                    item.className = 'resumen-item';
                    item.innerHTML = `
                        <span>${sector.nombre} × ${cantidad}</span>
                        <span>${(subtotal).toFixed(2).replace('.', ',')}€</span>
                    `;
                    resumenItems.appendChild(item);
                }
            });

            // Actualizar total
            document.getElementById('total-precio').textContent = total.toFixed(2).replace('.', ',') + '€';
        }

        function confirmarCompra() {
            const totalEntradas = Object.values(cantidades).reduce((a, b) => a + b, 0);
            
            if (totalEntradas === 0) {
                alert('Por favor selecciona al menos una entrada');
                return;
            }

            // Aquí irá la lógica de compra (integración con API o formulario)
            alert('Funcionalidad de pago en desarrollo...\nEntradas seleccionadas: ' + totalEntradas);
        }

        // Inicializar UI
        actualizarUI();
    </script>
@endsection
