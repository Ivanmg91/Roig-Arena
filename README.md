# Roig-Arena
Description

puedo iniciar sail con : sail up, sail down...  ALIAS: sail

## Estructura del proyecto con Sail

Después de la instalación, tu proyecto tendrá:

arena/
├── docker-compose.yml    # Configuración de Docker
├── .env                  # Variables de entorno
├── vendor/
│   └── bin/
│       └── sail          # El comando sail
├── app/                  # Tu código Laravel
├── database/
└── ...

## Configuración de la base de datos

Sail configura automáticamente tu archivo .env con las credenciales correctas:

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=arena
DB_USERNAME=sail
DB_PASSWORD=password

## Comandos útiles de Sail
### Ejecutar comandos Artisan

sail artisan migrate
sail artisan make:model Post
sail artisan tinker

### Ejecutar comandos Composer

sail composer require laravel/sanctum
sail composer update

### Ejecutar comandos NPM

sail npm install
sail npm run dev
sail npm run build

### Ejecutar tests

sail test
sail test --filter NombreDelTest

### Acceder a la base de datos

sail mysql

O si usas PostgreSQL:

sail psql

### Ver logs en tiempo real

sail logs
sail logs -f  # Seguir los logs en tiempo real

### Ejecutar comandos dentro del contenedor

sail shell

Esto te da acceso a una terminal bash dentro del contenedor de PHP.
### Reiniciar servicios

sail restart


sail down
sail up -d

## Checklist: Crear una nueva tabla (ej: Artistas)

Cuando necesites crear una nueva tabla en la base de datos, debes hacer lo siguiente:

1. **Migración** - `database/migrations/`
   - Añadir al archivo de migración: `2026_03_17_083200_create_roig_arena_tables`
   - Definir columnas, tipos y relaciones (foreign keys)

2. **Modelo** - `app/Models/`
   - Crear modelo: `sail artisan make:model Artista`
   - Definir atributos en `$fillable`
   - Agregar relaciones (hasMany, belongsTo, etc.)

3. **Factory** - `database/factories/`
   - Crear factory: `sail artisan make:factory ArtistaFactory`
   - Definir datos fake para pruebas y seeding

4. **Seeder** - `database/seeders/`
   - Crear seeder: `sail artisan make:seeder ArtistaSeeder`
   - Llamar al seeder desde `DatabaseSeeder.php`
   - Poblar datos iniciales

5. **Resource** - `app/Http/Resources/`
   - Crear resource: `sail artisan make:resource ArtistaResource`
   - Formatear datos para respuestas JSON

6. **Controller** - `app/Http/Controllers/`
   - Crear controller: `sail artisan make:controller ArtistaController`
   - Implementar acciones: index, show, store, update, destroy, etc.

7. **Rutas** - `routes/api.php`
   - Registrar rutas públicas (GET)
   - Registrar rutas protegidas (POST, PUT, DELETE)

8. **Ejecutar migraciones**
   - `sail artisan migrate:fresh --seed` para reiniciar desde cero
   - O `sail artisan migrate` para aplicar solo nuevas migraciones
   - El script `arena.sh` lo hace todo

## Checklist: Editar una tabla existente

Cuando necesites agregar/modificar campos en una tabla existente:

1. **Migración** - Crear una nueva migración
   - `sail artisan make:migration add_campos_to_artistas_table`
   - O `sail artisan make:migration modify_campos_in_artistas_table`
   - Usar `$table->addColumn()` o `$table->modify()`
   - O modificar el archivo de migracion de tablas (si usarás arena.sh)

2. **Modelo** - `app/Models/Artista.php`
   - Agregar nuevos campos en `$fillable`
   - Actualizar casteos en `$casts` si es necesario
   - Agregar/modificar relaciones

3. **Factory** - `database/factories/ArtistaFactory.php`
   - Agregar generadores fake para los nuevos campos

4. **Seeder** - `database/seeders/ArtistaSeeder.php`
   - Actualizar datos que se insertan con los nuevos campos

5. **Resource** - `app/Http/Resources/ArtistaResource.php`
   - Agregar nuevos campos a la respuesta JSON
   - Usar `$this->when()` para mostrar campos condicionalmente

6. **Controller** - `app/Http/Controllers/ArtistaController.php`
   - Actualizar validaciones en `store()` y `update()`
   - Manejar nuevos datos en la lógica

7. **Ejecutar migración**
   - `sail artisan migrate` para aplicar los cambios

---

# GUÍA: Sistema Interactivo de Compra de Entradas con Mapa de Asientos

## Descripción General

