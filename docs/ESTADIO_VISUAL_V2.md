# Estadio Visual v2: editor rectangular de sectores y mapa de asientos

## Resumen Ejecutivo

Este documento redefine la actualización de Roig Arena para que el sistema de sectores sea realmente utilizable por un administrador sin obligarlo a trabajar con catálogos rígidos. El objetivo es que el admin pueda abrir un mapa visual del estadio, pulsar un asiento como inicio, pulsar otro como fin y que el área rectangular entre ambos se convierta en un sector.

El cambio clave es este: un sector deja de ser solo una ficha con filas y columnas escritas a mano y pasa a ser una región rectangular seleccionada sobre un plano de asientos. Eso hace la gestión más natural, reduce errores y encaja mejor con una compra visual por parte del usuario final.

Para implementarlo de forma sólida en este proyecto, la recomendación es mantener el stack actual de Laravel, Vite, Blade y JavaScript modular, y construir el editor sobre SVG y DOM nativo. No hace falta meter React o Vue solo para esto. Si más adelante el editor crece mucho, se puede valorar una librería pequeña de interacción, pero el primer corte debe ser simple, mantenible y coherente con el código que ya existe.

---

## Qué quiere conseguir el producto

### Objetivo funcional

- El admin ve un mapa del estadio.
- El admin selecciona dos asientos del mapa.
- El sistema calcula el rectángulo mínimo que los contiene.
- Ese rectángulo se guarda como un nuevo sector.
- El sector queda coloreado, nombrado y listo para asignarle precio.
- El comprador entra al evento, ve los sectores sobre el mapa y después abre la rejilla del sector para elegir asientos individuales.
- En la compra, los asientos se ven dibujados y cada click añade o quita asientos del carrito.

### Regla de negocio principal

Los sectores de esta versión solo pueden ser rectangulares y alineados con la rejilla. No se permiten formas libres ni polígonos irregulares en la primera versión. Esa limitación no es una carencia, es una decisión intencionada para mantener el sistema controlado, predecible y rápido de operar.

---

## Problema actual y solución propuesta

### Lo que suele fallar en sistemas de este tipo

- Los sectores se crean en listas o formularios abstractos y nadie visualiza realmente dónde caen.
- El admin termina adivinando tamaño y posición en vez de ver el mapa.
- La compra por lista de asientos es poco intuitiva.
- Si se intenta permitir demasiada libertad desde el principio, el editor se vuelve difícil de mantener.

### Lo que proponemos aquí

- Un mapa visual como punto de entrada.
- Creación de sectores por selección de rango rectangular.
- Persistencia de sectores como bloques de asientos controlados.
- Visualización de compra en rejilla.
- Implementación técnica minimalista y compatible con el proyecto actual.

---

## Decisión técnica recomendada

### Recomendación principal

Usar la arquitectura actual del proyecto y no migrar a un framework SPA para resolver este caso.

### Por qué

- El proyecto ya está montado con Laravel y Vite.
- Ya existe JavaScript manual en `public/js/pages/`.
- La parte crítica no es la navegación de una app compleja, sino dibujar y manipular elementos en un plano.
- SVG da acceso directo a elementos clicables, accesibles y estilables.
- El coste de introducir React, Vue o una librería de estado sería mayor que el beneficio para este alcance.

### Stack propuesto

- Backend: Laravel.
- UI de administración: Blade + JavaScript modular.
- Mapa visual: SVG.
- Interacción de selección: Pointer Events nativos, con posibilidad de añadir una librería ligera si en pruebas hace falta mejorar el arrastre o el multiseleccionado.
- Compra de asientos: grid HTML/CSS renderizado desde JS.

### Librería extra: sí, pero solo si aporta valor real

Si se quiere mejorar la experiencia de selección rectangular en el editor del admin, la única categoría de herramienta que merece la pena es una librería pequeña de interacción, no un framework grande. La prioridad debe ser:

1. SVG y DOM nativo primero.
2. Librería de interacción ligera solo si el arrastre y la selección se complican.
3. Framework pesado solo si el editor termina siendo una app dentro de la app, cosa que hoy no parece necesaria.

---

## Flujo del admin

### 1. Abrir el editor del evento

El administrador entra en la edición de un evento y abre el editor visual de sectores. En lugar de ver solo botones o formularios sueltos, ve un plano del estadio con una rejilla de referencia.

