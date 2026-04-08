# Roig Arena

API REST para la gestión de eventos, sectores, asientos y compra de entradas del Roig Arena. Construida con **Laravel 12** y desplegada con **Laravel Sail** (Docker).

---

## Requisitos

- [Docker](https://docs.docker.com/get-docker/) con el plugin **Docker Compose**
- Bash (Linux / macOS / WSL en Windows)

> En Ubuntu/Debian, el script `setup-arena.sh` puede instalar Docker automáticamente si no está presente.

---

## Puesta en marcha

### 1. Configuración inicial (primera vez)

Ejecuta el script de setup desde la raíz del repositorio. Este script:

- Instala Docker si no está disponible (Ubuntu/Debian).
- Crea el archivo `.env` a partir de `.env.example`.
- Configura las variables de base de datos para Sail.
- Instala las dependencias de Composer (local o mediante contenedor).

```bash
bash setup-arena.sh
```

### 2. Levantar el entorno completo

Una vez completado el setup, usa `arena.sh` para arrancar el proyecto. Este script:

- Detiene y limpia los contenedores existentes (incluidos volúmenes).
- Levanta los servicios en segundo plano con Sail.
- Espera a que MySQL esté listo.
- Genera la `APP_KEY` si no existe.
- Ejecuta `migrate:fresh --seed` para recrear y poblar la base de datos.
- Lanza la batería de tests de PHPUnit.

```bash
bash arena.sh
```

> ⚠️ `arena.sh` hace un reset completo del entorno. Úsalo para limpiar y reiniciar desde cero.

---

## Estructura del proyecto

```
Roig-Arena/
├── setup-arena.sh        # Setup inicial del entorno (ejecutar una vez)
├── arena.sh              # Reinicio completo del entorno
└── roig-arena/           # Aplicación Laravel
    ├── app/
    │   ├── Http/
    │   │   ├── Controllers/   # Controladores de la API
    │   │   └── Resources/     # Recursos JSON
    │   └── Models/            # Modelos Eloquent
    ├── database/
    │   ├── migrations/        # Migraciones
    │   ├── factories/         # Factories para tests/seeders
    │   └── seeders/           # Seeders de datos iniciales
    ├── routes/
    │   └── api.php            # Rutas de la API
    ├── tests/                 # Tests de PHPUnit
    ├── compose.yaml           # Configuración de Docker / Sail
    └── .env.example           # Variables de entorno de ejemplo
```

---

## Variables de entorno

El script `setup-arena.sh` configura automáticamente el `.env` con los valores para Sail:

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=arena
DB_USERNAME=sail
DB_PASSWORD=password
```

---

## Comandos útiles de Sail

Una vez levantado el entorno, puedes usar Sail desde dentro de `roig-arena/`:

```bash
# Levantar / detener servicios
./vendor/bin/sail up -d
./vendor/bin/sail down

# Migraciones
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan migrate:fresh --seed

# Tests
./vendor/bin/sail artisan test

# Frontend
./vendor/bin/sail npm run dev
./vendor/bin/sail npm run build

# Acceso al contenedor
./vendor/bin/sail shell

# Logs
./vendor/bin/sail logs -f
```

---

## Endpoints principales de la API

| Método | Ruta                                  | Descripción                       | Auth |
|--------|---------------------------------------|-----------------------------------|------|
| POST   | `/api/register`                       | Registro de usuario               | No   |
| POST   | `/api/login`                          | Login (devuelve token)            | No   |
| POST   | `/api/logout`                         | Logout                            | Sí   |
| GET    | `/api/eventos`                        | Listar eventos                    | No   |
| GET    | `/api/eventos/{id}`                   | Detalle de un evento              | No   |
| GET    | `/api/eventos/{id}/sectores`          | Sectores de un evento             | No   |
| GET    | `/api/eventos/{id}/asientos`          | Asientos disponibles de un evento | No   |
| POST   | `/api/reservas`                       | Crear reserva de asientos         | Sí   |
| GET    | `/api/reservas`                       | Ver mis reservas                  | Sí   |
| DELETE | `/api/reservas/{id}`                  | Cancelar reserva                  | Sí   |
| POST   | `/api/compras`                        | Comprar entradas                  | Sí   |
| GET    | `/api/compras`                        | Ver mis compras                   | Sí   |

La autenticación usa **Laravel Sanctum** (Bearer token).

---

## Diseño Del Estadio Interactivo (Pasos Recomendados)

Para llegar a un estadio visualmente limpio y que siga funcionando bien cuando hay muchos sectores, estos son los pasos que se deberían haber seguido:

1. Definir criterios de UX antes de programar.
    - Objetivo visual: que parezca un graderio real, no un circulo con bloques sueltos.
    - Objetivo funcional: seleccionar sector en 1 clic y ver asientos sin perder contexto.
    - Escalabilidad: soportar pocos sectores y tambien eventos con muchos sectores.

2. Estandarizar los datos minimos por sector desde la API.
    - Campos necesarios: id, nombre, color_hex, pivot.precio, cantidad_filas y cantidad_columnas.
    - Con eso se puede dibujar el mapa, mostrar precio y renderizar asientos del sector activo.

3. Elegir SVG como base de renderizado del mapa.
    - SVG permite dibujar formas elipticas y segmentos con precision.
    - Es mas mantenible y escalable que posicionar botones absolutos alrededor de un contenedor.

4. Repartir sectores en anillos dinámicos.
    - Calcular el numero de anillos segun el total de sectores.
    - Distribuir sectores de forma equilibrada por anillo para evitar saturacion.
    - Limitar ángulos útiles del graderío para reservar zona de escenario.

5. Dibujar cada sector como un segmento real de graderio.
    - Cada segmento se construye con arco exterior + arco interior + cierres laterales.
    - Aplicar separacion angular minima para que se distingan incluso cuando hay muchos sectores.
    - Mantener estados visuales claros: normal, hover y activo.

6. Añadir una segunda capa de navegación para alta densidad.
    - Debajo del mapa, mostrar una lista compacta de sectores (chips).
    - Sincronizar siempre mapa y lista: seleccionar en uno activa el otro.
    - Esto evita problemas de usabilidad cuando los segmentos son pequenos.

7. Centralizar la logica de estado activo.
    - Una sola funcion debe activar el sector y disparar render de asientos.
    - Esa misma funcion debe actualizar estilos del segmento SVG y del chip de lista.

8. Cuidar responsive y accesibilidad.
    - En movil, priorizar lectura del mapa y mantener scroll controlado en la lista.
    - Permitir selección por teclado en segmentos del SVG (Enter y Espacio).
    - Incluir title o aria-label para mejorar contexto en lectores y tooltips.

9. Validar comportamiento con escenarios reales.
    - Probar eventos con pocos, medianos y muchos sectores.
    - Revisar que no haya solapes, que la seleccion sea estable y que la carga sea fluida.
    - Confirmar que el flujo completo (sector -> asientos -> carrito) no se rompe.

Archivos clave de referencia en esta implementacion:

- `roig-arena/public/js/pages/compra.js`
- `roig-arena/public/css/pages/compra.css`
- `roig-arena/public/css/pages/setmap.css`

---

## Tecnologías

- [Laravel 12](https://laravel.com/)
- [Laravel Sail](https://laravel.com/docs/sail) (Docker)
- [Laravel Sanctum](https://laravel.com/docs/sanctum) (autenticación API)
- MySQL 8
- PHPUnit (tests)
