class SeatMapManager {
    constructor(eventoId) {
        this.eventoId = eventoId;
        this.selectedSeats = new Map(); // Map<seatId, seatData>
        this.seatsCacheBySector = new Map(); // Map<sectorId, apiSeat[]>
        this.activeSectorRequestId = 0;
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

        const sectores = this.data?.data?.sectores_disponibles ?? [];
        if (sectores.length === 0) {
            const emptyState = document.createElement('p');
            emptyState.className = 'stadium-empty';
            emptyState.textContent = 'No hay sectores disponibles para este evento';
            stadiumView.appendChild(emptyState);
            return;
        }

        const svgNS = 'http://www.w3.org/2000/svg';
        const rings = this.distributeSectorsInRings(sectores);

        const canvas = document.createElement('div');
        canvas.className = 'stadium-canvas';

        const svg = document.createElementNS(svgNS, 'svg');
        svg.classList.add('stadium-svg');
        svg.setAttribute('viewBox', '0 0 1000 650');
        svg.setAttribute('role', 'img');
        svg.setAttribute('aria-label', 'Mapa de sectores del estadio');

        const cx = 500;
        const cy = 320;

        const shell = document.createElementNS(svgNS, 'ellipse');
        shell.classList.add('stadium-shell');
        shell.setAttribute('cx', String(cx));
        shell.setAttribute('cy', String(cy));
        shell.setAttribute('rx', '450');
        shell.setAttribute('ry', '265');
        svg.appendChild(shell);

        const floor = document.createElementNS(svgNS, 'ellipse');
        floor.classList.add('stadium-floor');
        floor.setAttribute('cx', String(cx));
        floor.setAttribute('cy', String(cy));
        floor.setAttribute('rx', '168');
        floor.setAttribute('ry', '100');
        svg.appendChild(floor);

        const stage = document.createElementNS(svgNS, 'rect');
        stage.classList.add('stadium-stage');
        stage.setAttribute('x', '388');
        stage.setAttribute('y', '500');
        stage.setAttribute('width', '224');
        stage.setAttribute('height', '74');
        stage.setAttribute('rx', '18');
        svg.appendChild(stage);

        const stageLabel = document.createElementNS(svgNS, 'text');
        stageLabel.classList.add('stadium-stage-label');
        stageLabel.setAttribute('x', '500');
        stageLabel.setAttribute('y', '544');
        stageLabel.textContent = 'ESCENARIO';
        svg.appendChild(stageLabel);

        const sectorShapeMap = new Map();
        const sectorChipMap = new Map();

        const setActiveSector = sector => {
            const targetId = String(sector.id);

            sectorShapeMap.forEach((shape, sectorId) => {
                shape.classList.toggle('active', sectorId === targetId);
            });

            sectorChipMap.forEach((chip, sectorId) => {
                chip.classList.toggle('active', sectorId === targetId);
            });

            this.renderSectorSeats(sector);
        };

        const ringCount = rings.length;
        const ringStart = 160;
        const ringEnd = 380;
        const ringSpan = ringEnd - ringStart;
        const baseOuterRx = 430;
        const baseOuterRy = 255;
        const minInnerRx = 170;
        const minInnerRy = 98;
        const gapX = 10;
        const gapY = 8;

        const ringThicknessX = (baseOuterRx - minInnerRx - (ringCount - 1) * gapX) / ringCount;
        const ringThicknessY = (baseOuterRy - minInnerRy - (ringCount - 1) * gapY) / ringCount;

        rings.forEach((ringSectors, ringIndex) => {
            if (ringSectors.length === 0) {
                return;
            }

            const outerRx = baseOuterRx - ringIndex * (ringThicknessX + gapX);
            const outerRy = baseOuterRy - ringIndex * (ringThicknessY + gapY);
            const innerRx = outerRx - ringThicknessX;
            const innerRy = outerRy - ringThicknessY;

            const stepAngle = ringSpan / ringSectors.length;
            const maxPadding = Math.max(0, stepAngle - 0.9);
            const anglePadding = Math.min(stepAngle * 0.22, 2.4, maxPadding);

            ringSectors.forEach((sector, index) => {
                const startAngle = ringStart + stepAngle * index + anglePadding * 0.5;
                const endAngle = ringStart + stepAngle * (index + 1) - anglePadding * 0.5;

                const path = document.createElementNS(svgNS, 'path');
                path.classList.add('stadium-sector-shape');
                path.dataset.sectorId = String(sector.id);
                path.style.setProperty('--sector-color', sector.color_hex || '#f53003');
                path.setAttribute('d', this.buildSectorPath(
                    cx,
                    cy,
                    outerRx,
                    outerRy,
                    innerRx,
                    innerRy,
                    startAngle,
                    endAngle
                ));
                path.setAttribute('tabindex', '0');

                const sectorPrice = Number(sector?.pivot?.precio ?? 0);
                const title = document.createElementNS(svgNS, 'title');
                title.textContent = `${sector.nombre} - ${sectorPrice.toFixed(2)} EUR`;
                path.appendChild(title);

                path.addEventListener('click', () => setActiveSector(sector));
                path.addEventListener('keydown', event => {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        setActiveSector(sector);
                    }
                });

                svg.appendChild(path);
                sectorShapeMap.set(String(sector.id), path);

                if (stepAngle >= 11) {
                    const midAngle = (startAngle + endAngle) * 0.5;
                    const labelPoint = this.getEllipsePoint(
                        cx,
                        cy,
                        (outerRx + innerRx) * 0.5,
                        (outerRy + innerRy) * 0.5,
                        midAngle
                    );

                    const label = document.createElementNS(svgNS, 'text');
                    label.classList.add('stadium-sector-label-text');
                    label.setAttribute('x', labelPoint.x.toFixed(2));
                    label.setAttribute('y', labelPoint.y.toFixed(2));
                    label.textContent = sector.nombre.length > 9
                        ? `${sector.nombre.slice(0, 9)}...`
                        : sector.nombre;
                    svg.appendChild(label);
                }
            });
        });

