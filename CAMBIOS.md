# CAMBIOS

## Proceso implementado: Pagos pendientes con modal flotante

### Objetivo
Permitir que un usuario autenticado vea sus entradas pendientes de pago y pueda pagarlas desde un modal con countdown.

### 1. Ruta web para la pantalla de pagos pendientes
Se usa la ruta web autenticada para mostrar la vista:

- `GET /mis-pagos-pendientes` -> `CompraController@misPagosPendientes`

Resultado: el usuario accede a una página con sus eventos agrupados por pago pendiente.

### 2. Método `misPagosPendientes()` en `CompraController`
En backend se implementó este flujo:

1. Obtener usuario autenticado.
2. Buscar entradas del usuario con `precio_pagado = 0`.
3. Cargar relaciones necesarias: `evento`, `asiento`, `sector`.
4. Agrupar por evento.
5. Para cada entrada, calcular precio usando la tabla `precios` (combinación `evento_id + sector_id`).
6. Preparar estructura para la vista:
   - Evento
   - Entradas pendientes
   - Cantidad
   - Monto total pendiente
7. Retornar la vista `compra.pagos-pendientes`.

Resultado: la vista recibe datos listos para pintar tarjetas por evento.

### 3. Vista `pagos-pendientes.blade.php`
Se creó una vista con:

1. Header informativo.
2. Estado vacío cuando no hay pagos pendientes.
3. Tarjeta por evento con:
   - Nombre y fecha
   - Monto pendiente total
   - Lista de entradas (fila/asiento, sector, precio)
4. Botón `Pagar Ahora` por evento.
5. Modal flotante con:
   - Resumen del evento y entradas
   - Formulario simulado de tarjeta
   - Countdown
   - Botón final de pago

Resultado: al pulsar `Pagar Ahora`, se abre un modal específico del evento seleccionado.

### 4. Lógica JavaScript del modal
Dentro de la vista se añadió JS para:

1. Abrir modal con datos del evento clicado.
2. Renderizar entradas y total en el resumen del modal.
3. Ejecutar countdown de tiempo de pago.
4. Cerrar modal por botón o clic en overlay.
5. Aplicar formato de campos de tarjeta (`numero`, `caducidad`, `cvv`).
6. Ejecutar petición de pago al endpoint API.

Resultado: experiencia de pago interactiva por cada evento pendiente.

### 5. Endpoint API para confirmar pago pendiente
Se agregó ruta protegida en API:

- `POST /api/entradas/pago-pendiente` -> `CompraController@procesarPagoPendiente`

#### Payload esperado
```json
{
  "entradas_ids": [1, 2, 3],
  "metodo_pago": "tarjeta"
}
```

### 6. Método `procesarPagoPendiente()` en `CompraController`
Proceso backend:

1. Validar `entradas_ids` y `metodo_pago`.
2. Verificar que las entradas:
   - Existan
   - Sean del usuario autenticado
   - Sigan con `precio_pagado = 0`
3. Para cada entrada, recalcular precio según `evento + sector`.
4. Actualizar `precio_pagado` en la tabla `entradas`.
5. Devolver respuesta JSON con:
   - `success`
   - `message`
   - `cantidad`
   - `total`

Resultado: las entradas dejan de estar pendientes y desaparecen de la pantalla tras recarga.

### 7. Estilos CSS de la pantalla
Se creó hoja de estilos dedicada:

- `public/css/pages/pagos-pendientes.css`

Incluye estilos para:

1. Grid de tarjetas.
2. Tarjetas de evento y listado de entradas.
3. Modal, formulario y timer.
4. Comportamiento responsive para móvil/tablet.

Resultado: interfaz consistente con el resto del proyecto y usable en móvil.

### 8. Verificación manual recomendada
Pasos de prueba:

1. Iniciar sesión con usuario que tenga entradas con `precio_pagado = 0`.
2. Abrir `/mis-pagos-pendientes`.
3. Pulsar `Pagar Ahora` en un evento.
4. Comprobar apertura de modal y countdown.
5. Confirmar pago.
6. Verificar respuesta exitosa y recarga de la vista.
7. Confirmar en base de datos que `precio_pagado` se actualizó.
8. Confirmar que el evento pagado deja de aparecer como pendiente.

## Notas técnicas

- El cálculo de importe se hace en backend para no confiar en precios enviados desde frontend.
- El endpoint de pago pendiente está protegido por autenticación sanctum en API.
- El flujo actual es de "simulación de pago" en frontend; la validación real de pasarela no está integrada.