### 2. Empezar una selección

El admin pulsa sobre el primer asiento de la zona que quiere convertir en sector. Ese punto actúa como esquina inicial del rectángulo.

### 3. Marcar el segundo asiento

Después pulsa otro asiento que define la esquina opuesta. El sistema no guarda solo dos puntos; calcula el rectángulo completo entre ambos.

### 4. Previsualizar el sector

Antes de guardar, el editor debe mostrar:

- el área resaltada,
- cuántas filas y columnas contiene,
- cuántos asientos tendrá,
- si se solapa con otro sector,
- y qué color o nombre se le asignará.

### 5. Confirmar y guardar

Si todo es correcto, el admin confirma. El backend crea o actualiza el sector y genera sus asientos de forma coherente con ese rectángulo.

### 6. Asignar precio

Una vez creado el sector, el admin le pone precio o ajusta el existente.

---

## Flujo del comprador

### 1. Entrar al evento

El comprador llega a la pantalla de compra del evento.

### 2. Ver el mapa de sectores

Se muestra el plano del estadio con sectores coloreados. Cada sector se ve con su color de base para que el usuario entienda rápido a qué zona pertenece.

### 3. Elegir sector

Cuando pulsa un sector, se abre su rejilla de asientos dibujada encima de ese color de sector.

### 4. Seleccionar asientos

Puede pulsar asientos uno a uno para añadirlos al carrito. El asiento cambia de estado visual al seleccionarse y se mantiene visible el color del sector por debajo o alrededor del bloque, según el diseño final que se aplique.

### 5. Ver resumen y pagar

El carrito se actualiza con la selección actual y el flujo de pago sigue como ya existe o como se debe consolidar en la evolución del proyecto.

---

## Modelo de datos recomendado

### Sector

El sector debería guardar la información necesaria para poder reconstruir su rectángulo y dibujarlo sin ambigüedad.

Campos recomendados:

- `id`
- `evento_id`
- `nombre`
- `descripcion`
- `color_hex`
- `activo`
- `fila_inicio`
- `fila_fin`
- `columna_inicio`
- `columna_fin`
- `cantidad_filas`
- `cantidad_columnas`
- `orden_visual` o `prioridad_visual` si hace falta ordenar el mapa

### Asiento

Cada asiento sigue siendo la unidad vendible.

Campos recomendados:

- `id`
- `sector_id`
- `fila`
- `numero`
- `estado_base` o derivación del estado por evento
- `created_at`
- `updated_at`

### Precio

No necesita grandes cambios, salvo asegurar que sigue ligado al sector y al evento.

### Estado del asiento

Se mantiene la lógica de disponibilidad por evento:

- disponible,
- reservado,
- ocupado.

### Nota importante de diseño

Si hoy la base de datos ya separa sectores, asientos y estado, no conviene introducir una estructura nueva innecesaria. Lo mejor es añadir coordenadas y límites al sector, no reinventar todo el modelo.

---

## Comportamiento del editor rectangular

### Regla del rectángulo

Cualquier selección de dos asientos define un rectángulo alineado a la rejilla.

Ejemplo:

- primer clic: A3
- segundo clic: C6

Resultado:

- filas A, B y C,
- columnas 3, 4, 5 y 6,
- 12 asientos en total.

### Validaciones necesarias

- No permitir seleccionar un área vacía fuera de la rejilla.
- No permitir crear un sector que se solape con otro sector ya definido, salvo que el negocio lo permita expresamente.
- No permitir sectores con ancho o alto cero.
- No permitir sectores con número de asientos inferior o superior a los límites del negocio.
- No permitir cambiar el tamaño de un sector si eso rompe ventas ya asociadas sin una confirmación clara.

### Cómo debe verse la experiencia

- Primer clic: se marca la esquina inicial.
- Segundo clic o arrastre: se dibuja la previsualización.
- Panel lateral: nombre, color, precio, resumen de tamaño y validaciones.
- Botón de confirmar: crea el sector.

---

## Backend: plan de actualización

### 1. Endpoints de administración

Se recomienda exponer una API clara para el editor de sectores:

- crear sector a partir de una selección rectangular,
- editar un sector existente,
- eliminar un sector,
- listar sectores del evento,
- consultar asientos de un sector,
- consultar conflictos o solapes antes de guardar.

