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


# Pendiente
- Q no se vean las cards de eventos gigantes si solo hay 1 o 2