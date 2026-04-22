class SeatMapManager {
    constructor(eventoId) {
        // ID del evento actual (se utiliza en llamadas API y persistencia del carrito).
        this.eventoId = eventoId;
        // Selección actual en memoria: clave = id de asiento, valor = datos del asiento.
        this.selectedSeats = new Map(); // Map<seatId, seatData>
        // Cache por sector para no repetir peticiones de asientos al cambiar de pestaña/sector.
        this.seatsCacheBySector = new Map(); // Map<sectorId, apiSeat[]>
        // Contador para invalidar respuestas antiguas cuando el usuario cambia rápido de sector.
        this.activeSectorRequestId = 0;
        // Payload completo del evento recibido desde backend.
        this.data = null;
        // Reservas activas creadas en el paso previo al pago (array de objetos ReservaResource).
        this.reservasActivas = [];
        // Referencia al intervalo del countdown del modal de pago.
        this.paymentTimerInterval = null;

        this.reservasActivas = [];   // [{id, reservado_hasta}, ...]
        this.paymentTimerInterval = null;

        // Arranca el ciclo de carga inicial de la pantalla.
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

    // Dibuja el mapa del estadio en SVG y crea chips para elegir sector.
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

        const canvas = document.createElement('div');
        canvas.className = 'stadium-canvas';

        const svg = document.createElementNS(svgNS, 'svg');
        svg.classList.add('stadium-svg');
        svg.setAttribute('viewBox', '0 0 1000 650');
        svg.setAttribute('role', 'img');
        svg.setAttribute('aria-label', 'Mapa de sectores del estadio');

        const shellX = 120;
        const shellY = 145;
        const shellWidth = 760;
        const shellHeight = 390;
        const shellRadius = 120;

        const shell = document.createElementNS(svgNS, 'rect');
        shell.classList.add('stadium-shell');
        shell.setAttribute('x', String(shellX));
        shell.setAttribute('y', String(shellY));
        shell.setAttribute('width', String(shellWidth));
        shell.setAttribute('height', String(shellHeight));
        shell.setAttribute('rx', String(shellRadius));
        shell.setAttribute('ry', String(shellRadius));
        svg.appendChild(shell);

        const innerGuide = document.createElementNS(svgNS, 'rect');
        innerGuide.classList.add('stadium-inner-guide');
        innerGuide.setAttribute('x', '262');
        innerGuide.setAttribute('y', '220');
        innerGuide.setAttribute('width', '476');
        innerGuide.setAttribute('height', '236');
        innerGuide.setAttribute('rx', '72');
        innerGuide.setAttribute('ry', '72');
        svg.appendChild(innerGuide);

        const stage = document.createElementNS(svgNS, 'rect');
        stage.classList.add('stadium-stage');
        stage.setAttribute('x', '388');
        stage.setAttribute('y', '34');
        stage.setAttribute('width', '224');
        stage.setAttribute('height', '72');
        stage.setAttribute('rx', '22');
        svg.appendChild(stage);

        const stageLabel = document.createElementNS(svgNS, 'text');
        stageLabel.classList.add('stadium-stage-label');
        stageLabel.setAttribute('x', '500');
        stageLabel.setAttribute('y', '78');
        stageLabel.textContent = 'ESCENARIO';
        svg.appendChild(stageLabel);

        const sectorShapeMap = new Map();
        const sectorChipMap = new Map();

        // Marca visualmente un sector activo y dispara carga de sus asientos.
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

        // Reparte sectores en laterales, zona inferior y centro. Sin sectores en la parte superior.
        const perimeterSlots = this.buildStadiumSlots(sectores.length, {
            x: shellX,
            y: shellY,
            width: shellWidth,
            height: shellHeight
        });

        sectores.forEach((sector, index) => {
            const slot = perimeterSlots[index];
            if (!slot) {
                return;
            }

            const shape = document.createElementNS(svgNS, 'rect');
            shape.classList.add('stadium-sector-shape');
            shape.dataset.sectorId = String(sector.id);
            shape.style.setProperty('--sector-color', sector.color_hex || '#f53003');
            shape.setAttribute('x', slot.x.toFixed(2));
            shape.setAttribute('y', slot.y.toFixed(2));
            shape.setAttribute('width', slot.width.toFixed(2));
            shape.setAttribute('height', slot.height.toFixed(2));
            shape.setAttribute('rx', '12');
            shape.setAttribute('ry', '12');
            shape.setAttribute('tabindex', '0');

            const sectorPrice = Number(sector?.pivot?.precio ?? 0);
            const title = document.createElementNS(svgNS, 'title');
            title.textContent = `${sector.nombre} - ${sectorPrice.toFixed(2)} EUR`;
            shape.appendChild(title);

            shape.addEventListener('click', () => setActiveSector(sector));
            // Accesibilidad: permite selección con teclado.
            shape.addEventListener('keydown', event => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    setActiveSector(sector);
                }
            });

            svg.appendChild(shape);
            sectorShapeMap.set(String(sector.id), shape);

            const label = document.createElementNS(svgNS, 'text');
            label.classList.add('stadium-sector-label-text');
            label.setAttribute('x', (slot.x + slot.width / 2).toFixed(2));
            label.setAttribute('y', (slot.y + slot.height / 2).toFixed(2));
            label.textContent = sector.nombre.length > 10
                ? `${sector.nombre.slice(0, 10)}...`
                : sector.nombre;
            svg.appendChild(label);
        });

        canvas.appendChild(svg);
        stadiumView.appendChild(canvas);

        const sectorList = document.createElement('div');
        sectorList.className = 'stadium-sector-list';

        // Lista inferior de sectores (alternativa de interacción al SVG).
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

        // Selecciona el primer sector por defecto para mostrar asientos al cargar.
        setActiveSector(sectores[0]);
    }

    // Crea slots de sectores para lateral derecho, inferior, lateral izquierdo y zona interior.
    buildStadiumSlots(total, frame) {
        if (!Number.isFinite(total) || total <= 0) {
            return [];
        }

        const sideGap = 8;
        const offset = 12;
        const horizontalThickness = 56;
        const verticalThickness = 64;
        const centerColumns = 3;
        const centerGap = 12;

        const rightCount = Math.max(1, Math.round(total * 0.25));
        const bottomCount = Math.max(1, Math.round(total * 0.25));
        const leftCount = Math.max(1, Math.round(total * 0.25));
        const centerCount = Math.max(1, total - rightCount - bottomCount - leftCount);

        const adjustedBottomCount = Math.max(1, total - rightCount - leftCount - centerCount);
        const slots = [];

        const buildHorizontal = (count, y) => {
            const width = (frame.width - sideGap * (count - 1)) / count;

            for (let i = 0; i < count; i++) {
                slots.push({
                    x: frame.x + i * (width + sideGap),
                    y,
                    width,
                    height: horizontalThickness
                });
            }
        };

        const buildVertical = (count, x) => {
            const height = (frame.height - sideGap * (count - 1)) / count;

            for (let i = 0; i < count; i++) {
                slots.push({
                    x,
                    y: frame.y + i * (height + sideGap),
                    width: verticalThickness,
                    height
                });
            }
        };

        buildVertical(rightCount, frame.x + frame.width + offset);
        buildHorizontal(adjustedBottomCount, frame.y + frame.height + offset);
        buildVertical(leftCount, frame.x - verticalThickness - offset);

        const buildCenter = count => {
            const innerWidth = frame.width * 0.56;
            const innerHeight = frame.height * 0.46;
            const innerX = frame.x + (frame.width - innerWidth) / 2;
            const innerY = frame.y + (frame.height - innerHeight) / 2;

            const columns = Math.max(1, Math.min(centerColumns, count));
            const rows = Math.max(1, Math.ceil(count / columns));
            const cellWidth = (innerWidth - centerGap * (columns - 1)) / columns;
            const cellHeight = (innerHeight - centerGap * (rows - 1)) / rows;

            for (let i = 0; i < count; i++) {
                const col = i % columns;
                const row = Math.floor(i / columns);

                slots.push({
                    x: innerX + col * (cellWidth + centerGap),
                    y: innerY + row * (cellHeight + centerGap),
                    width: cellWidth,
                    height: cellHeight
                });
            }
        };

        buildCenter(centerCount);

        return slots.slice(0, total);
    }

    // Carga y renderiza asientos del sector activo, con cache y control de carrera entre peticiones.
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
        // ID incremental: si llega una respuesta vieja, se ignora.
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
                // Si el usuario ya cambió de sector, no sobrescribimos la vista actual.
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
        // Orden natural por fila y luego por número para una lectura consistente.
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
            // Normaliza el asiento de API al formato que consume la UI.
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



    // Método legado: genera asientos sintéticos por matriz (actualmente no se usa en el flujo principal).
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


    // Crea el nodo DOM de un asiento y enlaza click solo si está disponible.
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

    // Añade o quita un asiento de la selección y sincroniza toda la UI.
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

    // Aplica clase visual de seleccionado en todos los asientos renderizados.
    updateSeatVisuals() {
        document.querySelectorAll('.seat').forEach(seat => {
            const seatId = seat.dataset.seatId;
            seat.classList.remove('seat-selected');

            if (this.selectedSeats.has(seatId)) {
                seat.classList.add('seat-selected');
            }
        });
    }

    // Punto central de actualización del resumen de compra.
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

    // Muestra lista de asientos elegidos y botón para eliminarlos uno a uno.
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

    // Calcula subtotal por sector y lo pinta en el desglose de precios.
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

    // Calcula el total general sumando el precio de todos los asientos seleccionados.
    updateTotal() {
        const total = Array.from(this.selectedSeats.values())
            .reduce((sum, asiento) => sum + parseFloat(asiento.precio), 0);

        document.getElementById('totalAmount').textContent =
            `${total.toFixed(2)}€`;
    }

    // Persiste el carrito en localStorage para mantener selección entre recargas.
    saveCartToStorage() {
        const cartData = {
            eventoId: this.eventoId,
            seats: Array.from(this.selectedSeats.values())
        };
        localStorage.setItem('seatmap_cart', JSON.stringify(cartData));
    }

    // Restaura selección previa si corresponde al mismo evento actual.
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

            // Refresca estado visual y totales tras hidratar el carrito.
            this.updateSeatVisuals();
            this.updateCart();
        } catch (error) {
            console.error('Error cargando carrito:', error);
            localStorage.removeItem('seatmap_cart');
        }
    }

    // Enlaza acciones de la vista con métodos de la clase.
    setupEventListeners() {
        document.getElementById('confirmBtn').addEventListener('click', () => this.proceedToCheckout());
        document.getElementById('payBtn').addEventListener('click', () => this.confirmPayment());
        document.getElementById('closePaymentModal').addEventListener('click', () => this.closePaymentModal());
        // Cerrar al pulsar fuera del modal
        document.getElementById('paymentModal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) this.closePaymentModal();
        });
    }

    // Reserva los asientos seleccionados y abre el modal de pago simulado.
    async proceedToCheckout() {
        if (this.selectedSeats.size === 0) {
            alert('Selecciona al menos un asiento para continuar.');
            return;
        }

        const token = localStorage.getItem('sanctum_token');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Authorization': token ? `Bearer ${token}` : ''
        };

        const asientos = Array.from(this.selectedSeats.values());

        try {
            // Paso 1: Reservar todos los asientos
            this.reservasActivas = [];

            for (const asiento of asientos) {
                const res = await fetch('/api/reservas', {
                    method: 'POST', headers, credentials: 'include',
                    body: JSON.stringify({
                        evento_id: Number(this.eventoId),
                        asiento_id: Number(asiento.id)
                    })
                });
                // Si el servidor responde 401, redirige a login
                if (res.status === 401) {
                    window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`;
                    return;
                }

                // Si es un 302, probablemente no autenticado
                if (res.status === 302) {
                    window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`;
                    return;
                }

                // Si no es JSON, lee el texto y lanza un error amigable
                const contentType = res.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    const text = await res.text();
                    throw new Error(`Respuesta del servidor inesperada (HTTP ${res.status}):\n` + text.slice(0, 200));
                }

                // Si es JSON, ahora sí:
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || `HTTP ${res.status}`);
                }
                this.reservasActivas.push(data.data); // guarda id y reservado_hasta
            }

            if (this.reservasActivas.length > 0) {
                this.openPaymentModal();
            }
        } catch (error) {
            console.error('Error al reservar:', error);
            alert('Error al reservar los asientos: ' + error.message);
        }
    }

    openPaymentModal() {
        const modal = document.getElementById('paymentModal');
        const summary = document.getElementById('paymentSummary');
        const totalEl = document.getElementById('paymentTotal');
        const payBtn = document.getElementById('payBtn');

        // Rehabilita el botón por si en un intento anterior el contador expiró.
        payBtn.disabled = false;
        payBtn.textContent = 'Pagar ahora';

        // Resumen asientos
        summary.innerHTML = '';
        let total = 0;
        Array.from(this.selectedSeats.values()).forEach(seat => {
            const precio = Number(seat.precio || 0);
            total += precio;
            const row = document.createElement('div');
            row.className = 'payment-seat-row';
            row.innerHTML = `<span>Fila ${seat.fila} · Asiento ${seat.columna} · ${seat.sector_nombre || ''}</span><span>${precio.toFixed(2)}€</span>`;
            summary.appendChild(row);
        });
        totalEl.textContent = total.toFixed(2).replace('.', ',') + '€';

        // Calcular tiempo restante desde reservado_hasta de la primera reserva
        const primeraReserva = this.reservasActivas[0];
        const expira = primeraReserva?.reservado_hasta
            ? new Date(primeraReserva.reservado_hasta)
            : new Date(Date.now() + 1 * 60 * 1000);

        // Arrancar countdown
        this.startCountdown(expira);

        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    startCountdown(expiresAt) {
        clearInterval(this.paymentTimerInterval);
        const el = document.getElementById('paymentCountdown');

        const tick = () => {
            const remaining = Math.max(0, expiresAt - Date.now());
            const mins = Math.floor(remaining / 60000);
            const secs = Math.floor((remaining % 60000) / 1000);
            el.textContent = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;

            if (remaining <= 0) {
                clearInterval(this.paymentTimerInterval);
                el.textContent = '00:00';
                document.getElementById('payBtn').disabled = true;
                alert('El tiempo de reserva ha expirado. Por favor, vuelve a seleccionar tus asientos.');

                // Ejecuta limpieza completa para sincronizar UI y backend tras expirar.
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
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Authorization': token ? `Bearer ${token}` : ''
        };

        const peticiones = this.reservasActivas
            .filter(reserva => Number.isFinite(Number(reserva?.id)))
            .map(reserva =>
                fetch(`/api/reservas/${reserva.id}`, {
                    method: 'DELETE',
                    headers,
                    credentials: 'include'
                })
            );

        await Promise.allSettled(peticiones);
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

        // Invalida cache para traer estado real de asientos tras expirar.
        this.seatsCacheBySector.clear();
        this.refreshActiveSectorSeats();
    }

    refreshActiveSectorSeats() {
        const activeSectorButton = document.querySelector('.stadium-sector-chip.active');
        if (!activeSectorButton) {
            return;
        }

        const activeSectorId = String(activeSectorButton.dataset.sectorId);
        const sectores = this.data?.data?.sectores_disponibles ?? [];
        const activeSector = sectores.find(sector => String(sector.id) === activeSectorId);

        if (activeSector) {
            this.renderSectorSeats(activeSector);
        }
    }

    closePaymentModal() {
        clearInterval(this.paymentTimerInterval);
        document.getElementById('paymentModal').style.display = 'none';
        document.body.style.overflow = '';
        // La reserva permanece en backend durante 1 min (mecánica de reservado_hasta)
        // El usuario puede volver a abrir la página y ver sus asientos reservados
    }

    async confirmPayment() {
        const payBtn = document.getElementById('payBtn');
        payBtn.disabled = true;
        payBtn.textContent = 'Procesando...';

        const token = localStorage.getItem('sanctum_token');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const headers = {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Authorization': token ? `Bearer ${token}` : ''
        };

        try {
            const res = await fetch('/api/compras/confirmar', {
                method: 'POST', headers, credentials: 'include',
                body: JSON.stringify({ metodo_pago: 'tarjeta' })
            });

            // Si el servidor responde 401, redirige a login
            if (res.status === 401) {
                window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`;
                return;
            }

            // Si es un 302, probablemente no autenticado
            if (res.status === 302) {
                window.location.href = `/login?redirect=${encodeURIComponent(window.location.pathname)}`;
                return;
            }

            // Si no es JSON, lee el texto y lanza un error amigable
            const contentType = res.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                const text = await res.text();
                throw new Error(`Respuesta del servidor inesperada (HTTP ${res.status}):\n` + text.slice(0, 200));
            }

            // Si es JSON, ahora sí:
            const data = await res.json();
            if (!res.ok) {
                throw new Error(data.message || `HTTP ${res.status}`);
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
            payBtn.disabled = false;
            payBtn.textContent = 'Pagar ahora';
        }
    }


}

// Inicializar cuando la página carga
document.addEventListener('DOMContentLoaded', () => {
    const eventoId = document.querySelector('[data-evento-id]').dataset.eventoId;
    new SeatMapManager(eventoId);
});