### 2. Flujo de creación

1. El frontend envía el evento, el rectángulo seleccionado y los metadatos del sector.
2. El backend valida que el rectángulo sea consistente.
3. El backend comprueba si el área se superpone con un sector ya existente.
4. Si es válido, guarda el sector y genera o vincula los asientos necesarios.
5. Si el sector ya existía, lo actualiza y, si hace falta, regenera su cobertura.

### 3. Flujo de edición

La edición debe ser conservadora:

- cambiar nombre y color debe ser inmediato,
- cambiar límites debe requerir validación,
- cambiar tamaño debe disparar revisión de asientos afectados,
- si existen ventas o reservas, no debe hacerse sin control.

### 4. Flujo de borrado

Un sector solo debería borrarse si no tiene asientos comprometidos por reservas o compras en curso. Si los tiene, el sistema debe bloquear la operación o exigir una sustitución controlada.

---

## Frontend: plan de actualización

### Área de administración

La vista de administración debería tener dos zonas:

- el mapa visual del estadio,
- el panel de propiedades del sector.

#### Componentes recomendados

- `SectorMapEditor`: controla clics, selección rectangular y previsualización.
- `SectorSidebarForm`: muestra nombre, color, precio, filas, columnas y estado.
- `SectorList`: lista sectores existentes y permite seleccionar uno para editarlo.

### Área de compra

#### Componentes recomendados

- `SeatMapManager`: coordina la carga del evento y los sectores.
- `SeatGrid`: dibuja la rejilla del sector seleccionado y gestiona el click sobre cada asiento.
- `SeatLegend`: muestra estados y colores.
- `CartSummary`: refleja la selección.

### Representación visual

Para compra y para admin no hace falta la misma capa visual exacta.

- En admin importa más la selección y edición.
- En compra importa más la claridad, el rendimiento y la lectura del estado de cada asiento.
- El color del sector debe seguir siendo visible en la compra como referencia visual del bloque de asientos.
- Los asientos deben verse como elementos clicables encima de esa base de color, no como una lista suelta.

---

## Decisión de implementación por fases

### Fase 1: Base técnica

Objetivo: dejar listo el terreno para el editor.

- Definir modelo de datos para límites del sector.
- Crear endpoints básicos de listar, crear, editar y borrar.
- Dejar preparada la respuesta de asientos con estado.
- Añadir validaciones de solape y tamaño.

### Fase 2: Editor visual del admin

Objetivo: que el admin pueda dibujar sectores.

- Crear vista Blade del editor.
- Dibujar el mapa del estadio en SVG.
- Implementar selección inicial y final.
- Pintar previsualización del rectángulo.
- Crear panel lateral de datos.
- Guardar sector desde el editor.

### Fase 3: Compra visual

Objetivo: que el comprador vea y use asientos individuales.

- Renderizar sectores como bloques coloreados.
- Al pulsar un sector, cargar su rejilla de asientos visibles.
- Permitir selección por click sobre cada asiento.
- Mostrar el estado visual del asiento seleccionado, disponible o no disponible.
- Mantener visible el color del sector como base visual del área.
- Sincronizar carrito y total.

### Fase 4: Robustez y UX

Objetivo: hacer el sistema fiable.

- Gestión de carga y errores.
- Estados vacíos y mensajes claros.
- Prevención de dobles clics.
- Cache de asientos por sector.
- Validaciones visuales de conflicto.

### Fase 5: Pruebas y endurecimiento

Objetivo: evitar regresiones.

- tests de modelo,
- tests de API,
- tests de creación por rectángulo,
- tests de no solape,
- tests de compra y carrito.

---

## Reglas de negocio detalladas

### Regla 1: sectores rectangulares solamente

La primera versión no debe admitir formas raras. Esto simplifica el cálculo, el render y la edición.

### Regla 2: el mapa manda

La geometría visible es la fuente de verdad operativa del editor.

### Regla 3: no romper ventas existentes

Si un sector ya tiene asientos reservados o vendidos, cualquier modificación debe ser segura o bloqueada.

### Regla 4: el precio sigue siendo por sector

El objetivo no es convertir cada asiento en una tarifa distinta salvo que el negocio lo pida después.

### Regla 5: compatibilidad progresiva

