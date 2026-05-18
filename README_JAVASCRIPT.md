# README JavaScript - Roig Arena

Este documento resume **solo los archivos JavaScript** del proyecto y explica, de forma práctica, qué hace cada uno y qué funciones/lógica destacan.

## 1) Configuración y entrada de frontend

### `roig-arena/vite.config.js`
- Configura Vite para Laravel.
- Define entradas de compilación: `resources/css/app.css` y `resources/js/app.js`.
- Activa `refresh` para recarga en desarrollo y ajusta `server.watch.ignored` para no vigilar vistas compiladas.

### `roig-arena/resources/js/bootstrap.js`
- Inicializa Axios en `window.axios` para usarlo globalmente en peticiones HTTP.

### `roig-arena/resources/js/app.js`
- Punto de entrada mínimo del JS de `resources`.
- Importa `./bootstrap` para cargar la configuración base.

## 2) Scripts de páginas (`public/js/pages`)

### `app.js`
- En `DOMContentLoaded`, lee `sanctum_user` desde `localStorage`.
- Si hay sesión válida, muestra el nombre del usuario en navegación y oculta el enlace de login.

### `login.js`
- Gestiona el formulario de login por `fetch`.
- Funciones/lógica clave:
  - `clearMessages()`: limpia errores generales y por campo.
  - `setBusy(isBusy)`: bloquea/desbloquea botón de envío.
  - `getRedirectTo()`: calcula redirección segura post-login.
  - `showFieldErrors(errors)`: pinta errores de validación.

### `register.js`
- Gestiona registro con envío JSON por `fetch`.
- Funciones/lógica clave similares a login:
  - `clearMessages()`
  - `setBusy(isBusy)`
  - `getRedirectTo()`
  - `showFieldErrors(errors)`
- Guarda `sanctum_token` y `sanctum_user` al completar registro.

### `compra.js`
- Implementa el flujo completo de selección de asientos, reserva temporal y pago.
- Clase principal: `SeatMapManager`.
- Funciones/métodos clave:
  - `init()`: carga datos de evento y asientos, renderiza mapa y listeners.
  - `loadEventoData()` / `loadAllSeats()`: obtiene datos desde API.
  - `renderSeatMap()`, `drawGridLines()`, `drawSectorBackgrounds()`, `drawSeatNodes()`: dibujan el mapa SVG.
  - `toggleSeat()`, `updateCart()`, `updateTotal()`: controlan selección y carrito.
  - `proceedToCheckout()`: crea reservas.
  - `openPaymentModal()` + `startCountdown()`: abre modal de pago con temporizador de expiración.
  - `confirmPayment()`: confirma compra en backend.
  - `cancelPayment()` / `handleReservationExpiration()`: cancela reservas y resetea UI.

### `downloadpdf.js`
- Gestiona descarga de entradas en PDF y cancelación de compras desde la tarjeta de ticket.
- Funciones clave:
  - `toDataUrl(url)`: convierte imagen remota (QR) a DataURL para `jsPDF`.
  - `sanitizeName(value)`: limpia nombre de evento para nombre de archivo.
  - `getRequestHeaders()`: genera cabeceras con CSRF y Bearer token.
- Flujo:
  - Marca entrada como descargada vía API.
  - Genera PDF con datos del ticket y QR.
  - Permite cancelar compra (`DELETE /api/entradas/{id}/cancelar`).

### `updateFieldText.js`
- Componente reutilizable de edición inline para campos de texto/fecha/hora.
- Función principal: `initInlineEditor(options)`.
- Funciones/lógica clave internas:
  - `normalizeDateValue()` y `formatDateForDisplay()` para fechas.
  - `openEditor()` / `closeEditor()` para alternar vista y formulario.
  - Envío `fetch` con `_method = PATCH` para actualizar sin recargar.
- Se inicializa en varios bloques: título, descripciones, fecha y hora del evento.

### `updateFieldPrice.js`
- Edición inline específica del precio de sector.
- Funciones clave:
  - `formatPrice(value)`: formato monetario `es-ES`.
  - `initPriceInlineEditors()`: inicializa cada editor de precio.
  - `openEditor()` / `closeEditor()` (internas por formulario).
- Actualiza el precio visual tras respuesta correcta del backend.

### `multiDelete.js`
- Controla selección múltiple de precios/sectores y borrado masivo.
- Funciones clave:
  - `setRowActionsState(checkbox, isEnabled)`: habilita/deshabilita acciones por fila.
  - `getSelectedPrecioIds()`: devuelve IDs seleccionados.
  - `syncBulkActions(checkboxes)`: muestra/oculta acciones globales.
  - `initMultiDeleteUI()`: inicializa checkboxes, eventos y borrado masivo.
- Expone API global: `window.multiDelete.getSelectedPrecioIds`.

### `posterEditor.js`
- Modal para editar URLs de póster (`poster_url`, `poster_ancho_url`) de un evento.
- Funciones clave:
  - `openModal(targetField)`: abre modal y precarga valores actuales.
  - `applyPreview(fieldName, value)`: refresca vista del póster en caliente.
  - `closeModal()`: cierra modal.
- Guarda cambios por `fetch` (PATCH simulado) y actualiza `dataset` local.

### `popUps.js`
- Modal para buscar artistas y añadir/borrar artistas desde la vista de evento.
- Funciones clave:
  - `openModal()` / `closeModal()`
  - `fetchArtistas(q)` y `renderList(artistas)`
  - `onAddArtista(e)` para asociar artista al evento.
  - `onDeleteArtista(e)` para borrar artista desde catálogo.
  - `appendArtistCardToPage()` y `removeArtistCardFromPage()` para reflejar cambios sin recargar.
  - `escapeHtml(text)` para evitar inyección al pintar contenido dinámico.

### `popUpSectores.js`
- Modal para buscar sectores y asociarlos al evento con precio.
- Funciones clave:
  - `openModal()` / `closeModal()`
  - `fetchSectores(q)` y `renderList(sectores)`
  - `onAddSector(e)`: pide precio, asocia sector y actualiza tabla de precios.
  - `appendSectorRowToTable(precio, sourceRow)`: inserta nueva fila con edición/borrado/checkbox.
  - `removeSectorRowFromTable(sectorId)` y `formatPrice(value)`.
  - `escapeHtml(text)` para pintar datos seguros.
- Reinvoca inicializadores globales (`initPriceInlineEditors`, `initMultiDeleteUI`) tras insertar filas.

### `editarSectoresEvento.js`
- Editor visual de sectores sobre mapa SVG (selección rectangular por asientos).
- Funciones clave:
  - `initSectorMapEditor()`: inicialización general.
  - `drawGrid()` y `drawSectorBackgrounds()`: dibujado de mapa y sectores.
  - `onSeatClick()`, `refreshSeatClasses()`, `updateSummary()`: gestión de selección.
  - `saveSector()`: guarda nuevo sector en backend y redibuja.
  - `showActionPopup()` / `hideActionPopup()` + `deleteSector(sectorId)` para acciones del popup.

## 3) Resumen rápido de responsabilidades

- **Autenticación y sesión UI**: `app.js`, `login.js`, `register.js`.
- **Compra y entradas**: `compra.js`, `downloadpdf.js`.
- **Edición inline de evento/precios**: `updateFieldText.js`, `updateFieldPrice.js`, `posterEditor.js`.
- **Gestión de sectores/artistas**: `popUpSectores.js`, `popUps.js`, `editarSectoresEvento.js`, `multiDelete.js`.
- **Build/arranque frontend**: `vite.config.js`, `resources/js/bootstrap.js`, `resources/js/app.js`.
