# Estadio Visual v2: Distribución de Asientos Controlada

## Resumen Ejecutivo

**Estadio Visual v2** es una actualización del sistema de sectores y asientos de Roig Arena que permite al administrador **definir sectores como matrices exactas (filas × columnas) con posiciones fijas**, eliminando la posibilidad de asientos sueltos sin sector y proporcionando una **experiencia de compra visual basada en rejillas numeradas**.

En lugar de que el admin solo añada sectores precreados de un catálogo, podrá **crear sectores nuevos dinámicamente** definiendo su geometría. La compra de entradas cambia: en lugar de mostrar asientos en lista, los muestra en una **rejilla interactiva** donde el usuario puede seleccionar asientos individuales o hacer selección rectangular (arrastrando para seleccionar un bloque).

---

## Problema que Resuelve

### Estado Actual (v1)
- ✗ Los sectores son estáticos, preexisten en un catálogo global
- ✗ No hay control sobre la distribución física en el mapa
- ✗ Posible tener asientos "sueltos" sin sector si la matriz no es perfecta  
- ✗ La experiencia de compra es poco intuitiva (lista de asientos sin visualización espacial)
- ✗ El admin no puede iterar rápidamente sobre la geometría del estadio

### Solución v2
- ✓ Los sectores se crean bajo demanda con parámetros simples (filas, columnas, color, precio)
- ✓ Matriz perfecta = nunca hay asientos sueltos (si el sector es 3×4, exactamente 12 asientos)
- ✓ La distribución es **editable y reutilizable** por evento
- ✓ Experiencia de compra visual: rejilla de asientos numerados, selección por click o rectángulo
- ✓ Velocidad de iteración: cambiar un sector toma segundos

---

## Arquitectura Conceptual

### Nivel de Datos

```
Sector (actualizado)
├── id
├── nombre
├── descripcion
├── cantidad_filas         ← Mantiene esto
├── cantidad_columnas      ← Mantiene esto
├── color_hex
├── activo
└── (NEW) posicion_x       ← Opcional: para futuros layouts gráficos
└── (NEW) posicion_y       ← Opcional: para futuros layouts gráficos

Asiento (sin cambios en estructura)
├── id
├── sector_id
├── fila                   ← Letra o número (A, B, C, etc.)
├── numero                 ← 1, 2, 3, etc.

Precio (sin cambios)
├── evento_id
├── sector_id
├── precio
├── disponible

EstadoAsiento (sin cambios)
├── evento_id
├── asiento_id
├── estado (DISPONIBLE, RESERVADO, OCUPADO)
└── reservado_hasta
```

### Flujo de Usuario: Admin (Creación de Evento)

```
1. Admin crea Evento
   └─> Accede a "Gestionar Sectores"

2. Modal/Editor de Sectores (NUEVO)
   └─> Botón "+ Crear Sector"
       └─> Form:
           - Nombre: "Zona Roja Premium"
           - Filas: 5
           - Columnas: 8
           - Color: #FF5733
           - Descripción: (opcional)
   └─> Backend: genera 40 asientos (5×8) automáticamente
       Filas: A, B, C, D, E
       Columnas: 1, 2, 3, 4, 5, 6, 7, 8
   └─> Asientos creados: A1, A2, ..., A8, B1, ..., E8

3. Admin define precios (igual que hoy)
   └─> Tabla: Sector | Precio | Estado | Acciones
       └─> Editar precio del sector

4. Button "Guardar Cambios" persiste todo
```

### Flujo de Usuario: Comprador (Selección de Asientos)

