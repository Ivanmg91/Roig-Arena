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

# Pendiente
- Q no se vean las cards de eventos gigantes si solo hay 1 o 2