Los sectores ya existentes deben poder seguir funcionando aunque no hayan sido creados con el nuevo editor.

---

## Qué cambiaría respecto al documento anterior

### Lo que se mantiene

- compra por rejilla,
- asiento individual seleccionable,
- sectores coloreados,
- compatibilidad con el sistema actual,
- almacenamiento de estado por evento.

### Lo que cambia

- el admin ya no crea sectores por un formulario de filas y columnas como flujo principal,
- el admin crea sectores seleccionando un rectángulo sobre el mapa,
- el plan técnico deja de centrarse en matrices abstractas y se centra en geometría visual,
- la solución se apoya más en SVG y menos en interfaces genéricas de catálogo.

---

## Riesgos y cómo reducirlos

### Riesgo 1: solapes entre sectores

Mitigación: validar intersecciones antes de guardar.

### Riesgo 2: editor confuso para el admin

Mitigación: mostrar preview inmediata, resumen de tamaño y mensajes claros.

### Riesgo 3: demasiada complejidad de frontend

Mitigación: evitar frameworks pesados y mantener un módulo por responsabilidad.

### Riesgo 4: asientos ya vendidos afectados por cambios de geometría

Mitigación: bloquear edición destructiva cuando existan reservas o ventas.

### Riesgo 5: rendimiento en mapas grandes

Mitigación: cachear sectores, no renderizar más de lo necesario y usar SVG/DOM con estructuras ligeras.

---

## Criterios de aceptación

El cambio puede considerarse bien resuelto si se cumple todo esto:

- El admin puede crear un sector seleccionando dos asientos del mapa.
- El sistema convierte esa selección en un rectángulo.
- El sector se guarda con nombre, color y precio.
- El sistema evita solapes o los maneja de forma explícita.
- El comprador ve los sectores por colores.
- El comprador puede abrir un sector, ver sus asientos dibujados y pulsar cada asiento para añadirlo al carrito.
- El color del sector sigue siendo visible debajo o como base del grupo de asientos.
- La compra y el carrito siguen funcionando.
- El sistema no obliga a migrar la app a un framework grande.

---

## Roadmap futuro

### v2.1

- arrastre de bordes para redimensionar sectores,
- edición visual de nombres y colores directamente sobre el plano,
- mejor soporte de zonas premium y etiquetas internas.

### v2.2

- plantillas de sectores reutilizables,
- duplicar zonas entre eventos,
- copiado rápido de geometrías frecuentes.

### v3

- editor visual más libre,
- capas por zona,
- soporte para geometrías no rectangulares si el negocio lo necesita de verdad.

---

## Conclusión

La mejor versión de este proyecto no es la más ambiciosa técnicamente, sino la que resuelve bien el flujo real del negocio. Para Roig Arena, eso significa un editor visual que permita al admin definir un sector con dos clics sobre un mapa, guardar ese rectángulo como bloque de asientos y seguir vendiendo con una experiencia clara y rápida.

La dirección recomendada es clara: mantener Laravel y JavaScript modular, usar SVG para el mapa, reservar las librerías externas para casos concretos de interacción y construir un sistema sencillo de entender, fácil de mantener y suficientemente robusto para crecer después.

---

## Guía paso a paso para implementar el update

Esta sección es la hoja de ruta práctica para ir tocando el proyecto sin perder el control. La idea es avanzar por capas: primero datos y backend, después editor visual, y por último la compra.

### Paso 1: decidir y fijar el modelo de datos

Archivos a tocar:

- `database/migrations/*`
- `app/Models/Sector.php`
- `app/Models/Asiento.php`

### Campos exactos que ya existen hoy

#### `sectores`

Estos son los campos que ya tienes en la base de datos actual:

- `id`
- `nombre`
- `descripcion`
- `cantidad_filas`
- `cantidad_columnas`
- `color_hex`
- `activo`
- `created_at`
- `updated_at`

#### `asientos`

Estos son los campos que ya tienes hoy para cada asiento:

- `id`
- `sector_id`
- `fila`
- `numero`
- `created_at`
- `updated_at`

#### `precios`

Campos actuales que se mantienen:

- `id`
- `evento_id`
- `sector_id`
- `precio`
- `disponible`
- `created_at`
- `updated_at`

#### `estado_asientos`

Campos actuales que se mantienen:

