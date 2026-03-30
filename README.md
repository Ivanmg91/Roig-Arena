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

## Tecnologías

- [Laravel 12](https://laravel.com/)
- [Laravel Sail](https://laravel.com/docs/sail) (Docker)
- [Laravel Sanctum](https://laravel.com/docs/sanctum) (autenticación API)
- MySQL 8
- PHPUnit (tests)