```
1. Accede a "/eventos/{evento}/comprar"
   
2. Vista "Entra al Estadio" - Mapa de Sectores (igual que hoy)
   └─> SVG con sectores dibujados
   └─> Click en un sector → carga rejilla de asientos

3. (NUEVO) Rejilla de Asientos del Sector
   ┌─────────────────────────────────┐
   │  Zona Roja Premium              │
   │                                 │
   │   1  2  3  4  5  6  7  8        │
   │ A ☐  ☐  ☑  ☑  ☐  ☐  ☐  ☐ A    │
   │ B ☐  ☐  ☐  ☐  ☐  ☐  ☐  ☐ B    │
   │ C ☑  ☐  ☐  ☐  ☐  ☑  ☐  ☐ C    │
   │ D ☐  ☐  ☐  ☐  ☐  ☐  ☐  ☐ D    │
   │ E ☐  ☐  ☐  ☐  ☐  ☐  ☐  ☐ E    │
   └─────────────────────────────────┘
   
   Leyenda:
   ☐ = Disponible (click para seleccionar)
   ☑ = Seleccionado (click para deseleccionar)
   ▌ = Ocupado/Reservado (gris, no clickable)

4. Formas de Selección
   a) Click individual: A3 → se selecciona
   b) Selección por rectángulo (NUEVO)
      - Click en A3 + Drag a C6 
      → selecciona A3, A4, A5, A6, B3, B4, B5, B6, C3, C4, C5, C6
      → útil para familias o grupos

5. Carrito Flotante (igual que hoy)
   └─> Resumen de selección
   └─> Total por sector
   └─> Total general
   └─> Botón "Confirmar Compra"

6. Checkout (sin cambios)
   └─> Modal de pago
   └─> Temporizador de reserva
```

---

## Componentes Técnicos a Modificar

### 1. Backend - Base de Datos

#### Migrations
- ✓ Tabla `sectores` ya tiene `cantidad_filas` y `cantidad_columnas`
- ~ **OPCIONAL**: Agregar `posicion_x`, `posicion_y` para futuras capas visuales de admin
- ✓ Tabla `asientos` ya tiene la estructura necesaria
- ✓ Tabla `precios` sin cambios
- ✓ Tabla `estado_asientos` sin cambios

**Acción**: Considerar migración opcional para posiciones (puede hacerse después de v2 MVP).

---

### 2. Backend - Lógica

#### Modelo `Sector.php`
```php
// NUEVO: Método para generar/validar asientos automáticamente
public function generarAsientosDesdeMatriz()
{
    // Elimina asientos antiguos (o marca como inactivos)
    $this->asientos()->delete();
    
    // Genera nuevos según filas × columnas
    $asientos = [];
    $filas = $this->obtenerLetrasFilas($this->cantidad_filas);
    
    foreach ($filas as $fila) {
        for ($col = 1; $col <= $this->cantidad_columnas; $col++) {
            $asientos[] = [
                'sector_id' => $this->id,
                'fila' => $fila,
                'numero' => $col,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
    }
    
    Asiento::insert($asientos);
}

// Helper para convertir número a letra (1→A, 2→B, etc.)
private function obtenerLetrasFilas($cantidad): array
{
    return array_map(fn($i) => chr(65 + $i), range(0, $cantidad - 1));
}
```

#### Controlador `EventoController.php` o nuevo `SectorEventoController.php`
```php
// NUEVO endpoint para crear sector dentro de evento
POST /admin/eventos/{eventoId}/sectores
{
    "nombre": "Zona Roja Premium",
    "cantidad_filas": 5,
    "cantidad_columnas": 8,
    "color_hex": "#FF5733",
    "descripcion": "Primera fila con mejor vista",
    "precio": 45.50
}

// Lógica:
// 1. Crear Sector (cantidad_filas, cantidad_columnas, color)
// 2. Llamar sector->generarAsientosDesdeMatriz()
// 3. Crear Precio(evento_id, sector_id, precio, disponible=true)
// 4. Retornar sector + precios + asientos como JSON
```

#### API Endpoint Existente (adaptado)
```php
GET /api/eventos/{eventoId}/sectores/{sectorId}/asientos

// AHORA retorna asientos organizados por matriz
// Con estado (disponible, reservado, ocupado)
{
    "data": {
        "sector": {
            "id": 5,
            "nombre": "Zona Roja",
            "cantidad_filas": 5,
            "cantidad_columnas": 8,
            "color_hex": "#FF5733"
        },
        "matriz": {  // NUEVO
            "filas": ["A", "B", "C", "D", "E"],
            "columnas": [1, 2, 3, 4, 5, 6, 7, 8]
        },
        "asientos": [
            {
                "id": 100,
                "fila": "A",
                "numero": 1,
                "estado": "disponible"
            },
            ...
        ]
    }
}
```

---

### 3. Frontend - Vista de Admin

#### Nueva Vista: `resources/views/eventos/sectores-editor.blade.php`