Actualmente, el flujo de compra muestra una tabla de precios por sector. La nueva funcionalidad reemplazará esto con:
- **Visualización interactiva del estadio** con layout de asientos
- **Asientos seleccionables** que se añaden a un carrito de compra
- **Barra de pago flotante** que muestra las entradas seleccionadas
- **Feedback visual** del estado de cada asiento (disponible, reservado, seleccionado)

## Flujo de Usuario

```
1. Usuario entra en detalles del evento
2. Hace clic en "Comprar Entradas"
3. Se carga la vista de compra con:
   - Visualización del estadio (grilla de asientos)
   - Leyenda de estados (disponible, reservado, seleccionado)
   - Carrito flotante lateral con resumen de compra
4. Usuario hace clic en asientos disponibles
   - Los asientos se marcan como "seleccionados"
   - Se añaden al carrito con su precio
   - Se actualiza el total a pagar
5. Usuario visualiza su carrito lateral:
   - Lista de asientos seleccionados
   - Desglose de precios por sector
   - Total a pagar
   - Botón para proceder a pago
6. Usuario confirma la compra
   - Se reservan los asientos para el usuario
   - Se procesa el pago
   - Se generan las entradas
```

## Cambios en la Base de Datos

### 1. Tabla de Asientos (ya existe)
```sql
-- asientos TABLE
- id
- sector_id (foreign key)
- numero_fila
- numero_asiento
- estado_asiento_id (disponible, reservado, ocupado)
- created_at, updated_at
```

### 2. Tabla de Estado de Asientos (ya existe)
```sql
-- estado_asientos TABLE
- id
- nombre (disponible, reservado, ocupado)
```

### 3. Tabla de Sectores (ya existe con información que puede necesitar actualización)
Campos útiles para el layout visual:
```sql
-- sectores TABLE
- nombre
- descripcion
- cantidad_filas (número de filas del sector)
- cantidad_columnas (número de columnas del sector)
- color_hex (color visual en el mapa)
- activo (si en el evento se usa ese sector)
```

**Migración para actualizar Sectores:**
```
sail artisan make:migration add_layout_fields_to_sectores_table
```

Agregar campos:
- `cantidad_filas` (integer, default 10)
- `cantidad_columnas` (integer, default 15)
- `color_hex` (string, default '#F5300345')

### 4. Tabla de Reservas Temporales (nueva - OPCIONAL pero recomendada)
Para manejar "carritos" durante el flujo de compra:

```
sail artisan make:migration create_temporary_reservations_table
```

Campos:
- `id` (primary key)
- `user_id` (nullable - para usuarios anónimos con token)
- `evento_id` (foreign key)
- `asiento_id` (multiple rows per reservation)
- `carrito_session_id` (para identificar sesión de compra)
- `estado` (pendiente, confirmado, cancelado)
- `created_at`, `updated_at`
- `expires_at` (auto-liberar asientos si no se completa compra)

## Cambios en Modelos

### Actualizar Asiento.php
```php
// Agregar relaciones y métodos útiles
public function sector() { return $this->belongsTo(Sector::class); }
public function estadoAsiento() { return $this->belongsTo(EstadoAsiento::class); }

// Métodos helper
public function estaDisponible() { return $this->estado_asiento_id == EstadoAsiento::DISPONIBLE; }
public function estaReservado() { return $this->estado_asiento_id == EstadoAsiento::RESERVADO; }
public function estaOcupado() { return $this->estado_asiento_id == EstadoAsiento::OCUPADO; }
```

### Actualizar Sector.php
```php
// Agregar relación
public function asientos() { return $this->hasMany(Asiento::class); }

// Método para obtener asientos del sector organizados
public function obtenerAsientosOrganizados() { 
    return $this->asientos()
        ->orderBy('numero_fila')
        ->orderBy('numero_asiento')
        ->get()
        ->groupBy('numero_fila')
        ->toArray();
}

// Estadísticas del sector
public function contarDisponibles() { ... }
public function contarReservados() { ... }
public function contarOcupados() { ... }
```

### Crear Modelo ReservaTemporaria (si se implementa)
```
sail artisan make:model TemporaryReservation
```

## Cambios en API/Controllers

### Crear CompraController (si no existe)
```
sail artisan make:controller CompraController
```

**Métodos necesarios:**
1. `show($eventoId)` - Mostrar página de compra con datos del evento
2. `obtenerAsientos($eventoId)` - API endpoint para traer asientos por sector (JSON)
3. `obtenerAsientoDelSector($sectorId)` - API endpoint filtrado por sector
4. `agregarAlCarrito(Request $request)` - Añadir asiento al carrito temporal
5. `removerDelCarrito(Request $request)` - Remover asiento
6. `obtenerCarrito()` - Obtener estado actual del carrito
7. `confirmarCompra(Request $request)` - Procesar compra final