- `id`
- `evento_id`
- `asiento_id`
- `user_id`
- `estado`
- `reservado_hasta`
- `created_at`
- `updated_at`

### Campos exactos que deberías añadir

Para que el editor rectangular funcione bien y puedas reconstruir el bloque del sector sin inventar posiciones, lo recomendable es añadir estos campos a `sectores`:

- `fila_inicio` `string` o `integer` según la estrategia de filas que elijas.
- `fila_fin` `string` o `integer`.
- `columna_inicio` `integer`.
- `columna_fin` `integer`.
- `posicion_x` `decimal` o `integer`, si quieres guardar la posición del sector en el mapa visual.
- `posicion_y` `decimal` o `integer`, si quieres guardar la posición del sector en el mapa visual.
- `orden_visual` `integer`, si quieres controlar en qué orden se pintan los sectores.

### Qué conviene guardar y qué conviene calcular

Lo más limpio para este proyecto es esto:

- Guardar siempre el rectángulo exacto: inicio y fin de filas/columnas.
- Calcular a partir de eso `cantidad_filas` y `cantidad_columnas`.
- Mantener `cantidad_filas` y `cantidad_columnas` si ya te vienen bien para consultas y vistas.
- Usar `posicion_x` y `posicion_y` solo si más adelante quieres arrastrar sectores por el mapa o guardar una posición absoluta dentro del lienzo.

### Recomendación práctica de diseño

Si quieres ir a lo seguro, la primera versión debería añadir como mínimo estos 4 campos:

- `fila_inicio`
- `fila_fin`
- `columna_inicio`
- `columna_fin`

Y dejar `posicion_x`, `posicion_y` y `orden_visual` como opcionales para después, salvo que ya tengas claro que el mapa visual los va a necesitar desde el primer día.

Qué hacer:

1. Revisar cómo están hoy las tablas de sectores, asientos, precios y estado de asientos.
2. Añadir al sector los campos que permitan guardar el rectángulo: `fila_inicio`, `fila_fin`, `columna_inicio`, `columna_fin`.
3. Decidir si también necesitas `posicion_x`, `posicion_y` y `orden_visual` desde esta versión o si los dejas para una iteración posterior.
4. Ver si hace falta una migración nueva o si basta con reutilizar lo que ya existe.
5. Confirmar que un asiento sigue siendo la unidad vendible y que el sector solo define el bloque visual y lógico.

### Ejemplo de migración para esta fase

Si decides dejarlo cerrado desde ya, la migración de `sectores` debería quedar conceptualmente así:

```php
Schema::table('sectores', function (Blueprint $table) {
	$table->string('fila_inicio')->nullable()->after('activo');
	$table->string('fila_fin')->nullable()->after('fila_inicio');
	$table->integer('columna_inicio')->nullable()->after('fila_fin');
	$table->integer('columna_fin')->nullable()->after('columna_inicio');
	$table->decimal('posicion_x', 10, 2)->nullable()->after('columna_fin');
	$table->decimal('posicion_y', 10, 2)->nullable()->after('posicion_x');
	$table->integer('orden_visual')->nullable()->after('posicion_y');
});
```

Si quieres mantenerlo todavía más simple, puedes quitar `posicion_x`, `posicion_y` y `orden_visual` y dejar solo los cuatro límites del rectángulo.

### Paso 2: preparar la lógica del backend

Archivos a tocar:

- `app/Models/Sector.php`
- `app/Http/Controllers/...`
- `app/Services/...` si quieres separar lógica de negocio

Qué hacer:

1. Crear la lógica para calcular un rectángulo a partir de dos asientos seleccionados.
2. Añadir validación de solapes con sectores existentes.
3. Crear métodos para crear, editar y borrar sectores de forma segura.
4. Definir qué pasa si un sector ya tiene asientos reservados o vendidos.
5. Si el proyecto empieza a crecer, mover esta lógica a un service para no ensuciar el controlador.

### Paso 3: exponer endpoints claros

Archivos a tocar:

- `routes/web.php`
- `routes/api.php`
- el controlador de sectores que uses

Qué hacer:

1. Crear ruta para listar sectores del evento.
2. Crear ruta para guardar un sector nuevo a partir del rectángulo.
3. Crear ruta para editar un sector.
4. Crear ruta para borrar un sector.
5. Crear ruta para devolver los asientos de un sector con su estado.
6. Crear una ruta o endpoint auxiliar para validar conflictos antes de guardar.