Reemplaza o amplía el modal actual de `popUpSectores.js`.

**Componentes:**
- Tabla de sectores existentes con opciones de editar/eliminar
- Form "Crear Nuevo Sector":
  - Input: Nombre
  - Input: Filas (1-20)
  - Input: Columnas (1-30)
  - Input: Color (color picker)
  - Input: Descripción (opcional)
  - Button: "Generar Asientos"

**JavaScript: `public/js/pages/editarSectoresEvento.js`**
```js
class SectorEventoEditor {
    constructor(eventoId) {
        this.eventoId = eventoId;
        this.sectores = [];
    }

    crearSector(datos) {
        // POST /admin/eventos/{eventoId}/sectores
        // Backend genera matriz automáticamente
    }

    editarSector(sectorId, nuevosDatos) {
        // PATCH /admin/eventos/{eventoId}/sectores/{sectorId}
        // Si cambian filas/columnas, regenera asientos
    }

    eliminarSector(sectorId) {
        // DELETE /admin/eventos/{eventoId}/sectores/{sectorId}
    }

    actualizarPrecio(sectorId, nuevoPrecio) {
        // PATCH /admin/precios/{precioId}
        // (ya existe, sin cambios)
    }
}
```

---

### 4. Frontend - Experiencia de Compra

#### Componente: `public/js/components/SeatGrid.js` (NUEVO)

```js
class SeatGrid {
    constructor(containerSelector, sector, asientos) {
        this.container = document.querySelector(containerSelector);
        this.sector = sector;
        this.asientos = asientos;
        this.selectedSeats = new Set();
        this.selectionMode = 'individual'; // o 'rectangle'
    }

    render() {
        // Genera HTML grid
        const grid = document.createElement('div');
        grid.className = 'seats-grid-matrix';
        grid.style.gridTemplateColumns = `repeat(${this.sector.cantidad_columnas}, 1fr)`;

        // Renderiza cada asiento
        this.asientos.forEach(asiento => {
            const seatEl = this.crearElementoAsiento(asiento);
            grid.appendChild(seatEl);
        });

        this.container.appendChild(grid);
    }

    crearElementoAsiento(asiento) {
        const seat = document.createElement('button');
        seat.className = 'seat seat-' + asiento.estado;
        seat.textContent = asiento.numero;
        seat.dataset.seatId = asiento.id;
        seat.dataset.fila = asiento.fila;
        seat.dataset.numero = asiento.numero;

        if (asiento.estado === 'disponible') {
            seat.addEventListener('click', () => this.toggleSeat(asiento));
            seat.addEventListener('mousedown', () => this.startRectangleSelection(asiento));
        }

        return seat;
    }

    toggleSeat(asiento) {
        const id = String(asiento.id);
        if (this.selectedSeats.has(id)) {
            this.selectedSeats.delete(id);
        } else {
            this.selectedSeats.add(id);
        }
        this.updateVisuals();
    }

    startRectangleSelection(startSeat) {
        // Guarda point inicial
        // En mousemove, calcula rectángulo
        // En mouseup, selecciona todos los asientos del rectángulo
    }

    updateVisuals() {
        document.querySelectorAll('.seat').forEach(el => {
            const id = el.dataset.seatId;
            el.classList.toggle('seat-selected', this.selectedSeats.has(id));
        });
    }
}
```

#### Adaptación: `public/js/pages/compra.js`

En `renderSectorSeats()`, cambiar de lista lineal a rejilla:

```js
async renderSectorSeats(sector) {
    // ... fetch asientos ...
    
    const container = document.getElementById('sectorSeats');
    container.innerHTML = '';
    
    // NUEVO: Usar SeatGrid en lugar de crear elementos sueltos
    const grid = new SeatGrid('#sectorSeats', sector, asientos);
    grid.render();
    
    // Interconectar con carrito
    grid.onSelectedSeatsChange = (selectedSeats) => {
        this.selectedSeats = selectedSeats;
        this.updateCart();
    };
}
```

#### CSS: `public/css/components/seat-grid.css` (NUEVO)