**Response Example para asientos:**
```json
{
  "evento": { "id", "nombre", "fecha", "hora" },
  "sectores": [
    {
      "id": 1,
      "nombre": "Sector VIP",
      "filas": 10,
      "columnas": 15,
      "color": "#F5300345",
      "asientos": [
        {
          "id": 1,
          "fila": 1,
          "columna": 1,
          "estado": "disponible",
          "precio": 150.50
        }
      ],
      "estadisticas": {
        "disponibles": 140,
        "reservados": 5,
        "ocupados": 5
      }
    }
  ]
}
```

## Cambios en Frontend

### 1. Nueva Vista: `resources/views/compra/seatmap.blade.php`

Estructura base:
```blade
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
        <div class="stadium-view" id="stadiumView">
            <!-- JavaScript generará los sectores aquí -->
        </div>
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
                <a href="#" class="btn btn-secondary">
                    Volver
                </a>
            </div>
        </div>
    </aside>
</div>
```

### 2. Estilos: `public/css/pages/seatmap.css`

```css
/* Layout General */
.seatmap-container {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 2rem;
    margin-top: 2rem;
}

/* MAPA DE ASIENTOS */
.seatmap-area {
    padding: 2rem;
    background: var(--color-bg-secondary);
    border-radius: 8px;
}

.stadium-view {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

/* Sector */
.sector-group {
    border: 1px solid var(--color-border);
    border-radius: 6px;
    padding: 1.5rem;
    background: var(--color-bg-tertiary);
}

.sector-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--color-text);
}

.sector-stats {
    font-size: 0.85rem;
    color: var(--color-text-muted);
    margin-bottom: 1rem;
}

/* Grid de Asientos */
.seats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(35px, 1fr));
    gap: 0.5rem;
}

/* Asiento Individual */
.seat {
    width: 35px;
    height: 35px;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    border: 2px solid transparent;
}

.seat-available {
    background: #4CAF50;
    color: white;
}

.seat-available:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
}

.seat-reserved {
    background: #999;
    color: white;
    cursor: not-allowed;
    opacity: 0.6;
}

.seat-selected {
    background: #2196F3;
    color: white;
    border-color: #1976D2;
    box-shadow: 0 0 8px rgba(33, 150, 243, 0.5);
}

.seat-occupied {
    background: #F44336;
    color: white;
    cursor: not-allowed;
    opacity: 0.5;
}

/* Leyenda */
.legend {
    display: flex;
    gap: 2rem;
    margin-bottom: 2rem;
    padding: 1rem;
    background: var(--color-bg-tertiary);
    border-radius: 6px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

/* CARRITO FLOTANTE */
.checkout-sidebar {
    position: sticky;
    top: 100px;
    background: var(--color-bg-secondary);
    border: 1px solid var(--color-border);
    border-radius: 8px;
    padding: 1.5rem;
    height: fit-content;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.checkout-header {
    border-bottom: 1px solid var(--color-border);
    padding-bottom: 1rem;
    margin-bottom: 1rem;
}

.checkout-header h3 {
    margin: 0;
    font-size: 1.1rem;
}

.seat-count {
    font-size: 0.85rem;
    color: var(--color-text-muted);
}

.selection-summary {
    margin-bottom: 1rem;
    max-height: 200px;
    overflow-y: auto;
}

.selected-item {
    padding: 0.5rem;
    background: var(--color-bg-tertiary);
    border-radius: 4px;
    margin-bottom: 0.5rem;
    font-size: 0.85rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.selected-item-remove {
    background: none;
    border: none;
    color: #F44336;
    cursor: pointer;
    font-size: 0.9rem;
}

.price-breakdown {
    border-top: 1px solid var(--color-border);
    border-bottom: 1px solid var(--color-border);
    padding: 1rem 0;
    margin: 1rem 0;
}

.price-line {
    display: flex;
    justify-content: space-between;
    font-size: 0.9rem;
    margin: 0.5rem 0;
}

.price-line-total {
    font-weight: 700;
    color: var(--color-accent);
    font-size: 1.1rem;
}

.total-section {
    text-align: center;
    margin: 1.5rem 0;
}

.total-label {
    font-size: 0.9rem;
    color: var(--color-text-muted);
    margin: 0;
}

.total-amount {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-accent);
    margin: 0.5rem 0 0;
}

.checkout-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.empty-state {
    text-align: center;
    color: var(--color-text-muted);
    padding: 2rem 1rem;
    font-size: 0.9rem;
}

/* Responsive */
@media (max-width: 1024px) {
    .seatmap-container {
        grid-template-columns: 1fr;
    }

    .checkout-sidebar {
        position: static;
    }
}
```

### 3. JavaScript: `public/js/seatmap.js`