        canvas.appendChild(svg);
        stadiumView.appendChild(canvas);

        const sectorList = document.createElement('div');
        sectorList.className = 'stadium-sector-list';

        sectores.forEach(sector => {
            const sectorId = String(sector.id);
            const sectorPrice = Number(sector?.pivot?.precio ?? 0);

            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'stadium-sector-chip';
            chip.dataset.sectorId = sectorId;
            chip.style.setProperty('--sector-color', sector.color_hex || '#f53003');
            chip.innerHTML = `
                <span class="stadium-sector-chip-name">${sector.nombre}</span>
                <span class="stadium-sector-chip-price">${sectorPrice.toFixed(0)} EUR</span>
            `;
            chip.addEventListener('click', () => setActiveSector(sector));

            sectorChipMap.set(sectorId, chip);
            sectorList.appendChild(chip);
        });

        stadiumView.appendChild(sectorList);

        setActiveSector(sectores[0]);
    }

    distributeSectorsInRings(sectores) {
        const total = sectores.length;
        const ringCount = Math.max(2, Math.min(5, Math.ceil(total / 12)));
        const rings = [];

        let offset = 0;
        for (let ringIndex = 0; ringIndex < ringCount; ringIndex++) {
            const remaining = total - offset;
            const ringsLeft = ringCount - ringIndex;
            const sectorsInRing = Math.ceil(remaining / ringsLeft);

            rings.push(sectores.slice(offset, offset + sectorsInRing));
            offset += sectorsInRing;
        }

        return rings.filter(ring => ring.length > 0);
    }

    getEllipsePoint(cx, cy, rx, ry, angleDeg) {
        const radians = (angleDeg * Math.PI) / 180;

        return {
            x: cx + rx * Math.cos(radians),
            y: cy + ry * Math.sin(radians)
        };
    }

    buildSectorPath(cx, cy, outerRx, outerRy, innerRx, innerRy, startAngle, endAngle) {
        const startOuter = this.getEllipsePoint(cx, cy, outerRx, outerRy, startAngle);
        const endOuter = this.getEllipsePoint(cx, cy, outerRx, outerRy, endAngle);
        const endInner = this.getEllipsePoint(cx, cy, innerRx, innerRy, endAngle);
        const startInner = this.getEllipsePoint(cx, cy, innerRx, innerRy, startAngle);
        const largeArcFlag = endAngle - startAngle > 180 ? 1 : 0;

        return [
            `M ${startOuter.x.toFixed(2)} ${startOuter.y.toFixed(2)}`,
            `A ${outerRx.toFixed(2)} ${outerRy.toFixed(2)} 0 ${largeArcFlag} 1 ${endOuter.x.toFixed(2)} ${endOuter.y.toFixed(2)}`,
            `L ${endInner.x.toFixed(2)} ${endInner.y.toFixed(2)}`,
            `A ${innerRx.toFixed(2)} ${innerRy.toFixed(2)} 0 ${largeArcFlag} 0 ${startInner.x.toFixed(2)} ${startInner.y.toFixed(2)}`,
            'Z'
        ].join(' ');
    }

    async renderSectorSeats(sector) {
        const container = document.getElementById('sectorSeats');
        container.innerHTML = '';

        const title = document.createElement('h3');
        title.textContent = `Asientos - ${sector.nombre}`;
        container.appendChild(title);

        const loadingState = document.createElement('p');
        loadingState.className = 'empty-state';
        loadingState.textContent = 'Cargando asientos...';
        container.appendChild(loadingState);

        const grid = document.createElement('div');
        grid.className = 'seats-grid';
        const sectorPrice = Number(sector?.pivot?.precio ?? 0);
        const sectorId = String(sector.id);
        const requestId = ++this.activeSectorRequestId;

        let apiSeats = this.seatsCacheBySector.get(sectorId);

        if (!apiSeats) {
            try {
                const response = await fetch(`/api/eventos/${this.eventoId}/sectores/${sectorId}/asientos`);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const payload = await response.json();
                apiSeats = payload?.data?.asientos ?? [];
                this.seatsCacheBySector.set(sectorId, apiSeats);
            } catch (error) {
                if (requestId !== this.activeSectorRequestId) {
                    return;
                }

                loadingState.textContent = 'No se pudieron cargar los asientos de este sector';
                console.error('Error cargando asientos del sector:', error);
                return;
            }
        }

        if (requestId !== this.activeSectorRequestId) {
            return;
        }

        loadingState.remove();

        const asientos = Array.isArray(apiSeats) ? [...apiSeats] : [];
        asientos.sort((a, b) => {
            const filaA = String(a.fila);
            const filaB = String(b.fila);

            const filaDiff = filaA.localeCompare(filaB, 'es', {
                numeric: true,
                sensitivity: 'base'
            });

            if (filaDiff !== 0) {
                return filaDiff;
            }

            return Number(a.numero) - Number(b.numero);
        });

        if (asientos.length === 0) {
            const emptyState = document.createElement('p');
            emptyState.className = 'empty-state';
            emptyState.textContent = 'No hay asientos para este sector';
            container.appendChild(emptyState);
            this.updateSeatVisuals();
            return;
        }

        asientos.forEach(apiSeat => {
            const asiento = {
                id: String(apiSeat.id),
                fila: apiSeat.fila,
                columna: apiSeat.numero,
                estado: apiSeat.disponible ? 'disponible' : 'ocupado',
                precio: sectorPrice,
                sector_id: sector.id
            };

            const seatElement = this.createSeatElement(asiento);
            grid.appendChild(seatElement);
        });

        container.appendChild(grid);

        // Actualizar selección previa
        this.updateSeatVisuals();
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
        seat.dataset.seatId = String(asiento.id);
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
        const seatId = String(asiento.id);
        const normalizedSeat = {
            ...asiento,
            id: seatId
        };

        if (this.selectedSeats.has(seatId)) {
            // Remover
            this.selectedSeats.delete(seatId);
        } else {
            // Añadir
            this.selectedSeats.set(seatId, normalizedSeat);
        }

        // Actualizar UI
        this.updateSeatVisuals();
        this.updateCart();
        this.saveCartToStorage();
    }

    updateSeatVisuals() {
        document.querySelectorAll('.seat').forEach(seat => {
            const seatId = seat.dataset.seatId;
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
                const seatId = String(asiento.id);

                // Limpia selección heredada de versiones antiguas con IDs inventados tipo "sector-fila-col".
                if (!/^\d+$/.test(seatId)) {
                    return;
                }

                this.selectedSeats.set(seatId, {
                    ...asiento,
                    id: seatId
                });
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
