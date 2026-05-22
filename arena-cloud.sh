#!/bin/bash

if [ -z "${BASH_VERSION:-}" ]; then
    exec bash "$0" "$@"
fi

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' 

echo -e "${BLUE}>>> Levantando entorno Arena (Conectado a AWS)...${NC}\n"

# 1. Asegurar que estamos en el directorio correcto
# Ajusta esto si tu carpeta se llama de otra forma, según tu script original
if [ -d "roig-arena" ]; then
    cd roig-arena || { echo "Error al entrar en roig-arena"; exit 1; }
fi

# 2. Detener contenedores (sin borrar volúmenes locales porque la BD está en la nube)
echo -e "${YELLOW}[1/4] Reiniciando servicios de Sail...${NC}"
./vendor/bin/sail down
./vendor/bin/sail up -d
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✔ Contenedores levantados correctamente.${NC}\n"
else
    echo -e "${RED}Error al levantar contenedores${NC}"; exit 1
fi

# 3. Asegurar APP_KEY
if ! grep -q '^APP_KEY=base64:' .env; then
    echo -e "${YELLOW}[2/4] APP_KEY vacía. Generando clave de aplicación...${NC}"
    ./vendor/bin/sail artisan key:generate --force
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✔ APP_KEY generada correctamente.${NC}\n"
    else
        echo -e "${RED}Error al generar APP_KEY${NC}"; exit 1
    fi
else
    echo -e "${GREEN}✔ APP_KEY ya configurada.${NC}\n"
fi

# 4. Ejecutar migraciones y seeders en AWS
echo -e "${YELLOW}[3/4] Conectando a AWS para limpiar y poblar la Base de Datos...${NC}"
echo "Esto puede tardar unos segundos dependiendo de tu conexión a internet..."
./vendor/bin/sail artisan migrate:fresh --seed
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✔ Base de datos remota lista y poblada.${NC}\n"
else
    echo -e "${RED}Error en las migraciones remotas. Revisa que tu IP de AWS sea correcta en el .env y que el Security Group esté bien configurado.${NC}"; exit 1
fi

# 5. Ejecutar tests 
# (Recuerda tener configurado phpunit.xml para usar SQLite en memoria según la guía 04)
echo -e "${YELLOW}[4/4] Ejecutando batería de tests...${NC}"
./vendor/bin/sail artisan test
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✔ Todos los tests han pasado correctamente.${NC}\n"
else
    echo -e "${RED}Alerta: Algunos tests han fallado.${NC}\n"
fi

echo -e "${BLUE}>>> ¡Entorno híbrido listo! Tu app local está conectada a la BD de AWS. <<<${NC}"