```javascript
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
            const response = await fetch(`/api/eventos/${this.eventoId}/asientos`);
            this.data = await response.json();

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

        this.data.sectores.forEach(sector => {
            const sectorElement = this.createSectorElement(sector);
            stadiumView.appendChild(sectorElement);
        });
    }

    createSectorElement(sector) {
        const sectorDiv = document.createElement('div');
        sectorDiv.className = 'sector-group';
        sectorDiv.style.borderColor = sector.color;

        const title = document.createElement('div');
        title.className = 'sector-title';
        title.textContent = sector.nombre;

        const stats = document.createElement('div');
        stats.className = 'sector-stats';
        stats.innerHTML = `
            Disponibles: ${sector.estadisticas.disponibles} | 
            Reservados: ${sector.estadisticas.reservados}
        `;

        const grid = document.createElement('div');
        grid.className = 'seats-grid';

        // Crear matriz de asientos
        const asientosPorFila = {};
        sector.asientos.forEach(asiento => {
            if (!asientosPorFila[asiento.fila]) {
                asientosPorFila[asiento.fila] = [];
            }
            asientosPorFila[asiento.fila].push(asiento);
        });

        // Ordenar filas
        Object.keys(asientosPorFila)
            .sort((a, b) => parseInt(a) - parseInt(b))
            .forEach(fila => {
                asientosPorFila[fila]
                    .sort((a, b) => a.columna - b.columna)
                    .forEach(asiento => {
                        const seatElement = this.createSeatElement(asiento);
                        grid.appendChild(seatElement);
                    });
            });

        sectorDiv.appendChild(title);
        sectorDiv.appendChild(stats);
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
            const sector = this.data.sectores.find(s => s.id == sectorId);
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
```

## Checklist de Implementación

### Fase 1: Base de Datos (opcional si estructura ya existe)
- [ ] Ejecutar migración para agregar campos a `sectores` (filas, columnas, color)
- [ ] Verificar que tabla `asientos` está correctamente poblada
- [ ] Ejecutar seeders para generar datos de asientos de prueba

### Fase 2: Backend
- [ ] Crear/actualizar `CompraController`
- [ ] Crear endpoints API:
  - [ ] `GET /api/eventos/{id}/asientos` - Traer asientos con datos
  - [ ] `POST /api/compra/confirmar` - Procesar compra
  - [ ] `GET /api/compra/carrito` - Estado actual del carrito
- [ ] Actualizar modelos (Asiento, Sector)
- [ ] Crear rutas en `routes/api.php`

### Fase 3: Frontend
- [ ] Crear vista `resources/views/compra/seatmap.blade.php`
- [ ] Crear archivo CSS `public/css/pages/seatmap.css`
- [ ] Crear archivo JS `public/js/seatmap.js`
- [ ] Reemplazar botón de compra en vista de evento para apuntar a nueva ruta

### Fase 4: Actualizar Rutas
- [ ] Modificar ruta `compra.buy` para apuntar a nueva vista con seatmap
- [ ] O crear nueva ruta si se prefiere mantener separada

### Fase 5: Testing
- [ ] Verificar que asientos se cargan correctamente
- [ ] Probar interacción: clic en asiento → selección visual
- [ ] Probar carrito: actualización de totales
- [ ] Probar localStorage: guardar/cargar carrito
- [ ] Probar confirmación de compra
- [ ] Prueba responsive en móvil/tablet

## Notas y Consideraciones

1. **Reservas Temporales**: Para sistemas con alta concurrencia, considera implementar tabla de `TemporaryReservations` para bloquear asientos por 10-15 minutos

2. **Validación Backend**: Siempre validar que los asientos seleccionados estén disponibles antes de confirmar

3. **Optimización de Carga**: Para eventos muy grandes (1000+ asientos), considera pagination o lazy-loading por sector

4. **Diseño Responsive**: El mapa puede necesitar ajustarse para móvil (grid responsive, sidebar debajo)

5. **Cancelación**: Implementar lógica para liberar asientos si el usuario no completa la compra en X minutos

6. **Accesibilidad**: Asegurar que asientos sean navegables con teclado (Tab, Enter)

---

# Pendiente
- Q no se vean las cards de eventos gigantes si solo hay 1 o 2




FRONTEND (JavaScript/Vue/React)
    ↓ (Hace petición HTTP con Axios)
GET http://localhost:8000/api/eventos
    ↓
SERVIDOR LARAVEL
    ├─ Route: /api/eventos
    ├─ EventoController@index
    ├─ Service + Models (acceso BD)
    ├─ Resource (transforma a JSON)
    ↓
RESPUESTA JSON
{
  "data": [
    {
      "id": 1,
      "nombre": "Concierto",
      "fecha": "25/03/2026",
      ...
    }
  ]
}
    ↓
FRONTEND recibe el JSON y lo renderiza en pantalla