```css
.seats-grid-matrix {
    display: grid;
    gap: 8px;
    padding: 1rem;
    max-width: 500px;
    align-items: center;
    justify-items: center;
}

.seat {
    width: 40px;
    height: 40px;
    border-radius: 4px;
    border: 1px solid #ccc;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.8rem;
    font-weight: 600;
}

.seat-disponible {
    background: #1e4d38;
    border-color: #2a6650;
    color: #b8dece;
}

.seat-disponible:hover {
    transform: scale(1.15);
    box-shadow: 0 0 0 2px rgba(42, 102, 80, 0.6);
}

.seat-selected {
    background: #ff5733;
    border-color: #ff5733;
    color: white;
    box-shadow: 0 0 8px rgba(255, 87, 51, 0.4);
}

.seat-ocupado,
.seat-reservado {
    background: #4a4a47;
    border-color: #6b6b68;
    color: #8a8a84;
    cursor: not-allowed;
    opacity: 0.6;
}

/* Etiquetas de fila y columna */
.seats-grid-matrix::before {
    content: '';
    grid-column: 1; /* En la primera columna */
}
```

---

### 5. Frontend - Cambios en Vistas Blade

#### `resources/views/eventos/show.blade.php`

- **Reemplazar** el modal `#sector-modal` actual por una nueva sección de edición
- O agregar un tab/botón "Editor Visual" que abra el nuevo editor
- Mantener la tabla de precios (casi igual, pero ahora actualiza automáticamente al crear sector)

#### `resources/views/compra/buy.blade.php`

- El container `#sectorSeats` ahora recibirá markup generado por `SeatGrid`, no por templates Blade
- **Sin cambios mayores**: el layout sigue siendo igual, solo cambia cómo se pintan los asientos

---

## Lista de Cambios Detallados

### Fase 1: Backend (Datos + Lógica)

- [ ] **Modelo `Sector.php`**
  - [ ] Agregar método `generarAsientosDesdeMatriz()`
  - [ ] Agregar helper `obtenerLetrasFilas()`
  - [ ] Validações: `cantidad_filas` y `cantidad_columnas` entre límites razonables (1-30)

- [ ] **Controlador (crear o ampliar)**
  - [ ] Ruta POST `/admin/eventos/{eventoId}/sectores` - crear sector
  - [ ] Ruta PATCH `/admin/eventos/{eventoId}/sectores/{sectorId}` - editar sector + regenerar asientos
  - [ ] Ruta DELETE `/admin/eventos/{eventoId}/sectores/{sectorId}` - borrar sector
  - [ ] Lógica: al crear/editar, llamar `generarAsientosDesdeMatriz()`

- [ ] **API Controller (adaptar existente)**
  - [ ] GET `/api/eventos/{eventoId}/sectores/{sectorId}/asientos`
    - [ ] Retornar también `matriz` con filas/columnas para que front sepa dibujar grid
    - [ ] Asimismo retornar `estado` de cada asiento (disponible, reservado, ocupado)

- [ ] **Rutas**
  - [ ] Registrar nuevas rutas en `routes/web.php` (admin)
  - [ ] Rutas API ya existen, adaptar respuestas

---

### Fase 2: Frontend - UI de Admin

- [ ] **Nueva vista**: `resources/views/eventos/sectores-editor.blade.php`
  - [ ] Form para crear sector (nombre, filas, columnas, color, descripción)
  - [ ] Tabla de sectores existentes
  - [ ] Botones: Editar, Eliminar, Previsualizar

- [ ] **Nuevo JS**: `public/js/pages/editarSectoresEvento.js`
  - [ ] Clase `SectorEventoEditor`
  - [ ] Métodos: crear, editar, eliminar, actualizarPrecio
  - [ ] Validación cliente (filas/columnas dentro de rangos)
  - [ ] Feedback visual (loading, error, success)

- [ ] **Actualizar**: `resources/views/eventos/show.blade.php`
  - [ ] Reemplazar modal antiguo o crear nueva sección
  - [ ] Link a editar sectores (modal o página nueva)
  - [ ] Tabla de precios: mantener como está (el precio se liga al sector igual que hoy)

---

### Fase 3: Frontend - UI de Compra

- [ ] **Nuevo componente**: `public/js/components/SeatGrid.js`
  - [ ] Clase `SeatGrid` con métodos: `render()`, `crearElementoAsiento()`, `toggleSeat()`, `startRectangleSelection()`, `updateVisuals()`
  - [ ] Gestionar estado de selección internally
  - [ ] Emitir evento cuando cambia selección (para actualizar carrito)