### Paso 4: construir el editor visual del admin

Archivos a tocar:

- `resources/views/eventos/show.blade.php`
- una nueva vista como `resources/views/eventos/sectores-editor.blade.php`
- `public/js/pages/editarSectoresEvento.js`
- `public/js/components/...` si quieres separar piezas

Qué hacer:

1. Añadir en la vista del evento un botón o acceso al editor visual.
2. Dibujar el mapa base del estadio en SVG.
3. Pintar los sectores existentes con color.
4. Permitir que el admin haga click en un asiento inicial y otro final.
5. Mostrar en pantalla la previsualización del rectángulo.
6. Crear un panel lateral con nombre, color, precio y resumen del sector.
7. Enviar la selección al backend cuando el admin confirme.

### Paso 5: adaptar la compra para que se vean y se pulseen los asientos

Archivos a tocar:

- `public/js/pages/compra.js`
- `resources/views/compra/buy.blade.php`
- `public/css/...` o los estilos que ya use el proyecto

Qué hacer:

1. Mantener el mapa de sectores por colores.
2. Al pulsar un sector, mostrar su rejilla de asientos.
3. Dibujar cada asiento como un elemento clicable.
4. Hacer que al pulsar un asiento se añada o quite del carrito.
5. Pintar claramente el estado del asiento: disponible, ocupado, reservado o seleccionado.
6. Mantener visible el color del sector como base del bloque para que el usuario entienda la zona.

### Paso 6: ajustar estilos y comportamiento visual

Archivos a tocar:

- `public/css/components/seat-grid.css` o equivalente
- cualquier CSS del mapa o del editor visual

Qué hacer:

1. Definir cómo se ve el asiento disponible.
2. Definir cómo se ve el asiento seleccionado.
3. Definir cómo se ve el asiento no disponible.
4. Asegurar que el color del sector no desaparece cuando se muestran los asientos.
5. Cuidar el hover, el foco y la accesibilidad básica.

### Paso 7: conectar el carrito y los totales

Archivos a tocar:

- `public/js/pages/compra.js`

Qué hacer:

1. Asegurar que la selección visual y el carrito usan la misma fuente de verdad.
2. Actualizar el resumen de selección en cada click.
3. Recalcular total y subtotal por sector.
4. Evitar que el usuario pueda seguir con el checkout si no hay asientos válidos seleccionados.

### Paso 8: añadir validaciones y casos de borde

Archivos a tocar:

- modelos
- requests o validators si los usas
- controladores

Qué hacer:

1. Bloquear sectores que se solapen.
2. Bloquear rectángulos vacíos o mal formados.
3. Bloquear borrados peligrosos.
4. Bloquear cambios que rompan reservas existentes.
5. Mostrar mensajes de error claros al admin.

### Paso 9: probar antes de seguir creciendo

Archivos a tocar:

- `tests/Feature/...`
- `tests/Unit/...`

Qué hacer:

1. Probar que un rectángulo genera exactamente los asientos esperados.
2. Probar que no se crean sectores solapados.
3. Probar que la compra muestra los asientos y permite seleccionarlos.
4. Probar que el carrito se actualiza correctamente.
5. Probar que los sectores antiguos siguen funcionando.

### Orden recomendado de trabajo

Si quieres hacerlo sin dispersarte, este es el orden más limpio:

1. Modelo de datos.
2. Endpoints de backend.
3. Lógica de rectángulo y validaciones.
4. Editor visual del admin.
5. Compra visual con asientos clicables.
6. Estilos.
7. Carrito y checkout.
8. Tests.

### Qué no tocar al principio

Para no complicarte desde el primer día, evita esto al inicio:

- migrar toda la app a React o Vue,
- permitir formas irregulares,
- meter edición libre de sectores,
- separar demasiado la lógica antes de validar el flujo básico,
- rehacer el checkout completo sin necesidad.

### Resultado esperado al terminar esta hoja de ruta

Al acabar estos pasos tendrás:

- un admin que puede dibujar sectores sobre el mapa,
- una compra donde el usuario ve asientos reales y los clickea,
- sectores que conservan su color visual,
- un carrito sincronizado con la selección,
- y una base técnica suficientemente limpia para seguir iterando.

