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

La aplicación sigue una estructura clásica de Laravel, pero organizada para separar claramente la capa HTTP, la lógica de negocio, el acceso a datos y la preparación de respuestas JSON. El flujo general es este:

1. La petición entra por `routes/api.php` o `routes/web.php`.
2. El controlador valida los datos recibidos y decide qué caso de uso ejecutar.
3. Si la operación tiene reglas de negocio más complejas, el controlador delega en un `Service`.
4. El `Model` encapsula relaciones, scopes y métodos útiles del dominio.
5. Los `Resource` transforman los modelos a JSON con una salida consistente.
6. Las migraciones, seeders y factories preparan y alimentan la base de datos.

```text
Roig-Arena/
├── setup-arena.sh              # Setup inicial del entorno
├── arena.sh                    # Reinicio completo del entorno
└── roig-arena/                 # Aplicación Laravel
    ├── app/
    │   ├── Http/
    │   │   ├── Controllers/
    │   │   │   ├── Auth/       # Login, registro y cierre de sesión
    │   │   │   └── Web/        # Vistas web puntuales
    │   │   ├── Middleware/      # Ej. protección de rutas de admin
    │   │   └── Resources/      # Formateo de respuestas JSON
    │   ├── Models/              # Modelo de dominio y relaciones Eloquent
    │   └── Services/            # Lógica de negocio más compleja
    ├── bootstrap/               # Arranque de la aplicación
    ├── config/                  # Configuración de Laravel y paquetes
    ├── database/
    │   ├── migrations/          # Esquema de tablas
    │   ├── factories/           # Datos falsos para tests/seeders
    │   └── seeders/             # Carga inicial de datos
    ├── public/                  # Entrada pública y assets compilados
    ├── resources/               # Vistas, CSS y JS fuente
    ├── routes/
    │   ├── api.php              # Rutas REST de la API
    │   ├── web.php              # Rutas web
    │   └── console.php          # Tareas de consola
    └── tests/                   # Tests de PHPUnit
```

### Qué hace cada capa

- `routes/api.php`: define el mapa de endpoints públicos, protegidos por `auth:sanctum` y administradores con `admin`.
- `app/Http/Controllers/`: recibe la petición, valida entrada y devuelve la respuesta. Aquí viven los controladores de autenticación, eventos, reservas, compras, artistas, asientos y sectores.
- `app/Http/Resources/`: formatea la salida JSON para que la API no exponga directamente toda la estructura interna del modelo.
- `app/Services/`: concentra reglas de negocio que no deberían vivir en el controlador. Por ejemplo, la reserva bloquea un asiento con transacción y control de concurrencia.
- `app/Models/`: representa las tablas y relaciones Eloquent. Aquí están las reglas de consulta, scopes y métodos como disponibilidad de eventos, estado de asientos o generación de códigos QR.
- `database/migrations/`: define el esquema físico. La migración principal crea sectores, asientos, eventos, artistas, precios, estados de asientos y entradas.
- `database/seeders/`: introduce datos base para poder arrancar el proyecto y probar la API desde el principio.
- `database/factories/`: genera datos de prueba para tests y cargas masivas.
- `tests/`: contiene la batería de PHPUnit que verifica endpoints y reglas principales.

### Flujo funcional de la aplicación

#### 1. Autenticación

El usuario se registra o inicia sesión contra `AuthController`. En el registro se crea el usuario, se genera un token de Sanctum y se devuelve junto con los datos del usuario. En el login API se validan credenciales, se invalidan tokens anteriores y se emite uno nuevo.

#### 2. Consulta pública

Las rutas públicas permiten listar eventos, consultar su detalle, ver sectores, asientos y artistas. En esta capa el controlador suele apoyarse en relaciones Eloquent como `precios`, `sectores` o `artistas` y en recursos como `EventoResource` para devolver una respuesta limpia y homogénea.

#### 3. Reserva de asientos

Cuando un usuario autenticado reserva un asiento, `ReservaController` delega en `ReservaService`. Ese servicio abre una transacción, bloquea el registro para evitar carreras simultáneas, comprueba que el sector esté disponible para el evento y crea un registro en `estado_asientos` con estado `RESERVADO` y una caducidad de 15 minutos.

#### 4. Compra y confirmación

La compra convierte una reserva temporal en una venta definitiva. `CompraController` valida las reservas, verifica que no hayan expirado y que pertenezcan al usuario autenticado, crea una entrada en `entradas` y marca el asiento como `OCUPADO`. La entrada guarda el código QR que luego servirá para validación.

#### 5. Administración

Las rutas de administración están protegidas con `auth:sanctum` y el middleware `admin`. Desde ahí se pueden crear, editar o eliminar eventos, sectores y artistas. La eliminación de un evento está restringida si ya tiene entradas vendidas.

### Archivos clave del dominio

- `app/Models/Evento.php`: centraliza la lógica del evento, sus relaciones con precios, sectores, artistas, estados de asientos y entradas, además de scopes como eventos futuros.
- `app/Models/Sector.php`: representa las zonas del recinto y permite consultar sus asientos y su disponibilidad.
- `app/Models/Asiento.php`: modela cada butaca física y expone métodos para saber si está libre, reservada u ocupada para un evento concreto.
- `app/Models/EstadoAsiento.php`: controla el ciclo de vida de cada asiento por evento, incluyendo reservas temporales y ventas definitivas.
- `app/Models/Entrada.php`: representa la compra confirmada y genera automáticamente el código QR único.
- `app/Services/ReservaService.php`: concentra la lógica crítica de bloqueo y expiración de reservas.
- `database/seeders/DatabaseSeeder.php`: ejecuta los seeders en orden para que primero existan sectores, luego eventos, asientos, usuarios, precios y artistas.

### Orden de carga de datos iniciales

El orden de los seeders importa, porque varias tablas dependen de otras:

1. `SectorSeeder` crea las zonas base del recinto.
2. `EventoSeeder` crea los eventos.
3. `AsientoSeeder` genera los asientos físicos por sector.
4. `UserSeeder` crea usuarios de prueba o administradores.
5. `PrecioSeeder` asigna precios por evento y sector.
6. `ArtistaSeeder` vincula artistas con sus eventos.

Este orden permite que la base de datos quede lista para consumir la API y para ejecutar tests sin pasos manuales adicionales.

### Resumen rápido del flujo

- Un cliente consume la API por `routes/api.php`.
- Los controladores validan y responden.
- Los servicios resuelven la lógica sensible de reservas y compras.
- Los modelos representan el estado real de eventos, asientos y entradas.
- Los recursos estandarizan la salida JSON.
- Los seeders y migraciones dejan el entorno preparado para desarrollo y pruebas.

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

## Tecnologías

- [Laravel 12](https://laravel.com/)
- [Laravel Sail](https://laravel.com/docs/sail) (Docker)
- [Laravel Sanctum](https://laravel.com/docs/sanctum) (autenticación API)
- MySQL 8
- PHPUnit (tests)