- [ ] **Nuevo CSS**: `public/css/components/seat-grid.css`
  - [ ] Estilos para grid de asientos
  - [ ] Estados: disponible, seleccionado, ocupado, reservado
  - [ ] Hover effects, transiciones suaves

- [ ] **Adaptar**: `public/js/pages/compra.js`
  - [ ] Modificar `renderSectorSeats()` para usar `SeatGrid` en lugar de pintar elementos sueltos
  - [ ] Pasar asientos ordenados por matriz (ya vienen de API con fila/número)
  - [ ] Mantener lógica de `toggleSeat()`, `updateCart()`, etc. sin cambios mayores

- [ ] **Adaptar**: `resources/views/compra/buy.blade.php`
  - [ ] El container `#sectorSeats` recibe markup de JS, no de Blade templates
  - [ ] Sin cambios de estructura HTML, el JS genera el grid dinámicamente

---

### Fase 4: Validación y Pruebas

- [ ] **Pruebas de unidad** (Backend)
  - [ ] `generarAsientosDesdeMatriz()` crea exactamente `filas × columnas` asientos
  - [ ] Asientos numerados correctamente (A1, A2, ..., ZZ99)
  - [ ] Validaciones de límites (no < 1, no > 30)

- [ ] **Pruebas de integración** (API)
  - [ ] POST crear sector → retorna sector + precios iniciales
  - [ ] GET asientos del sector → retorna matriz con estados

- [ ] **Pruebas de UI** (Frontend)
  - [ ] Grid se renderiza con correcta cantidad de columnas
  - [ ] Click en asiento cambia estado visual
  - [ ] Selección rectangular selecciona rango correcto
  - [ ] Carrito actualiza con asientos seleccionados
  - [ ] Checkout no se rompe, reservas y compra funcionan

---

### Fase 5: Documentación y Deploy

- [ ] Actualizar documentos existentes (si los hay)
- [ ] Crear guía para admins: "Cómo crear y gestionar sectores"
- [ ] Notas de release
- [ ] Considera migration path: ¿qué pasa con sectores antiguos? (mantener compatibilidad o migrar)

---

## Ventajas Clave de v2

| Aspecto | v1 (Actual) | v2 (Propuesta) |
|--------|-----------|-------------|
| **Creación de Sectores** | Catálogo global precreado | Dinámico, bajo demanda |
| **Control de Distribución** | Limitado a una matriz predefinida | Ajustable (filas, cols) por evento |
| **Asientos Sueltos** | Posible si matriz no es perfecta | Imposible (siempre matriz completa) |
| **UX Compra** | Lista de asientos | Rejilla numerada visual |
| **Selección Múltiple** | Click individual | Click o rectángulo |
| **Velocidad de Iteración** | Lenta (crear sector global) | Rápida (crear por evento) |
| **Reusabilidad** | Baja (sectores globales) | Media (puedes guardar "plantillas") |

---

## Consideraciones de Compatibilidad

- **Datos existentes**: Los sectores antiguos siguen funcionando (tienen filas/columnas)
- **Compras en progreso**: No afectadas (se venden asientos igual que hoy)
- **APIs**: Nuevos endpoints, respuestas ampliadas (backward compatible si usas `.data.sectores` como antes)
- **Migraciones**: Mínimas, no destructivas

---

## Roadmap Futuro (Después de v2)

1. **v2.1**: Agregar posiciones XY para edición visual del estadio (drag & drop de sectores en mapa)
2. **v2.2**: Plantillas de sectores reutilizables entre eventos
3. **v3**: Editor visual WYSIWYG (dibuja el estadio en UI)
4. **v3+**: Soporte para formas irregulares, rotación de sectores

---

## Conclusión

**Estadio Visual v2** es una mejora directa al flujo actual que:
- Elimina la fricción de crear eventos (gestión de sectores in-situ)
- Mejora la UX de compra (visualización espacial)
- Previene errores (matrices exactas, sin asientos sueltos)
- Mantiene compatibilidad (casi ningún cambio destructivo)

El effort es **medio-bajo** porque reutiliza la infraestructura existente de asientos y simplemente la mejora en presentación y flujo.

