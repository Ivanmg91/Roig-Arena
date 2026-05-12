# Guía de Implementación: Sincronización del Mapa de Compra con Editor de Sectores

**Estado del documento:** Guía de implementación  
**Fecha:** Mayo 2026  
**Versión:** 1.0

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Requisitos Previos](#requisitos-previos)
3. [Análisis de la Arquitectura Actual](#análisis-de-la-arquitectura-actual)
4. [Cambios Requeridos por Componente](#cambios-requeridos-por-componente)
5. [Guía Paso a Paso de Implementación](#guía-paso-a-paso-de-implementación)
6. [Archivos a Modificar y Crear](#archivos-a-modificar-y-crear)
7. [Ejemplos de Código Detallados](#ejemplos-de-código-detallados)
8. [Testing y Validación](#testing-y-validación)
9. [Consideraciones Futuras](#consideraciones-futuras)

---

## Resumen Ejecutivo

### Objetivo

Sincronizar el mapa de compra de asientos (`compra.js`) con el mapa de edición de sectores (`editarSectoresEvento.js`) para que:

1. **En compra:** los asientos se dibujen directamente sobre un mapa SVG (no solo en lista)
2. **En compra:** cada asiento sea clicable para seleccionar/deseleccionar
3. **En compra:** el mapa use la misma información de sectores que el editor
4. **En compra:** se muestre el estado real (disponible, ocupado, seleccionado)
5. **Futuro:** permita a un programador (no admin) redistribuir asientos sin cambiar la base de datos

### Beneficios

- **Mejor UX:** el usuario ve exactamente dónde está cada asiento
- **Consistencia visual:** mismo mapa en admin y comprador
- **Reutilizable:** el código del mapa puede compartirse
- **Escalable:** fácil de adaptarlo a diferentes distribuciones (cuadrícula, curva, libre)

### Cambios de Alto Nivel

| Componente | Estado Actual | Nuevo Estado |
|-----------|--------------|-------------|
| `compra.js` | Dibuja sectores, lista asientos | Dibuja mapa SVG con asientos individuales clicables |
| `compra.blade.php` | HTML para sector-list | Mantiene estructura, añade contenedor SVG |
| API `/api/eventos/{id}/sectores/{sectorId}/asientos` | Devuelve lista simple | Devuelve con estado de disponibilidad |
| CSS | Estilos para lista | Estilos para grid SVG |
| `editarSectoresEvento.js` | Standalone | Se puede reutilizar lógica de SVG |

---

## Requisitos Previos

### Conocimientos Necesarios

- ✅ JavaScript ES6+ (Clases, async/await, fetch)
- ✅ SVG (atributos básicos, eventos, estilos)
- ✅ Laravel (rutas, controladores, relaciones)
- ✅ Bootstrap/CSS Grid (layout)

### Estado del Proyecto

- ✅ Asientos ya están en la base de datos (`asientos` table)
- ✅ Estados de asientos existen (`estado_asientos`)
- ✅ Relaciones Sector ↔ Asiento ↔ EstadoAsiento están definidas
- ✅ API de eventos y asientos existe
- ✅ Autenticación con Sanctum está implementada

### Cambios Previos Asumidos

- ✅ Todos los asientos de un evento están vinculados a un sector
- ✅ Cada asiento tiene `fila` y `numero` (columna)
- ✅ Cada sector tiene `fila_inicio`, `fila_fin`, `columna_inicio`, `columna_fin`

---

## Análisis de la Arquitectura Actual

### Mapa de Edición de Sectores (`editarSectoresEvento.js`)

**Qué hace:**
- Lee dimensiones del estadio (`rows`, `cols`)
- Lee sectores desde atributo data JSON
- Dibuja grilla SVG con asientos como círculos
- Permite seleccionar asiento inicial y final
- Calcula rectángulo entre ambos y lo guarda como sector

**Código clave:**
```javascript
// Inicialización
const rows = Number(container.dataset.seatRows || 12);
const cols = Number(container.dataset.seatCols || 20);
const sectors = JSON.parse(container.dataset.sectors || '[]');

// Creación de asientos
for (let row = 1; row <= rows; row++) {
    for (let col = 1; col <= cols; col++) {
        const seatGroup = createSvgNode('g', {...});
        const seatCircle = createSvgNode('circle', {...});
        seatGroup.addEventListener('click', () => onSeatClick(row, col));
    }
}

// Guardar sector
const payload = {
    nombre, descripcion, color_hex,
    inicio: { fila: filaInicio, columna: colInicio },
    fin: { fila: filaFin, columna: colFin }
};
```

### Mapa de Compra (`compra.js`)

**Qué hace:**
- Carga evento vía API
- Dibuja sectores como bloques en perímetro del estadio
- Al hacer clic en sector, carga y lista asientos en div aparte
- Usuario selecciona de la lista y se añaden al carrito

**Estructura actual:**
```
┌─ Estadio Visual (SVG con sectores como bloques)
│
├─ Chips de sectores (botones en fila)
│
└─ Lista de Asientos (div con grid de elementos)
    └─ Cada asiento es seleccionable
```

**Problema:** El usuario no ve dónde caen los asientos en el mapa, solo una lista.

---

## Cambios Requeridos por Componente

### 1. Backend - API (`routes/api.php` y Controladores)

#### Cambio 1.1: Endpoint para Asientos con Estado Disponible

**Ruta actual:**
```
GET /api/eventos/{eventoId}/sectores/{sectorId}/asientos
```

**Respuesta actual:**
```json
{
  "data": {
    "asientos": [
      { "id": 1, "fila": "A", "numero": 1, "disponible": true }
    ]
  }
}
```

**Cambio:** Ya es correcto. No necesita cambios.

#### Cambio 1.2: Nuevo Endpoint para Todos los Asientos del Evento (Descarga Completa)

**Nueva ruta:**
```
GET /api/eventos/{eventoId}/asientos (NUEVO)
```

**Respuesta:**
```json
{
  "data": {
    "total_filas": 12,
    "total_columnas": 20,
    "asientos": [
      {
        "id": 1,
        "fila": "A",
        "numero": 1,
        "disponible": true,
        "estado": "disponible",
        "sector_id": 5,
        "sector_nombre": "VIP"
      },
      ...
    ]
  }
}
```

**Beneficio:** El cliente obtiene TODO en una llamada, permitiendo renderizar el mapa completo de una vez.

---

### 2. Frontend - Blade (`resources/views/compra/buy.blade.php`)

#### Cambio 2.1: Estructura HTML

**Antes:**
```html
<div class="seatmap-container">
    <div class="seatmap-area">
        <div class="stadium-layout">
            <div class="stadium-view" id="stadiumView"></div>
        </div>
        <div id="sectorSeats" class="sector-seats"></div>
    </div>
    <aside class="checkout-sidebar">...</aside>
</div>
```

**Después:**
```html
<div class="seatmap-container">
    <div class="seatmap-area">
        <!-- Nueva: Contenedor del mapa SVG completo -->
        <div class="seat-map-wrapper">
            <svg id="seatMapSvg" 
                 class="seat-map-svg"
                 viewBox="0 0 960 560"
                 role="img"
                 aria-label="Mapa interactivo de asientos del evento">
            </svg>
        </div>
        
        <!-- Leyenda -->
        <div class="legend">
            <span class="legend-item">
                <div class="seat seat-available"></div> Disponible
            </span>
            <span class="legend-item">
                <div class="seat seat-reserved"></div> Ocupado
            </span>
            <span class="legend-item">
                <div class="seat seat-selected"></div> Seleccionado
            </span>
        </div>
        
        <!-- Info del sector (opcional, si se quiere mantener) -->
        <div id="sectorInfo" class="sector-info" style="display:none;">
            <h3 id="sectorTitle"></h3>
        </div>
    </div>
    <aside class="checkout-sidebar">...</aside>
</div>
```

**Cambios clave:**
- ✅ Añadir contenedor SVG principal `#seatMapSvg`
- ✅ Mantener leyenda
- ✅ Remover `stadiumView` (ya no lo necesitamos)
- ✅ Remover o esconder `sectorSeats`

---

### 3. Frontend - JavaScript (`public/js/pages/compra.js`)

Este es el cambio más importante. Se reestructura la clase `SeatMapManager`.

#### Cambio 3.1: Nueva Estructura de Clase

```javascript
class SeatMapManager {
    constructor(eventoId) {
        this.eventoId = eventoId;
        this.data = null;                      // Evento + sectores
        this.allSeats = new Map();             // Map<seatId, seatData>
        this.selectedSeats = new Map();        // Map<seatId, seatData> (seleccionados)
        this.seatNodeMap = new Map();          // Map<seatId, SVG element>
        this.reservasActivas = [];
        this.paymentTimerInterval = null;
        
        // CONFIG EDITOR (mismo que editarSectoresEvento.js)
        this.rows = 12;                        // Número de filas
        this.cols = 20;                        // Número de columnas
        this.viewWidth = 960;
        this.viewHeight = 560;
        
        this.init();
    }
    
    async init() {
        try {
            // 1. Cargar evento completo
            await this.loadEventoData();
            
            // 2. Cargar todos los asientos en una sola llamada
            await this.loadAllSeats();
            
            // 3. Renderizar mapa SVG con todos los asientos
            this.renderSeatMap();
            
            // 4. Configurar event listeners
            this.setupEventListeners();
            
            // 5. Restaurar carrito del localStorage
            this.loadCartFromStorage();
        } catch (error) {
            console.error('Error inicializando mapa:', error);
        }
    }
    
    async loadEventoData() {
        const response = await fetch(`/api/eventos/${this.eventoId}/`);
        this.data = await response.json();
        console.log('Evento:', this.data);
    }
    
    async loadAllSeats() {
        // NUEVA LLAMADA que devuelve TODOS los asientos con su estado
        const response = await fetch(`/api/eventos/${this.eventoId}/asientos`);
        const payload = await response.json();
        
        const asientos = payload?.data?.asientos ?? [];
        asientos.forEach(seat => {
            this.allSeats.set(String(seat.id), {
                id: String(seat.id),
                fila: seat.fila,
                numero: seat.numero,
                sector_id: seat.sector_id,
                disponible: seat.disponible,
                estado: seat.disponible ? 'disponible' : 'ocupado'
            });
        });
    }
    
    renderSeatMap() {
        // NUEVA: Renderiza todos los asientos en una matriz SVG
        // Similar a editarSectoresEvento.js
    }
    
    createSeatElement(asiento) {
        // NUEVA: Crea un nodo SVG clicable para cada asiento
    }
    
    updateSeatVisuals() {
        // Actualiza clases CSS de todos los asientos basándose en selectedSeats
    }
}
```

---

### 4. Frontend - CSS (`public/css/pages/compra.css`)

#### Cambio 4.1: Estilos para Mapa SVG

**Añadir:**
```css
/* Mapa SVG */
.seat-map-wrapper {
    display: flex;
    justify-content: center;
    margin-bottom: 2rem;
    background: #f9f9f9;
    border-radius: 8px;
    padding: 1rem;
}

.seat-map-svg {
    max-width: 100%;
    height: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
}

/* Asientos SVG */
.seat-node {
    cursor: pointer;
    transition: opacity 0.2s ease;
}

.seat-node circle {
    fill: #e0e0e0;
    stroke: #999;
    stroke-width: 0.5;
}

/* Estado: Disponible */
.seat-node.seat-available circle {
    fill: #4CAF50;
    stroke: #2E7D32;
}

.seat-node.seat-available:hover circle {
    fill: #66BB6A;
}

/* Estado: Ocupado */
.seat-node.seat-reserved circle {
    fill: #f44336;
    stroke: #c62828;
    opacity: 0.6;
}

.seat-node.seat-reserved {
    cursor: not-allowed;
}

/* Estado: Seleccionado */
.seat-node.seat-selected circle {
    fill: #2196F3;
    stroke: #1565c0;
    stroke-width: 2;
    filter: drop-shadow(0 0 3px rgba(33, 150, 243, 0.5));
}

/* Etiquetas sector (overlay) */
.sector-zone-label {
    fill: white;
    font-size: 14px;
    font-weight: bold;
    pointer-events: none;
}

/* Fondo del mapa */
.sector-map-bg {
    fill: #ffffff;
    stroke: #ccc;
}

/* Etapa/Escenario */
.sector-map-stage {
    fill: #F57C00;
    opacity: 0.3;
}

.sector-map-stage-label {
    fill: #F57C00;
    font-size: 16px;
    font-weight: bold;
    text-anchor: middle;
}

/* Grid lines */
.sector-map-grid-line {
    stroke: #e0e0e0;
    stroke-width: 0.5;
    opacity: 0.5;
}

.sector-map-axis-label {
    fill: #666;
    font-size: 11px;
    text-anchor: middle;
}
```

---

## Guía Paso a Paso de Implementación

### Fase 1: Preparación del Backend (Día 1)

#### Paso 1.1: Crear Endpoint Nuevo para Todos los Asientos

**Archivo:** `app/Http/Controllers/EventoController.php` (o similar)

```php
/**
 * GET /api/eventos/{eventoId}/asientos
 * Devuelve TODOS los asientos del evento con su estado de disponibilidad
 */
public function mostrarTodosLosAsientos(Evento $evento)
{
    // SELECT asientos con relación a estado_asientos del evento
    $asientos = Asiento::whereHas('sector', function ($query) use ($evento) {
        $query->whereIn('id', $evento->sectores()->pluck('id'));
    })
    ->with(['sector', 'estadoAsientos' => function ($query) use ($evento) {
        $query->where('evento_id', $evento->id);
    }])
    ->get()
    ->map(function ($asiento) use ($evento) {
        $estado = $asiento->estadoAsientos->firstWhere('evento_id', $evento->id);
        $disponible = !$estado || 
                      ($estado->estado !== 'reservado' && $estado->estado !== 'vendido');
        
        return [
            'id' => $asiento->id,
            'fila' => $asiento->fila,
            'numero' => $asiento->numero,
            'sector_id' => $asiento->sector_id,
            'sector_nombre' => $asiento->sector->nombre,
            'disponible' => $disponible,
            'estado' => $disponible ? 'disponible' : 'ocupado'
        ];
    });

    return response()->json([
        'data' => [
            'total_filas' => 12,    // Obtener del evento o constante
            'total_columnas' => 20,  // Obtener del evento o constante
            'asientos' => $asientos
        ]
    ]);
}
```

**Ruta:** `routes/api.php`

```php
Route::get('/eventos/{evento}/asientos', [EventoController::class, 'mostrarTodosLosAsientos']);
```

---

### Fase 2: Preparación del Frontend HTML (Día 1)

#### Paso 2.1: Actualizar Vista Blade

**Archivo:** `resources/views/compra/buy.blade.php`

Reemplazar la sección `<!-- LADO IZQUIERDO: MAPA DE ASIENTOS -->` con:

```blade
<div class="seatmap-area">
    <h2>Selecciona tus asientos</h2>

    <!-- Leyenda -->
    <div class="legend">
        <span class="legend-item">
            <div class="seat seat-available"></div> Disponible
        </span>
        <span class="legend-item">
            <div class="seat seat-reserved"></div> Ocupado
        </span>
        <span class="legend-item">
            <div class="seat seat-selected"></div> Seleccionado
        </span>
    </div>

    <!-- Mapa SVG interactivo (NUEVO) -->
    <div class="seat-map-wrapper">
        <svg id="seatMapSvg" 
             class="seat-map-svg"
             viewBox="0 0 960 560"
             role="img"
             aria-label="Mapa interactivo de asientos del evento">
        </svg>
    </div>

    <!-- Info del sector actual (opcional) -->
    <div id="sectorInfo" class="sector-info" style="display:none;">
        <h3 id="sectorTitle"></h3>
        <p id="sectorDesc"></p>
    </div>
</div>
```

**Remover/comentar:**
- `<div class="stadium-layout">` (toda la sección)
- `<div id="sectorSeats" class="sector-seats"></div>`

---

### Fase 3: Implementación del Motor de Mapa SVG (Día 2-3)

#### Paso 3.1: Reemplazar `compra.js`

**Archivo:** `public/js/pages/compra.js`

Este es el archivo más crítico. Se asume que reescribirlo casi en su totalidad.

**Estructura general:**

```javascript
class SeatMapManager {
    // === CONFIGURACIÓN DEL MAPA ===
    constructor(eventoId) {
        // ... (ver abajo para detalles)
    }

    // === INICIALIZACIÓN ===
    async init() { ... }
    async loadEventoData() { ... }
    async loadAllSeats() { ... }

    // === RENDERIZADO SVG ===
    renderSeatMap() { ... }
    createSvgNode(tag, attrs) { ... }
    drawSectorBackgrounds() { ... }
    drawSeatNodes() { ... }

    // === INTERACCIÓN DE ASIENTOS ===
    createSeatElement(asiento) { ... }
    toggleSeat(asiento) { ... }
    updateSeatVisuals() { ... }
    
    // === CARRITO ===
    updateCart() { ... }
    updateSelectionSummary() { ... }
    updatePriceBreakdown() { ... }
    updateTotal() { ... }
    saveCartToStorage() { ... }
    loadCartFromStorage() { ... }

    // === PERSISTENCIA ===
    async proceedToCheckout() { ... }
    async confirmPayment() { ... }
    ... (mantener métodos existentes)
}

document.addEventListener('DOMContentLoaded', () => {
    const eventoId = document.querySelector('[data-evento-id]').dataset.eventoId;
    new SeatMapManager(eventoId);
});
```

---

#### Paso 3.2: Implementar Constructor y Configuración

```javascript
class SeatMapManager {
    constructor(eventoId) {
        this.eventoId = eventoId;
        
        // Datos globales
        this.data = null;
        this.allSeats = new Map();           // Map<seatId, seatData>
        this.selectedSeats = new Map();      // Map<seatId, seatData>
        this.seatNodeMap = new Map();        // Map<seatId, SVGElement>
        
        // Config SVG (debe coincidir con editarSectoresEvento.js)
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
        this.seatRadius = Math.max(6, Math.min(13, 
            Math.min(this.gridWidth / this.cols, this.gridHeight / this.rows) * 0.28
        ));
        this.xStep = this.gridWidth / (this.cols - 1);
        this.yStep = this.gridHeight / (this.rows - 1);
        
        // Reservas y pago
        this.reservasActivas = [];
        this.paymentTimerInterval = null;
        
        this.init();
    }
}
```

---

#### Paso 3.3: Implementar Carga de Datos

```javascript
async init() {
    try {
        console.log('[SeatMapManager] Inicializando para evento:', this.eventoId);
        
        // 1. Cargar evento y sectores
        await this.loadEventoData();
        
        // 2. Cargar todos los asientos
        await this.loadAllSeats();
        
        // 3. Renderizar mapa
        this.renderSeatMap();
        
        // 4. Setup event listeners
        this.setupEventListeners();
        
        // 5. Cargar carrito previo
        this.loadCartFromStorage();
        
    } catch (error) {
        console.error('[SeatMapManager] Error en init:', error);
        this.showError('Error cargando el mapa de asientos');
    }
}

async loadEventoData() {
    const response = await fetch(`/api/eventos/${this.eventoId}/`);
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    
    const json = await response.json();
    this.data = json;
    
    console.log('[SeatMapManager] Evento cargado:', this.data);
}

async loadAllSeats() {
    const response = await fetch(`/api/eventos/${this.eventoId}/asientos`);
    if (!response.ok) throw new Error(`HTTP ${response.status}`);
    
    const json = await response.json();
    const asientos = json?.data?.asientos ?? [];
    
    console.log(`[SeatMapManager] Cargados ${asientos.length} asientos`);
    
    asientos.forEach(seat => {
        const key = String(seat.id);
        this.allSeats.set(key, {
            id: key,
            fila: seat.fila,
            numero: seat.numero,
            sector_id: seat.sector_id,
            sector_nombre: seat.sector_nombre || '',
            disponible: seat.disponible,
            estado: seat.disponible ? 'disponible' : 'ocupado',
            precio: this.data?.data?.sectores_disponibles?.find(s => s.id == seat.sector_id)?.pivot?.precio || 0
        });
    });
}
```

---

#### Paso 3.4: Implementar Renderizado SVG

```javascript
renderSeatMap() {
    const svg = document.getElementById('seatMapSvg');
    if (!svg) {
        console.error('[SeatMapManager] SVG no encontrado');
        return;
    }
    
    svg.innerHTML = '';
    
    // 1. Fondo
    this.createAndAppendSvgNode(svg, 'rect', {
        x: 0,
        y: 0,
        width: this.viewWidth,
        height: this.viewHeight,
        rx: 14,
        class: 'sector-map-bg'
    });
    
    // 2. Escenario
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
    
    // 3. Grid (líneas guía)
    this.drawGridLines(svg);
    
    // 4. Asientos (como círculos clicables)
    this.drawSeatNodes(svg);
    
    // 5. Overlay de sectores (coloreado, semitransparente)
    this.drawSectorBackgrounds(svg);
}

drawGridLines(svg) {
    // Dibujar líneas horizontales y verticales de referencia
    // (similar a editarSectoresEvento.js)
    
    for (let row = 1; row <= this.rows; row++) {
        const y = this.padTop + (row - 1) * this.yStep;
        
        // Etiqueta de fila
        const rowLabel = this.createSvgNode('text', {
            x: 34,
            y: y + 4,
            class: 'sector-map-axis-label',
            'text-anchor': 'middle'
        });
        rowLabel.textContent = String(row);
        svg.appendChild(rowLabel);
        
        // Línea horizontal
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
        
        // Etiqueta de columna
        const colLabel = this.createSvgNode('text', {
            x,
            y: this.viewHeight - 6,
            class: 'sector-map-axis-label',
            'text-anchor': 'middle'
        });
        colLabel.textContent = String(col);
        svg.appendChild(colLabel);
        
        // Línea vertical
        this.createAndAppendSvgNode(svg, 'line', {
            x1: x,
            y1: this.padTop,
            x2: x,
            y2: this.padTop + this.gridHeight,
            class: 'sector-map-grid-line'
        });
    }
}

drawSeatNodes(svg) {
    this.allSeats.forEach((asiento) => {
        const [fila, numero] = this.parseAsientoCoords(asiento);
        if (!fila || !numero) return;
        
        const x = this.padLeft + (numero - 1) * this.xStep;
        const y = this.padTop + (fila - 1) * this.yStep;
        
        const seatGroup = this.createSvgNode('g', {
            class: `seat-node seat-${asiento.estado}`,
            'data-seat-id': asiento.id,
            'data-fila': fila,
            'data-numero': numero,
            'aria-label': `Asiento fila ${fila} número ${numero}`
        });
        
        const circle = this.createSvgNode('circle', {
            cx: x,
            cy: y,
            r: this.seatRadius,
            class: 'seat-circle'
        });
        
        seatGroup.appendChild(circle);
        
        // Solo si está disponible, permitir click
        if (asiento.estado === 'disponible') {
            seatGroup.style.cursor = 'pointer';
            seatGroup.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleSeat(asiento);
            });
            
            // Hover efecto
            seatGroup.addEventListener('mouseenter', () => {
                circle.style.opacity = '0.8';
            });
            seatGroup.addEventListener('mouseleave', () => {
                circle.style.opacity = '1';
            });
        }
        
        svg.appendChild(seatGroup);
        this.seatNodeMap.set(asiento.id, seatGroup);
    });
    
    console.log(`[SeatMapManager] Dibujados ${this.seatNodeMap.size} asientos`);
}

drawSectorBackgrounds(svg) {
    // Dibujar rectángulos semitransparentes sobre cada sector
    const sectores = this.data?.data?.sectores_disponibles ?? [];
    
    sectores.forEach((sector) => {
        const bounds = this.calculateSectorBounds(sector);
        if (!bounds) return;
        
        const x1 = this.padLeft + (bounds.colInicio - 1) * this.xStep;
        const x2 = this.padLeft + (bounds.colFin - 1) * this.xStep;
        const y1 = this.padTop + (bounds.filaInicio - 1) * this.yStep;
        const y2 = this.padTop + (bounds.filaFin - 1) * this.yStep;
        
        const zonePadding = this.seatRadius + 3;
        const rectX = x1 - zonePadding;
        const rectY = y1 - zonePadding;
        const rectWidth = (x2 - x1) + zonePadding * 2;
        const rectHeight = (y2 - y1) + zonePadding * 2;
        
        const sectorRect = this.createSvgNode('rect', {
            x: rectX,
            y: rectY,
            width: rectWidth,
            height: rectHeight,
            rx: 8,
            class: 'sector-zone-background',
            fill: sector.color_hex || '#5ba8ff',
            opacity: '0.15',
            'pointer-events': 'none'
        });
        
        svg.appendChild(sectorRect);
        
        // Etiqueta del sector
        const label = this.createSvgNode('text', {
            x: rectX + 8,
            y: rectY + 16,
            class: 'sector-zone-label',
            'text-anchor': 'start',
            fill: sector.color_hex || '#5ba8ff',
            'font-size': '12px',
            'font-weight': 'bold',
            'pointer-events': 'none'
        });
        label.textContent = sector.nombre;
        svg.appendChild(label);
    });
}

calculateSectorBounds(sector) {
    const filaInicioRaw = Number(sector.fila_inicio);
    const filaFinRaw = Number(sector.fila_fin);
    const colInicioRaw = Number(sector.columna_inicio);
    const colFinRaw = Number(sector.columna_fin);
    
    if (!Number.isFinite(filaInicioRaw) || !Number.isFinite(colInicioRaw)) {
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

parseAsientoCoords(asiento) {
    // Convertir fila (número) y numero (columna) a coordenadas
    let fila = asiento.fila;
    if (typeof fila === 'string') {
        fila = fila.charCodeAt(0) - 64; // 'A' -> 1, 'B' -> 2, etc
    }
    return [Number(fila), Number(asiento.numero)];
}

// Utilities
createSvgNode(tag, attrs) {
    const node = document.createElementNS('http://www.w3.org/2000/svg', tag);
    Object.entries(attrs).forEach(([key, value]) => {
        node.setAttribute(key, String(value));
    });
    return node;
}

createAndAppendSvgNode(parent, tag, attrs) {
    const node = this.createSvgNode(tag, attrs);
    parent.appendChild(node);
    return node;
}
```

---

#### Paso 3.5: Implementar Interacción de Asientos

```javascript
toggleSeat(asiento) {
    const seatId = String(asiento.id);
    
    if (this.selectedSeats.has(seatId)) {
        // Desseleccionar
        this.selectedSeats.delete(seatId);
    } else {
        // Seleccionar
        this.selectedSeats.set(seatId, asiento);
    }
    
    // Actualizar UI
    this.updateSeatVisuals();
    this.updateCart();
    this.saveCartToStorage();
}

updateSeatVisuals() {
    this.seatNodeMap.forEach((seatNode, seatId) => {
        const circle = seatNode.querySelector('circle');
        
        seatNode.classList.remove('seat-selected');
        
        if (this.selectedSeats.has(seatId)) {
            seatNode.classList.add('seat-selected');
            circle?.style.setProperty('--seat-state', 'selected');
        }
    });
}

updateCart() {
    const seatCount = this.selectedSeats.size;
    
    document.getElementById('seatCount').textContent = 
        `${seatCount} asiento${seatCount !== 1 ? 's' : ''}`;
    
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
    
    this.selectedSeats.forEach((asiento) => {
        const [fila, numero] = this.parseAsientoCoords(asiento);
        
        const item = document.createElement('div');
        item.className = 'selected-item';
        item.innerHTML = `
            <span>${asiento.sector_nombre || 'Sector'} - Fila ${fila}, Asiento ${numero}</span>
            <button class="selected-item-remove" data-seat-id="${asiento.id}">✕</button>
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
    
    const asientosPorSector = {};
    
    this.selectedSeats.forEach(asiento => {
        const sectorId = asiento.sector_id;
        if (!asientosPorSector[sectorId]) {
            asientosPorSector[sectorId] = [];
        }
        asientosPorSector[sectorId].push(asiento);
    });
    
    let total = 0;
    Object.entries(asientosPorSector).forEach(([sectorId, asientos]) => {
        const sector = this.data.data.sectores_disponibles.find(s => s.id == sectorId);
        const precioSector = Number(sector?.pivot?.precio || 0);
        const subtotal = asientos.length * precioSector;
        total += subtotal;
        
        const line = document.createElement('div');
        line.className = 'price-line';
        line.innerHTML = `
            <span>${sector?.nombre || 'Sector '} (${asientos.length}x)</span>
            <strong>${subtotal.toFixed(2)}€</strong>
        `;
        breakdown.appendChild(line);
    });
}

updateTotal() {
    let total = 0;
    
    this.selectedSeats.forEach(asiento => {
        const sectorId = asiento.sector_id;
        const sector = this.data.data.sectores_disponibles.find(s => s.id == sectorId);
        const precio = Number(sector?.pivot?.precio || 0);
        total += precio;
    });
    
    document.getElementById('totalAmount').textContent = `${total.toFixed(2)}€`;
}

setupEventListeners() {
    document.getElementById('confirmBtn').addEventListener('click', () => this.proceedToCheckout());
    document.getElementById('payBtn').addEventListener('click', () => this.confirmPayment());
    document.getElementById('closePaymentModal').addEventListener('click', () => this.closePaymentModal());
}

showError(message) {
    alert(message);
    console.error(message);
}
```

---

#### Paso 3.6: Mantener Métodos de Checkout (igual que antes)

Los métodos:
- `proceedToCheckout()`
- `openPaymentModal()`
- `startCountdown()`
- `confirmPayment()`
- `closePaymentModal()`
- `saveCartToStorage()`
- `loadCartFromStorage()`

**Se mantienen prácticamente igual**. Solo cambios menores:
- Cambiar `this.selectedSeats.forEach(...)` para iterar sobre el nuevo mapa

---

### Fase 4: Actualizar CSS (Día 1)

#### Paso 4.1: Añadir a `public/css/pages/compra.css`

```css
/* ============================================
   MAPA SVG DE ASIENTOS
   ============================================ */

.seat-map-wrapper {
    display: flex;
    justify-content: center;
    margin-bottom: 2rem;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 1rem;
    overflow: auto;
}

.seat-map-svg {
    max-width: 100%;
    height: auto;
    display: block;
    margin: 0 auto;
}

/* Nodos de asientos en SVG */
.seat-node {
    transition: opacity 0.15s ease;
}

.seat-node circle {
    fill: #e0e0e0;
    stroke: #999;
    stroke-width: 0.5;
}

/* Disponible */
.seat-node.seat-disponible circle {
    fill: #4CAF50;
    stroke: #2E7D32;
    stroke-width: 1;
}

.seat-node.seat-disponible:hover circle {
    fill: #66BB6A;
    filter: drop-shadow(0 0 2px rgba(76, 175, 80, 0.5));
}

/* Ocupado */
.seat-node.seat-ocupado circle {
    fill: #f44336;
    stroke: #c62828;
    opacity: 0.5;
}

.seat-node.seat-ocupado {
    cursor: not-allowed;
    opacity: 0.7;
}

/* Seleccionado */
.seat-node.seat-selected circle {
    fill: #2196F3;
    stroke: #1565c0;
    stroke-width: 2;
    filter: drop-shadow(0 0 4px rgba(33, 150, 243, 0.6));
}

/* Fondo y escenario */
.sector-map-bg {
    fill: #ffffff;
    stroke: #999;
    stroke-width: 1;
}

.sector-map-stage {
    fill: #FF9800;
    opacity: 0.2;
}

.sector-map-stage-label {
    fill: #FF9800;
    font-size: 14px;
    font-weight: bold;
}

/* Grid de referencia */
.sector-map-grid-line {
    stroke: #e0e0e0;
    stroke-width: 0.5;
    opacity: 0.4;
}

.sector-map-axis-label {
    fill: #999;
    font-size: 10px;
    font-weight: 500;
}

/* Overlay de sectores */
.sector-zone-background {
    opacity: 0.1;
    pointer-events: none;
}

.sector-zone-label {
    font-size: 12px;
    font-weight: bold;
    opacity: 0.7;
    pointer-events: none;
}

/* Info del sector (si se usa) */
.sector-info {
    margin-top: 1rem;
    padding: 1rem;
    background: #f5f5f5;
    border-radius: 4px;
    border-left: 4px solid #2196F3;
}

.sector-info h3 {
    margin: 0 0 0.5rem 0;
    color: #2196F3;
}

.sector-info p {
    margin: 0;
    color: #666;
    font-size: 0.9rem;
}
```

---

## Archivos a Modificar y Crear

### Resumen de Cambios

| Archivo | Acción | Prioridad | Esfuerzo |
|---------|--------|-----------|----------|
| `app/Http/Controllers/EventoController.php` | Agregar método `mostrarTodosLosAsientos()` | ALTA | 1 hora |
| `routes/api.php` | Agregar ruta GET `/eventos/{evento}/asientos` | ALTA | 15 min |
| `resources/views/compra/buy.blade.php` | Reemplazar sección de mapa por SVG | ALTA | 30 min |
| `public/js/pages/compra.js` | Reescribir clase `SeatMapManager` | CRÍTICA | 4-6 horas |
| `public/css/pages/compra.css` | Agregar estilos SVG | MEDIA | 1 hora |
| `public/css/pages/stadium.css` | Remover estilos no usados (optional) | BAJA | 30 min |

---

## Ejemplos de Código Detallados

(Ver secciones anteriores para código completo)

### Flujo Completo de Ejemplo

**Usuario abre `/eventos/{id}/compra`:**

1. ✅ `buy.blade.php` renderiza HTML con `#seatMapSvg` vacío
2. ✅ `compra.js` inicializa `new SeatMapManager(eventoId)`
3. ✅ Llamada a `/api/eventos/{id}/`
4. ✅ Llamada a `/api/eventos/{id}/asientos` (NUEVA)
5. ✅ Renderiza SVG con todos los asientos
6. ✅ Usuario hace clic en asiento disponible
7. ✅ `toggleSeat()` lo añade a `selectedSeats`
8. ✅ `updateSeatVisuals()` marca el asiento como seleccionado
9. ✅ `updateCart()` recalcula totales
10. ✅ Repite 6-9 según sea necesario
11. ✅ Botón **Confirmar Compra** reserva y abre modal de pago
12. ✅ Resto del flujo igual que antes

---

## Testing y Validación

### Test 1: Renderizado del Mapa

```bash
# En consola del navegador:
console.log(window.seatMapManager?.allSeats.size);  // Debe >0
console.log(window.seatMapManager?.seatNodeMap.size);  // Debe coincidir
```

### Test 2: Interacción de Asientos

```javascript
// Click en asiento programático
const seatNode = document.querySelector('[data-seat-id="1"]');
seatNode.click();  // Debe añadir a selectedSeats
console.log(window.seatMapManager?.selectedSeats.size);  // Debe ser 1
```

### Test 3: Persistencia de Carrito

```javascript
// Seleccionar asientos, recargar página
window.location.reload();
// Asientos deben seguir seleccionados
```

### Test 4: Checkout

```javascript
// Seleccionar asientos, ejecutar proceedToCheckout()
const manager = window.seatMapManager;
manager.selectedSeats.set('1', {...});
manager.proceedToCheckout();
// Debe abrir modal y crear reservas
```

---

## Consideraciones Futuras

### V2: Edición de Distribución de Asientos

Para permitir que un programador (no admin) redistribuya asientos alrededor del escenario:

1. **Migrar tabla `asientos` a modelo flexible**
   - Añadir campos `x_position`, `y_position` (coordenadas SVG)
   - O mantener `fila`, `numero` pero permitir offset visual

2. **Crear nuevo módulo de edición SVG**
   - Permitir drag & drop de asientos
   - Guardar nueva posición en backend
   - Respetar la alineación a grilla o permitir libre

3. **Versionando de layouts**
   - Guardar histórico de disposiciones
   - Permitir rollback si hay errores

### V3: Soporte para Formas Irregulares

- Permitir sectores no rectangulares (polígonos)
- Path SVG para cada sector en lugar de rectángulos
- Más complejo pero más flexible

### V4: Editor Visual para Admins

- UI drag & drop sin necesidad de programador
- Canvas interactivo con herramientas
- Real-time preview

---

## Checklist de Implementación

- [ ] **Backend**
  - [ ] Crear endpoint `/api/eventos/{id}/asientos`
  - [ ] Validar respuesta JSON
  - [ ] Probar con cliente real (Postman/curl)

- [ ] **Blade**
  - [ ] Actualizar `buy.blade.php`
  - [ ] Validar que carga sin errores

- [ ] **JavaScript**
  - [ ] Implementar `loadAllSeats()`
  - [ ] Implementar `renderSeatMap()`
  - [ ] Implementar `drawSeatNodes()`
  - [ ] Implementar `toggleSeat()`
  - [ ] Implementar `updateCart()`
  - [ ] Probar cada método de forma aislada

- [ ] **CSS**
  - [ ] Añadir estilos SVG básicos
  - [ ] Probar en navegadores (Chrome, Firefox, Safari, Edge)
  - [ ] Validar responsive en móvil

- [ ] **Testing**
  - [ ] Test manual: renderizado
  - [ ] Test manual: clic en asientos
  - [ ] Test manual: carrito
  - [ ] Test manual: checkout
  - [ ] Test de storage: recarga mantiene selección

- [ ] **Documentación**
  - [ ] Actualizar API docs
  - [ ] Comentar código
  - [ ] Crear guía de deployment

---

## Referencias

- Documento: `ESTADIO_VISUAL_V2.md`
- Archivo actual: `public/js/pages/editarSectoresEvento.js`
- Archivo actual: `public/js/pages/compra.js`
- CSS actual: `public/css/pages/stadium.css`, `compra.css`

---

**Fin del Documento**
