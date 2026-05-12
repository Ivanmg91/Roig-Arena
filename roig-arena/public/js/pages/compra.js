class SeatMapManager {
    constructor(eventoId) {
        this.eventoId = eventoId;
        this.data = null;
        this.allSeats = new Map();
        this.selectedSeats = new Map();
        this.seatNodeMap = new Map();
        this.priceBySector = new Map();
        this.reservasActivas = [];
        this.paymentTimerInterval = null;

        this.rows = 12;
        this.cols = 20;
        this.viewWidth = 960;
        this.viewHeight = 560;
        this.padLeft = 64;
        this.padTop = 42;
        this.padRight = 26;
        this.padBottom = 26;

        this.gridWidth = this.viewWidth - this.padLeft - this.padRight;
        this.gridHeight = this.viewHeight - this.padTop - this.padBottom;
        this.seatRadius = Math.max(6, Math.min(13, Math.min(this.gridWidth / this.cols, this.gridHeight / this.rows) * 0.28));
        this.xStep = this.cols > 1 ? this.gridWidth / (this.cols - 1) : this.gridWidth;
        this.yStep = this.rows > 1 ? this.gridHeight / (this.rows - 1) : this.gridHeight;

        this.init();
    }

    async init() {
        try {
            await this.loadEventoData();
            await this.loadAllSeats();
            this.computeGridSize();
            this.renderSeatMap();
            this.setupEventListeners();
            this.loadCartFromStorage();
        } catch (error) {
            console.error('Error inicializando SeatMapManager:', error);
            this.showError('No se pudo cargar el mapa de asientos. Intenta recargar la página.');
        }
    }

    async loadEventoData() {
        const response = await fetch(`/api/eventos/${this.eventoId}/`);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        this.data = data;

        const sectores = this.data?.data?.sectores_disponibles ?? [];
        sectores.forEach(sector => {
            this.priceBySector.set(String(sector.id), Number(sector?.pivot?.precio ?? 0));
        });
    }

    async loadAllSeats() {
        const response = await fetch(`/api/eventos/${this.eventoId}/asientos`);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const payload = await response.json();
        const seats = payload?.data?.asientos ?? [];

        seats.forEach(seat => {
            const row = this.parseRowValue(seat.fila);
            const col = Number(seat.numero);
            const sectorId = String(seat.sector_id ?? '');
            this.allSeats.set(String(seat.id), {
                id: String(seat.id),
                fila: seat.fila,
                filaCoord: row,
                numero: col,
                sector_id: sectorId,
                sector_nombre: seat.sector_nombre || '',
                disponible: Boolean(seat.disponible),
                estado: seat.disponible ? 'disponible' : 'ocupado',
                precio: Number(this.priceBySector.get(sectorId) ?? 0),
            });
        });
    }

    computeGridSize() {
        let maxRow = 0;
        let maxCol = 0;

        this.allSeats.forEach(seat => {
            if (Number.isFinite(seat.filaCoord)) {
                maxRow = Math.max(maxRow, seat.filaCoord);
            }
            if (Number.isFinite(seat.numero)) {
                maxCol = Math.max(maxCol, seat.numero);
            }
        });

        if (maxRow > 0) {
            this.rows = maxRow;
        }
        if (maxCol > 0) {
            this.cols = maxCol;
        }

        this.seatRadius = Math.max(6, Math.min(13, Math.min(this.gridWidth / Math.max(this.cols, 1), this.gridHeight / Math.max(this.rows, 1)) * 0.28));
        this.xStep = this.cols > 1 ? this.gridWidth / (this.cols - 1) : this.gridWidth;
        this.yStep = this.rows > 1 ? this.gridHeight / (this.rows - 1) : this.gridHeight;
    }

    renderSeatMap() {
        const svg = document.getElementById('seatMapSvg');
        if (!svg) {
            console.error('No se encontró el elemento SVG del mapa de asientos');
            return;
        }

        svg.innerHTML = '';
        svg.setAttribute('viewBox', `0 0 ${this.viewWidth} ${this.viewHeight}`);

        this.drawBackground(svg);
        this.drawGridLines(svg);
        this.drawSectorBackgrounds(svg);
        this.drawSeatNodes(svg);
    }

    drawBackground(svg) {
        this.createAndAppendSvgNode(svg, 'rect', {
            x: 0,
            y: 0,
            width: this.viewWidth,
            height: this.viewHeight,
            rx: 14,
            class: 'sector-map-bg'
        });

        this.createAndAppendSvgNode(svg, 'rect', {
            x: this.padLeft,
            y: 8,
            width: this.gridWidth,
            height: 20,
            rx: 10,
            class: 'sector-map-stage'
        });

        const stageLabel = this.createSvgNode('text', {
            x: this.padLeft + this.gridWidth / 2,
            y: 23,
            class: 'sector-map-stage-label',
            'text-anchor': 'middle'
        });
        stageLabel.textContent = 'ESCENARIO';
        svg.appendChild(stageLabel);
    }

    drawGridLines(svg) {
        for (let row = 1; row <= this.rows; row++) {
            const y = this.padTop + (row - 1) * this.yStep;
            const rowLabel = this.createSvgNode('text', {
                x: 34,
                y: y + 4,
                class: 'sector-map-axis-label',
                'text-anchor': 'middle'
            });
            rowLabel.textContent = String(row);
            svg.appendChild(rowLabel);

            this.createAndAppendSvgNode(svg, 'line', {
                x1: this.padLeft,
                y1: y,
                x2: this.padLeft + this.gridWidth,
                y2: y,
                class: 'sector-map-grid-line'
            });
        }

        for (let col = 1; col <= this.cols; col++) {
            const x = this.padLeft + (col - 1) * this.xStep;
            const colLabel = this.createSvgNode('text', {
                x,
                y: this.viewHeight - 6,
                class: 'sector-map-axis-label',
                'text-anchor': 'middle'
            });
            colLabel.textContent = String(col);
            svg.appendChild(colLabel);

            this.createAndAppendSvgNode(svg, 'line', {
                x1: x,
                y1: this.padTop,
                x2: x,
                y2: this.padTop + this.gridHeight,
                class: 'sector-map-grid-line'
            });
        }
    }

    drawSectorBackgrounds(svg) {
        const sectores = this.data?.data?.sectores_disponibles ?? [];
        sectores.forEach(sector => {
            const bounds = this.calculateSectorBounds(sector);
            if (!bounds) {
                return;
            }

            const x1 = this.padLeft + (bounds.colInicio - 1) * this.xStep;
            const x2 = this.padLeft + (bounds.colFin - 1) * this.xStep;
            const y1 = this.padTop + (bounds.filaInicio - 1) * this.yStep;
            const y2 = this.padTop + (bounds.filaFin - 1) * this.yStep;

            const zonePadding = this.seatRadius + 3;
            const rectX = x1 - zonePadding;
            const rectY = y1 - zonePadding;
            const rectWidth = (x2 - x1) + zonePadding * 2;
            const rectHeight = (y2 - y1) + zonePadding * 2;

            this.createAndAppendSvgNode(svg, 'rect', {
                x: rectX,
                y: rectY,
                width: rectWidth,
                height: rectHeight,
                rx: 8,
                class: 'sector-zone-background',
                fill: sector.color_hex || '#5ba8ff'
            });

            const label = this.createSvgNode('text', {
                x: rectX + 8,
                y: rectY + 14,
                class: 'sector-zone-label',
                'text-anchor': 'start'
            });
            label.textContent = sector.nombre || 'Sector';
            svg.appendChild(label);
        });
    }

    drawSeatNodes(svg) {
        this.seatNodeMap.clear();
        this.allSeats.forEach(asiento => {
            if (!Number.isFinite(asiento.filaCoord) || !Number.isFinite(asiento.numero)) {
                return;
            }

            const x = this.padLeft + (asiento.numero - 1) * this.xStep;
            const y = this.padTop + (asiento.filaCoord - 1) * this.yStep;
            const seatGroup = this.createSvgNode('g', {
                class: `seat-node seat-${asiento.estado}`,
                'data-seat-id': asiento.id,
                'data-sector-id': asiento.sector_id,
                'data-fila': asiento.fila,
                'data-numero': asiento.numero,
                tabindex: asiento.disponible ? '0' : '-1',
                'aria-label': `Fila ${asiento.fila}, Asiento ${asiento.numero}`
            });

            const seatCircle = this.createSvgNode('circle', {
                cx: x,
                cy: y,
                r: this.seatRadius
            });

            const title = this.createSvgNode('title', {});
            title.textContent = `${asiento.sector_nombre || 'Sector'} · Fila ${asiento.fila} · Asiento ${asiento.numero}`;
            seatGroup.appendChild(title);
            seatGroup.appendChild(seatCircle);

            if (asiento.disponible) {
                seatGroup.addEventListener('click', () => this.toggleSeat(asiento));
                seatGroup.addEventListener('keydown', event => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        this.toggleSeat(asiento);
                    }
                });
            }

            svg.appendChild(seatGroup);
            this.seatNodeMap.set(asiento.id, seatGroup);
        });

        this.updateSeatVisuals();
    }

    calculateSectorBounds(sector) {
        const filaInicioRaw = this.parseRowValue(sector.fila_inicio);
        const filaFinRaw = this.parseRowValue(sector.fila_fin);
        const colInicioRaw = Number(sector.columna_inicio);
        const colFinRaw = Number(sector.columna_fin);

        if (!Number.isFinite(filaInicioRaw) || !Number.isFinite(filaFinRaw) || !Number.isFinite(colInicioRaw) || !Number.isFinite(colFinRaw)) {
            return null;
        }

        const filaInicio = Math.max(1, Math.min(this.rows, Math.min(filaInicioRaw, filaFinRaw)));
        const filaFin = Math.max(1, Math.min(this.rows, Math.max(filaInicioRaw, filaFinRaw)));
        const colInicio = Math.max(1, Math.min(this.cols, Math.min(colInicioRaw, colFinRaw)));
        const colFin = Math.max(1, Math.min(this.cols, Math.max(colInicioRaw, colFinRaw)));

        if (filaInicio > filaFin || colInicio > colFin) {
            return null;
        }

        return { filaInicio, filaFin, colInicio, colFin };
    }

    parseRowValue(fila) {
        if (fila === null || fila === undefined) {
            return null;
        }

        if (typeof fila === 'number' && Number.isFinite(fila)) {
            return fila;
        }

        const parsed = String(fila).trim();
        if (/^\d+$/.test(parsed)) {
            return Number(parsed);
        }

        if (/^[A-Za-z]$/.test(parsed)) {
            return parsed.toUpperCase().charCodeAt(0) - 64;
        }

        const numeric = Number(parsed);
        return Number.isFinite(numeric) ? numeric : null;
    }

    toggleSeat(asiento) {
        const seatId = String(asiento.id);
        if (!asiento.disponible) {
            return;
        }

        if (this.selectedSeats.has(seatId)) {
            this.selectedSeats.delete(seatId);
        } else {
            this.selectedSeats.set(seatId, {
                ...asiento,
                id: seatId
            });
        }

        this.updateSeatVisuals();
        this.updateCart();
        this.saveCartToStorage();
    }

    updateSeatVisuals() {
        this.seatNodeMap.forEach((node, seatId) => {
            node.classList.toggle('seat-selected', this.selectedSeats.has(seatId));
        });
    }

    updateCart() {
        const seatCount = this.selectedSeats.size;
        document.getElementById('seatCount').textContent = `${seatCount} asiento${seatCount !== 1 ? 's' : ''}`;
        this.updateSelectionSummary();
        this.updatePriceBreakdown();
        this.updateTotal();
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
                <span>${asiento.sector_nombre || 'Sector'} · Fila ${asiento.fila} · Asiento ${asiento.numero}</span>
                <button class="selected-item-remove" data-seat-id="${asiento.id}" aria-label="Quitar asiento">✕</button>
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

        const seatsBySector = {};
        this.selectedSeats.forEach(asiento => {
            if (!seatsBySector[asiento.sector_id]) {
                seatsBySector[asiento.sector_id] = [];
            }
            seatsBySector[asiento.sector_id].push(asiento);
        });

        Object.entries(seatsBySector).forEach(([sectorId, seats]) => {
            const price = Number(this.priceBySector.get(sectorId) ?? 0);
            const subtotal = seats.length * price;
            const sectorName = seats[0]?.sector_nombre || 'Sector';

            const line = document.createElement('div');
            line.className = 'price-line';
            line.innerHTML = `<span>${sectorName} (${seats.length}x)</span><strong>${subtotal.toFixed(2)}€</strong>`;
            breakdown.appendChild(line);
        });
    }

    updateTotal() {
        let total = 0;
        this.selectedSeats.forEach(asiento => {
            total += Number(this.priceBySector.get(asiento.sector_id) ?? 0);
        });
        document.getElementById('totalAmount').textContent = `${total.toFixed(2)}€`;
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
        if (!stored) {
            return;
        }

        try {
            const parsed = JSON.parse(stored);
            if (parsed.eventoId !== this.eventoId) {
                localStorage.removeItem('seatmap_cart');
                return;
            }

            parsed.seats?.forEach(asiento => {
                const seatId = String(asiento.id);
                if (this.allSeats.has(seatId)) {
                    const existing = this.allSeats.get(seatId);
                    if (existing && existing.disponible) {
                        this.selectedSeats.set(seatId, {
                            ...existing,
                            precio: Number(existing.precio || 0)
                        });
                    }
                }
            });

            this.updateSeatVisuals();
            this.updateCart();
        } catch (error) {
            console.error('Error cargando carrito desde localStorage:', error);
            localStorage.removeItem('seatmap_cart');
        }
    }

    setupEventListeners() {
        const confirmBtn = document.getElementById('confirmBtn');
        const payBtn = document.getElementById('payBtn');
        const closePaymentModal = document.getElementById('closePaymentModal');
        const paymentModal = document.getElementById('paymentModal');

        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => this.proceedToCheckout());
        }

        if (payBtn) {
            payBtn.addEventListener('click', () => this.confirmPayment());
        }

        if (closePaymentModal) {
            closePaymentModal.addEventListener('click', () => this.closePaymentModal());
        }

        if (paymentModal) {
            paymentModal.addEventListener('click', e => {
                if (e.target === e.currentTarget) {
                    this.closePaymentModal();
                }
            });
        }
    }

    async proceedToCheckout() {
        if (this.selectedSeats.size === 0) {
            alert('Selecciona al menos un asiento para continuar.');
            return;
        }

        const token = localStorage.getItem('sanctum_token');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const headers = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            Authorization: token ? `Bearer ${token}` : ''
        };

        const asientos = Array.from(this.selectedSeats.values()).map(asiento => ({
            evento_id: Number(this.eventoId),
            asiento_id: Number(asiento.id)
        }));

        this.reservasActivas = [];

        try {
            for (const asiento of asientos) {
                const response = await fetch('/api/reservas', {
                    method: 'POST',
                    headers,
                    credentials: 'include',
                    body: JSON.stringify(asiento)
                });

                if (response.status === 401 || response.status === 302) {
                    window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`;
                    return;
                }

                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    const text = await response.text();
                    throw new Error(`Respuesta del servidor inesperada (HTTP ${response.status}):\n${text.slice(0, 200)}`);
                }

                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || `HTTP ${response.status}`);
                }

                this.reservasActivas.push(data.data);
            }

            if (this.reservasActivas.length > 0) {
                this.openPaymentModal();
            }
        } catch (error) {
            console.error('Error al reservar los asientos:', error);
            alert('Error al reservar los asientos: ' + error.message);
        }
    }

    openPaymentModal() {
        const modal = document.getElementById('paymentModal');
        const summary = document.getElementById('paymentSummary');
        const totalEl = document.getElementById('paymentTotal');
        const payBtn = document.getElementById('payBtn');

        if (!modal || !summary || !totalEl || !payBtn) {
            return;
        }

        payBtn.disabled = false;
        payBtn.textContent = 'Pagar ahora';

        summary.innerHTML = '';
        let total = 0;

        Array.from(this.selectedSeats.values()).forEach(seat => {
            const precio = Number(this.priceBySector.get(seat.sector_id) ?? 0);
            total += precio;
            const row = document.createElement('div');
            row.className = 'payment-seat-row';
            row.innerHTML = `<span>Fila ${seat.fila} · Asiento ${seat.numero} · ${seat.sector_nombre || ''}</span><span>${precio.toFixed(2)}€</span>`;
            summary.appendChild(row);
        });

        totalEl.textContent = total.toFixed(2).replace('.', ',') + '€';

        const primeraReserva = this.reservasActivas[0];
        const expira = primeraReserva?.reservado_hasta
            ? new Date(primeraReserva.reservado_hasta)
            : new Date(Date.now() + 15 * 60 * 1000);

        this.startCountdown(expira);

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    startCountdown(expiresAt) {
        clearInterval(this.paymentTimerInterval);
        const el = document.getElementById('paymentCountdown');
        if (!el) {
            return;
        }

        const tick = () => {
            const remaining = Math.max(0, expiresAt - Date.now());
            const mins = Math.floor(remaining / 60000);
            const secs = Math.floor((remaining % 60000) / 1000);
            el.textContent = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;

            if (remaining <= 0) {
                clearInterval(this.paymentTimerInterval);
                el.textContent = '00:00';
                const payBtn = document.getElementById('payBtn');
                if (payBtn) {
                    payBtn.disabled = true;
                }
                alert('El tiempo de reserva ha expirado. Por favor, vuelve a seleccionar tus asientos.');
                this.handleReservationExpiration();
            }
        };

        tick();
        this.paymentTimerInterval = setInterval(tick, 1000);
    }

    async cancelarReservasActivas() {
        if (!Array.isArray(this.reservasActivas) || this.reservasActivas.length === 0) {
            return;
        }

        const token = localStorage.getItem('sanctum_token');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const headers = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            Authorization: token ? `Bearer ${token}` : ''
        };

        const requests = this.reservasActivas
            .filter(reserva => Number.isFinite(Number(reserva?.id)))
            .map(reserva => fetch(`/api/reservas/${reserva.id}`, {
                method: 'DELETE',
                headers,
                credentials: 'include'
            }));

        await Promise.allSettled(requests);
        this.reservasActivas = [];
    }

    async handleReservationExpiration() {
        try {
            await this.cancelarReservasActivas();
        } catch (error) {
            console.error('Error cancelando reservas expiradas:', error);
        }

        this.selectedSeats.clear();
        localStorage.removeItem('seatmap_cart');
        this.updateSeatVisuals();
        this.updateCart();
        this.closePaymentModal();
        this.renderSeatMap();
    }

    closePaymentModal() {
        clearInterval(this.paymentTimerInterval);
        const modal = document.getElementById('paymentModal');
        if (modal) {
            modal.style.display = 'none';
        }
        document.body.style.overflow = '';
    }

    async confirmPayment() {
        const payBtn = document.getElementById('payBtn');
        if (payBtn) {
            payBtn.disabled = true;
            payBtn.textContent = 'Procesando...';
        }

        const token = localStorage.getItem('sanctum_token');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const headers = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            Authorization: token ? `Bearer ${token}` : ''
        };

        try {
            const response = await fetch('/api/compras/confirmar', {
                method: 'POST',
                headers,
                credentials: 'include',
                body: JSON.stringify({ metodo_pago: 'tarjeta' })
            });

            if (response.status === 401 || response.status === 302) {
                window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`;
                return;
            }

            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                const text = await response.text();
                throw new Error(`Respuesta del servidor inesperada (HTTP ${response.status}):\n${text.slice(0, 200)}`);
            }

            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || `HTTP ${response.status}`);
            }

            clearInterval(this.paymentTimerInterval);
            localStorage.removeItem('seatmap_cart');
            this.selectedSeats.clear();
            this.reservasActivas = [];
            this.updateSeatVisuals();
            this.updateCart();
            this.closePaymentModal();

            alert(`¡Compra confirmada! Total: ${Number(data.total || 0).toFixed(2)}€`);
            window.location.href = '/eventos';
        } catch (error) {
            console.error('Error al confirmar pago:', error);
            alert('Error al procesar el pago: ' + error.message);
            if (payBtn) {
                payBtn.disabled = false;
                payBtn.textContent = 'Pagar ahora';
            }
        }
    }

    createSvgNode(tag, attrs = {}) {
        const node = document.createElementNS('http://www.w3.org/2000/svg', tag);
        Object.entries(attrs).forEach(([key, value]) => {
            node.setAttribute(key, String(value));
        });
        return node;
    }

    createAndAppendSvgNode(parent, tag, attrs = {}) {
        const node = this.createSvgNode(tag, attrs);
        parent.appendChild(node);
        return node;
    }
}

// Inicializar cuando la página carga
window.addEventListener('DOMContentLoaded', () => {
    const eventoId = document.querySelector('[data-evento-id]')?.dataset.eventoId;
    if (eventoId) {
        window.seatMapManager = new SeatMapManager(eventoId);
    }
});
