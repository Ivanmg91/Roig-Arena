#!/bin/bash

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' 

echo -e "${BLUE}>>> Levantando entorno Arena (Conectado a AWS)...${NC}\n"

# Verificar que los archivos cloud existen
if [ ! -f .env.cloud ] || [ ! -f compose.cloud.yaml ]; then
    echo -e "${RED}Error: Faltan archivos de configuración cloud.${NC}"
    echo -e "Por favor, ejecuta primero: ./setup-arena-cloud.sh"
    exit 1
fi

# Comando base de Docker Compose indicando que use SÓLO los archivos de la nube
DOCKER_CMD="docker compose --env-file .env.cloud -f compose.cloud.yaml"

echo -e "${YELLOW}[1/4] Reiniciando servicios en la nube...${NC}"
$DOCKER_CMD down
$DOCKER_CMD up -d

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✔ Contenedores levantados usando .env.cloud.${NC}\n"
else
    echo -e "${RED}Error al levantar contenedores${NC}"; exit 1
fi

# Comprobar APP_KEY en la nube
if ! grep -q '^APP_KEY=base64:' .env.cloud; then
    echo -e "${YELLOW}[2/4] Generando APP_KEY en .env.cloud...${NC}"
    $DOCKER_CMD exec -u sail laravel.test php artisan key:generate --force
    echo -e "${GREEN}✔ APP_KEY generada correctamente.${NC}\n"
else
    echo -e "${GREEN}✔ APP_KEY ya configurada.${NC}\n"
fi

# Migraciones en AWS (usando el usuario 'sail' dentro del contenedor)
echo -e "${YELLOW}[3/4] Conectando a AWS para limpiar y poblar la Base de Datos...${NC}"
echo "Esto puede tardar unos segundos dependiendo de tu conexión a internet..."
$DOCKER_CMD exec -u sail laravel.test php artisan migrate:fresh --seed

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✔ Base de datos remota lista y poblada.${NC}\n"
else
    echo -e "${RED}Error en las migraciones. Revisa tu conexión, la IP y los Security Groups en AWS.${NC}"; exit 1
fi

# Ejecutar tests
echo -e "${YELLOW}[4/4] Ejecutando batería de tests...${NC}"
$DOCKER_CMD exec -u sail laravel.test php artisan test

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✔ Todos los tests han pasado correctamente.${NC}\n"
else
    echo -e "${RED}Alerta: Algunos tests han fallado.${NC}\n"
fi

echo -e "${BLUE}>>> ¡Entorno híbrido listo! Tu app local está conectada a la BD de AWS sin afectar tu configuración local. <<<${NC}"