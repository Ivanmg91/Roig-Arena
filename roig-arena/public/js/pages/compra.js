class SeatMapManager {
    constructor(eventoId) {
        this.eventoId = eventoId;
        this.selectedSeats = new Map(); // Map<seatId, seatData>
        this.data = null;
        this.init();
    }

    async init() {
        try {
            // 1. Cargar datos del evento y asientos
            const response = await fetch(`/api/eventos/${this.eventoId}/`);
            this.data = await response.json();
            console.log('Datos del evento:', this.data);

            // 2. Renderizar el mapa
            this.renderStadium();

            // 3. Configurar event listeners
            this.setupEventListeners();

            // 4. Cargar carrito del localStorage (si existe)
            this.loadCartFromStorage();
        } catch (error) {
            console.error('Error cargando datos del evento:', error);
        }
    }

    renderStadium() {
        const stadiumView = document.getElementById('stadiumView');
        stadiumView.innerHTML = '';

        this.data.data.sectores_disponibles.forEach(sector => {
            const sectorElement = this.createSectorElement(sector);
            stadiumView.appendChild(sectorElement);
        });
    }

    createSectorElement(sector) {
        const sectorDiv = document.createElement('div');
        sectorDiv.className = 'sector-group';
        sectorDiv.style.borderColor = sector.color_hex;

        const title = document.createElement('div');
        title.className = 'sector-title';
        title.textContent = sector.nombre;

        const grid = document.createElement('div');
        grid.className = 'seats-grid';

        // Crear matriz de asientos
        const asientosPorFila = {};

        for (let fila = 1; fila <= sector.cantidad_filas; fila++) {
            asientosPorFila[fila] = [];

            for (let col = 1; col <= sector.cantidad_columnas; col++) {
                asientosPorFila[fila].push({
                    id: `${sector.id}-${fila}-${col}`,
                    fila: fila,
                    columna: col,
                    estado: "disponible",
                    precio: sector.pivot.precio,
                    sector_id: sector.id
                });
            }
        }

        // Renderizar asientos
        Object.keys(asientosPorFila)
            .sort((a, b) => parseInt(a) - parseInt(b))
            .forEach(fila => {
                asientosPorFila[fila].forEach(asiento => {
                    const seatElement = this.createSeatElement(asiento);
                    grid.appendChild(seatElement);
                });
            });

        sectorDiv.appendChild(title);
        sectorDiv.appendChild(grid);

        return sectorDiv;
    }


    createSeatElement(asiento) {
        const seat = document.createElement('div');
        seat.className = `seat seat-${asiento.estado}`;
        seat.textContent = `${asiento.fila}${asiento.columna}`;
        seat.dataset.seatId = asiento.id;
        seat.dataset.sectorId = asiento.sector_id;
        seat.dataset.fila = asiento.fila;
        seat.dataset.columna = asiento.columna;
        seat.dataset.precio = asiento.precio;
        seat.dataset.estado = asiento.estado;

        if (asiento.estado === 'disponible') {
            seat.addEventListener('click', () => this.toggleSeat(asiento));
        }

        return seat;
    }

    toggleSeat(asiento) {
        const seatId = asiento.id;

        if (this.selectedSeats.has(seatId)) {
            // Remover
            this.selectedSeats.delete(seatId);
        } else {
            // Añadir
            this.selectedSeats.set(seatId, asiento);
        }

        // Actualizar UI
        this.updateSeatVisuals();
        this.updateCart();
        this.saveCartToStorage();
    }

    updateSeatVisuals() {
        document.querySelectorAll('.seat').forEach(seat => {
            const seatId = parseInt(seat.dataset.seatId);
            seat.classList.remove('seat-selected');

            if (this.selectedSeats.has(seatId)) {
                seat.classList.add('seat-selected');
            }
        });
    }

    updateCart() {
        const seatCount = this.selectedSeats.size;
        document.getElementById('seatCount').textContent = 
            `${seatCount} asiento${seatCount !== 1 ? 's' : ''}`;

        // Resumen de selección
        this.updateSelectionSummary();

        // Desglose de precios
        this.updatePriceBreakdown();

        // Total
        this.updateTotal();

        // Habilitar/deshabilitar botón
        document.getElementById('confirmBtn').disabled = seatCount === 0;
    }

    updateSelectionSummary() {
        const summary = document.getElementById('selectionSummary');

        if (this.selectedSeats.size === 0) {
            summary.innerHTML = '<p class="empty-state">Selecciona asientos para comenzar</p>';
            return;
        }

        summary.innerHTML = '';
        this.selectedSeats.forEach(asiento => {
            const item = document.createElement('div');
            item.className = 'selected-item';
            item.innerHTML = `
                <span>Fila ${asiento.fila}, Asiento ${asiento.columna}</span>
                <button class="selected-item-remove" data-seat-id="${asiento.id}">
                    ✕
                </button>
            `;
            item.querySelector('.selected-item-remove').addEventListener('click', () => {
                this.toggleSeat(asiento);
            });
            summary.appendChild(item);
        });
    }

    updatePriceBreakdown() {
        const breakdown = document.getElementById('priceBreakdown');
        breakdown.innerHTML = '';

        // Agrupar asientos por sector
        const asientosPorSector = {};
        this.selectedSeats.forEach(asiento => {
            if (!asientosPorSector[asiento.sector_id]) {
                asientosPorSector[asiento.sector_id] = [];
            }
            asientosPorSector[asiento.sector_id].push(asiento);
        });

        // Renderizar líneas de desglose
        Object.entries(asientosPorSector).forEach(([sectorId, asientos]) => {
            const sector = this.data.data.sectores_disponibles.find(s => s.id == sectorId);
            const totalSector = asientos.reduce((sum, a) => sum + parseFloat(a.precio), 0);

            const line = document.createElement('div');
            line.className = 'price-line';
            line.innerHTML = `
                <span>${sector.nombre} (${asientos.length}x)</span>
                <strong>${totalSector.toFixed(2)}€</strong>
            `;
            breakdown.appendChild(line);
        });
    }

    updateTotal() {
        const total = Array.from(this.selectedSeats.values())
            .reduce((sum, asiento) => sum + parseFloat(asiento.precio), 0);

        document.getElementById('totalAmount').textContent = 
            `${total.toFixed(2)}€`;
    }

    saveCartToStorage() {
        const cartData = {
            eventoId: this.eventoId,
            seats: Array.from(this.selectedSeats.values())
        };
        localStorage.setItem('seatmap_cart', JSON.stringify(cartData));
    }

    loadCartFromStorage() {
        const stored = localStorage.getItem('seatmap_cart');
        if (!stored) return;

        try {
            const data = JSON.parse(stored);
            if (data.eventoId !== this.eventoId) {
                localStorage.removeItem('seatmap_cart');
                return;
            }

            data.seats.forEach(asiento => {
                this.selectedSeats.set(asiento.id, asiento);
            });

            this.updateSeatVisuals();
            this.updateCart();
        } catch (error) {
            console.error('Error cargando carrito:', error);
            localStorage.removeItem('seatmap_cart');
        }
    }

    setupEventListeners() {
        const confirmBtn = document.getElementById('confirmBtn');
        confirmBtn.addEventListener('click', () => this.proceedToCheckout());
    }

    proceedToCheckout() {
        const cartData = {
            evento_id: this.eventoId,
            asientos: Array.from(this.selectedSeats.keys())
        };

        // Enviar al backend para confirmar compra
        fetch('/api/compra/confirmar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(cartData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirigir a confirmación de pago
                window.location.href = data.redirect_url;
            } else {
                alert('Error al procesar la compra: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

// Inicializar cuando la página carga
document.addEventListener('DOMContentLoaded', () => {
    const eventoId = document.querySelector('[data-evento-id]').dataset.eventoId;
    new SeatMapManager(eventoId);
});