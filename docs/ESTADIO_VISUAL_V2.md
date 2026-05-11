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

Además, para simplificar lógica y validaciones, la geometría interna se gestiona en coordenadas numéricas (`fila`, `columna`). Si quieres mostrar letras (A, B, C...) en interfaz, se generan solo en frontend como formato visual.

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

Nota de implementación recomendada:

- `fila` y `numero` se guardan como enteros en backend.
- Si quieres mostrar etiqueta tipo `A3`, se calcula en UI (`fila` 1 -> `A`) sin cambiar la fuente de verdad.

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

- primer clic: fila 1, columna 3
- segundo clic: fila 3, columna 6

Resultado:

- filas 1, 2 y 3,
- columnas 3, 4, 5 y 6,
- 12 asientos en total.

Si en interfaz quieres mostrar letras, esas mismas filas pueden mostrarse como A, B y C, pero internamente siguen siendo 1, 2 y 3.

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

- `fila_inicio` `integer`.
- `fila_fin` `integer`.
- `columna_inicio` `integer`.
- `columna_fin` `integer`.
- `posicion_x` `decimal(10,2)`.
- `posicion_y` `decimal(10,2)`.
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

La decisión recomendada es usar filas numéricas (`integer`) y columnas numéricas (`integer`) como fuente de verdad para cálculo de rectángulos, solapes y generación de asientos. Si quieres mostrar letras en pantalla, conviértelas en frontend solo para presentación. Si guardas posición visual, usa `decimal(10,2)` para `posicion_x` y `posicion_y` porque el SVG y el layout visual suelen necesitar decimales para alinear bien los elementos.

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
	$table->integer('fila_inicio')->nullable();
	$table->integer('fila_fin')->nullable();
	$table->integer('columna_inicio')->nullable();
	$table->integer('columna_fin')->nullable();
	$table->decimal('posicion_x', 10, 2)->nullable();
	$table->decimal('posicion_y', 10, 2)->nullable();
	$table->integer('orden_visual')->nullable();
});
```

Si quieres mantenerlo todavía más simple, puedes quitar `posicion_x`, `posicion_y` y `orden_visual` y dejar solo los cuatro límites del rectángulo.

### Paso 2: preparar la lógica del backend

Archivos a tocar:

- `app/Models/Sector.php`
- `app/Http/Controllers/...`
- `app/Services/...` si quieres separar lógica de negocio

Qué hacer:

1. En `app/Models/Sector.php`, crea un método para calcular la posición rectangular del sector a partir de dos coordenadas. Ese método debe recibir los dos extremos y devolver siempre los límites ordenados, es decir, cuál es el inicio real y cuál es el final real aunque el usuario pinche primero abajo y luego arriba. (hecho)
2. En ese mismo modelo, añade un método que te diga cuántas filas y cuántas columnas ocupa ese rectángulo a partir de los límites. La idea es que no tengas que repetir la cuenta en varios sitios. (hecho)
3. Añade un método para generar la lista de asientos que pertenecen a ese rectángulo. Ese método debe recorrer todas las filas y columnas del bloque y devolver la colección que luego insertará el backend.
4. Añade un método para comprobar si un nuevo rectángulo se solapa con algún sector existente. Ese método debe comparar rangos de filas y columnas, no solo posiciones visuales.
5. En el controlador que uses para sectores, crea una acción para guardar un sector nuevo. Esa acción debe recibir el rectángulo, validar los datos y llamar a los métodos del modelo.
6. En el mismo controlador, crea una acción para editar un sector. Esa acción debe permitir cambiar nombre, color y descripción sin tocar la geometría si no hace falta.
7. Si quieres permitir cambiar la geometría, esa misma acción debe volver a validar el rectángulo y regenerar los asientos solo si no rompe reservas ni ventas.
8. Añade una acción para borrar un sector. Esa acción debe comprobar primero si hay reservas o ventas asociadas; si las hay, debe bloquear el borrado.
9. Si prefieres no meter toda la lógica en el controlador, crea un service dedicado, por ejemplo `SectorGeometryService`, que se encargue de calcular límites, solapes y generación de asientos. El controlador solo recibiría la request y llamaría al service.
10. Mantén la lógica de negocio centralizada en un solo sitio. No dupliques el cálculo del rectángulo en el modelo, el controlador y el frontend porque eso te acabará creando desajustes.

### Paso 3: exponer endpoints claros

Archivos a tocar:

- `routes/web.php`
- `routes/api.php`
- el controlador de sectores que uses

Qué hacer:

1. En `routes/web.php`, define las rutas de administración que se usarán desde el editor visual. Deben vivir detrás de autenticación de admin o del middleware que ya use el panel.
2. Crea una ruta GET para cargar el editor de sectores de un evento. Esa vista debe recibir el `evento_id` y, si hace falta, el listado inicial de sectores.
3. Crea una ruta GET o POST para listar sectores del evento en formato JSON. Esa respuesta debe devolver `id`, `nombre`, `color_hex`, límites del sector y, si lo necesitas, los asientos agrupados.
4. Crea una ruta POST para guardar un sector nuevo. Esa ruta debe recibir los datos del rectángulo y el metadato del sector.
5. Crea una ruta PATCH para editar un sector. Úsala para cambios de nombre, color, descripción y, si lo permites, límites.
6. Crea una ruta DELETE para borrar un sector. Debe devolver error claro si el sector no puede eliminarse.
7. En `routes/api.php`, crea una ruta para consultar los asientos de un sector concreto con su estado. Esa respuesta la usará la pantalla de compra.
8. Añade una ruta de validación previa si te viene bien separar la previsualización del guardado final. Esa ruta debería devolver si hay solape, si el rectángulo es válido y cuántos asientos generaría.
9. Mantén nombres de rutas claros y coherentes. Si una ruta es de admin, que se vea; si es de compra, que quede separada; si es de validación, que no se mezcle con la de guardado.
10. Evita meter lógica en la ruta. La ruta solo debe apuntar al controlador o al service, no resolver nada por su cuenta.

### Paso 4: construir el editor visual del admin

Archivos a tocar:

- `resources/views/eventos/show.blade.php`
- una nueva vista como `resources/views/eventos/sectores-editor.blade.php`
- `public/js/pages/editarSectoresEvento.js`
- `public/js/components/...` si quieres separar piezas

Qué hacer:

1. En `resources/views/eventos/show.blade.php`, añade un botón visible para abrir el editor visual. Ese botón debe llevar a una vista específica del editor o abrir un modal grande, pero no debe quedar escondido.
2. Crea la vista del editor, por ejemplo `resources/views/eventos/sectores-editor.blade.php`. Esa vista debe tener tres zonas: el mapa, el panel lateral y una zona de acciones.
3. En la parte central, dibuja el mapa base del estadio con SVG. Ese SVG debe mostrar una rejilla o al menos puntos claramente clicables que representen asientos.
4. Pinta sobre ese mapa los sectores ya existentes con su color. Así el admin ve qué zonas ya están ocupadas antes de crear otra.
5. Cuando el admin haga click en el primer asiento, marca ese asiento como inicio visual. Debe cambiar de estado para que el usuario vea claramente que ya se ha empezado una selección.
6. Cuando haga click en el segundo asiento, calcula el rectángulo completo y pinta la previsualización sobre el mapa. Esa previsualización debe enseñar claramente el área que ocupará el sector.
7. Si el admin mueve el ratón antes de confirmar, actualiza el bloque visual en tiempo real para que vea el tamaño del sector antes de guardarlo.
8. En el panel lateral, muestra los campos que vas a guardar: nombre, color, descripción y precio. Si quieres, añade también el número de filas, columnas y asientos calculados.
9. Añade botones claros para “Cancelar selección”, “Guardar sector” y “Limpiar todo”. No mezcles acciones de edición con acciones de creación.
10. Cuando el usuario pulse guardar, envía al backend el rectángulo, el nombre, el color y el precio. Si el backend devuelve error, muéstralo en pantalla sin perder la selección si el problema es corregible.
11. Si la petición sale bien, refresca el mapa y muestra el nuevo sector pintado con su color.

### Paso 5: adaptar la compra para que se vean y se pulseen los asientos

Archivos a tocar:

- `public/js/pages/compra.js`
- `resources/views/compra/buy.blade.php`
- `public/css/...` o los estilos que ya use el proyecto

Qué hacer:

1. En `public/js/pages/compra.js`, mantén la carga inicial del evento y de los sectores como ya está, pero prepara la lógica para que al pulsar un sector se pinte una rejilla de asientos debajo de él.
2. Cuando el usuario entre en un sector, limpia la zona de asientos anterior y dibuja la nueva rejilla desde cero. Así evitas que queden restos visuales de otro sector.
3. Cada asiento de la rejilla debe ser un elemento clicable separado. No lo pintes como texto suelto; debe parecer una butaca o celda interactiva.
4. Al hacer click en un asiento disponible, añade ese asiento al carrito y marca su estado visual como seleccionado.
5. Si vuelve a hacer click sobre el mismo asiento, quítalo del carrito y devuelve su estado a disponible.
6. Si un asiento está reservado u ocupado, no debe reaccionar al click. Visualmente debe parecer desactivado o bloqueado.
7. Mantén el color base del sector detrás de la rejilla o como fondo del contenedor. Eso ayuda a entender que todos esos asientos pertenecen a una misma zona.
8. En el resumen de compra, refleja siempre cuántos asientos hay seleccionados, cuánto cuesta cada sector y el total general.
9. Si el usuario cambia de sector, no pierdas la selección que ya haya hecho en otros sectores salvo que el flujo del negocio diga lo contrario.
10. Asegúrate de que el comprador pueda ver de forma muy clara qué asientos tiene elegidos, cuáles siguen libres y cuáles no se pueden tocar.

### Paso 6: ajustar estilos y comportamiento visual

Archivos a tocar:

- `public/css/components/seat-grid.css` o equivalente
- cualquier CSS del mapa o del editor visual

Qué hacer:

1. Define un estilo base para el asiento disponible. Debe verse claramente como clicable, con un color legible y un borde que lo distinga del fondo.
2. Define un estilo distinto para el asiento seleccionado. Ese estado tiene que notarse de inmediato sin que el usuario tenga que leer textos.
3. Define un estilo para asiento reservado u ocupado. Debe verse apagado o bloqueado, y el cursor no debe invitar a hacer click.
4. Haz que el sector mantenga su color como fondo o capa base, aunque haya asientos encima. Ese color debe seguir ayudando a leer la zona.
5. Añade un hover claro para los asientos disponibles. El usuario debe notar enseguida que puede pulsarlos.
6. Añade foco visible para navegación por teclado si quieres que el editor y la compra sean más accesibles.
7. Revisa el espaciado entre asientos. Si queda demasiado junto, el usuario no distinguirá bien dónde pulsa.
8. Si usas iconos, sombras o bordes, mantén un lenguaje visual consistente entre admin y compra.

### Paso 7: conectar el carrito y los totales

Archivos a tocar:

- `public/js/pages/compra.js`

Qué hacer:

1. En `public/js/pages/compra.js`, usa una única estructura de datos para la selección, por ejemplo un `Map` o un array de asientos seleccionados. No lleves un estado visual por un lado y otro lógico por otro.
2. Cada vez que se seleccione o deseleccione un asiento, actualiza esa estructura central y desde ahí refresca el carrito.
3. Recalcula el subtotal por sector agrupando los asientos seleccionados por `sector_id`.
4. Recalcula el total general sumando todos los asientos seleccionados.
5. Actualiza el resumen visual con el número de asientos, el nombre del sector y el importe de cada bloque.
6. Si el usuario borra un asiento seleccionado desde el resumen, devuelve ese asiento a su estado visual anterior en la rejilla.
7. Bloquea el botón de continuar o confirmar compra si no queda ningún asiento válido seleccionado.
8. Guarda la selección en localStorage solo si eso encaja con tu flujo actual. Si lo haces, limpia el estado cuando el evento cambie para no mezclar compras.

### Paso 8: añadir validaciones y casos de borde

Archivos a tocar:

- modelos
- requests o validators si los usas
- controladores

Qué hacer:

1. Si el usuario intenta crear un rectángulo que quede fuera del mapa, recházalo antes de enviar nada al backend.
2. Si el rectángulo tiene ancho o alto cero, no lo dejes avanzar.
3. Si el nuevo sector se solapa con uno existente, devuelve un error claro y marca visualmente el conflicto.
4. Si intentan borrar un sector con reservas o entradas ya comprometidas, bloquea la acción y explica por qué.
5. Si intentan cambiar la geometría de un sector que ya tiene ventas, no lo permitas sin una confirmación muy explícita.
6. En compra, si el backend dice que un asiento ya no está disponible, actualiza la UI y quítalo de la selección si hace falta.
7. Muestra siempre mensajes de error concretos: qué ha pasado, en qué asiento o sector ha pasado y qué debe hacer el admin para corregirlo.
8. No dejes que un error silencioso rompa el estado visual del mapa. Si falla algo, el usuario tiene que seguir entendiendo qué está viendo.

### Paso 9: probar antes de seguir creciendo

Archivos a tocar:

- `tests/Feature/...`
- `tests/Unit/...`

Qué hacer:

1. Crea una prueba de unidad para comprobar que un rectángulo A3-C6 genera exactamente 12 asientos y que las coordenadas resultan correctas.
2. Crea una prueba de unidad para comprobar que el cálculo de límites funciona aunque el usuario pinche primero en la esquina contraria.
3. Crea una prueba de integración para verificar que al guardar un sector nuevo se persisten el sector y sus asientos.
4. Crea una prueba de integración para verificar que un solape devuelve error y no guarda nada.
5. Crea una prueba de compra para asegurar que al pulsar un asiento se añade al carrito y se refleja el cambio visual.
6. Crea una prueba para verificar que al pulsar de nuevo el mismo asiento se elimina del carrito.
7. Crea una prueba para comprobar que los sectores antiguos siguen cargando y que no rompes compatibilidad.
8. Crea una prueba final del flujo completo: abrir evento, seleccionar sector, elegir asiento y confirmar carrito.

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

### Siguiente iteración recomendable

Cuando ya tengas esto funcionando, el siguiente paso lógico no es meter más complejidad visual, sino pulir:

- la validación del solape,
- la experiencia de arrastre,
- la edición de sectores ya creados,
- y la respuesta del backend ante asientos bloqueados.

