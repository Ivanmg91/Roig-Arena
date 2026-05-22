#!/usr/bin/env bash

set -Eeuo pipefail

GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

log() { echo -e "${BLUE}>>> $*${NC}"; }
ok() { echo -e "${GREEN}[OK] $*${NC}"; }
warn() { echo -e "${YELLOW}[WARN] $*${NC}"; }
fail() { echo -e "${RED}[ERROR] $*${NC}"; exit 1; }

upsert_env_var() {
    local key="$1"
    local value="$2"
    local file="$3"
    if grep -qE "^${key}=" "$file"; then
        sed -i "s|^${key}=.*|${key}=${value}|" "$file"
    elif grep -qE "^#\s*${key}=" "$file"; then
        sed -i "s|^#\s*${key}=.*|${key}=${value}|" "$file"
    else
        echo "${key}=${value}" >> "$file"
    fi
}

log "Iniciando configuración automática para AWS..."

# 1. Crear .env.cloud
if [ ! -f .env.cloud ]; then
    if [ -f .env.example ]; then
        cp .env.example .env.cloud
        ok "Archivo .env.cloud creado desde .env.example"
    else
        fail "No se encontró .env.example para crear la base."
    fi
else
    ok "El archivo .env.cloud ya existe. Se actualizará la IP."
fi

# 2. Pedir la IP de AWS
echo -e "${YELLOW}"
read -p "¿Cuál es la IP pública de tu servidor AWS EC2?: " AWS_IP
echo -e "${NC}"

if [ -z "$AWS_IP" ]; then
    fail "La IP no puede estar vacía. Abortando."
fi

# 3. Configurar variables en .env.cloud
log "Inyectando credenciales de AWS en .env.cloud..."
upsert_env_var "DB_CONNECTION" "mysql" ".env.cloud"
upsert_env_var "DB_HOST" "$AWS_IP" ".env.cloud"
upsert_env_var "DB_PORT" "3306" ".env.cloud"
upsert_env_var "DB_DATABASE" "arena2" ".env.cloud"
upsert_env_var "DB_USERNAME" "arena2_user" ".env.cloud"
upsert_env_var "DB_PASSWORD" "Arena2Pass2024\!Student" ".env.cloud"

# Añadimos permisos de usuario (Sail hace esto por detrás, lo necesitamos explícito aquí)
upsert_env_var "WWWUSER" "$(id -u)" ".env.cloud"
upsert_env_var "WWWGROUP" "$(id -g)" ".env.cloud"

ok "Credenciales guardadas en .env.cloud apuntando a $AWS_IP."

# 4. Crear compose.cloud.yaml dinámicamente si no existe
if [ ! -f compose.cloud.yaml ]; then
    log "Generando compose.cloud.yaml sin el contenedor de MySQL local..."
    cat << 'EOF' > compose.cloud.yaml
services:
    laravel.test:
        build:
            context: './vendor/laravel/sail/runtimes/8.5'
            dockerfile: Dockerfile
            args:
                WWWGROUP: '${WWWGROUP}'
        image: 'sail-8.5/app'
        extra_hosts:
            - 'host.docker.internal:host-gateway'
        ports:
            - '${APP_PORT:-8080}:80'
            - '${VITE_PORT:-5173}:${VITE_PORT:-5173}'
        environment:
            WWWUSER: '${WWWUSER}'
            LARAVEL_SAIL: 1
            XDEBUG_MODE: '${SAIL_XDEBUG_MODE:-off}'
            XDEBUG_CONFIG: '${SAIL_XDEBUG_CONFIG:-client_host=host.docker.internal}'
            IGNITION_LOCAL_SITES_PATH: '${PWD}'
        volumes:
            - '.:/var/www/html'
        networks:
            - sail
        depends_on:
            - redis
            - meilisearch
            - mailpit
            - selenium
    redis:
        image: 'redis:alpine'
        ports:
            - '${FORWARD_REDIS_PORT:-6379}:6379'
        volumes:
            - 'sail-redis:/data'
        networks:
            - sail
    meilisearch:
        image: 'getmeili/meilisearch:latest'
        ports:
            - '${FORWARD_MEILISEARCH_PORT:-7700}:7700'
        volumes:
            - 'sail-meilisearch:/meili_data'
        networks:
            - sail
    mailpit:
        image: 'axllent/mailpit:latest'
        ports:
            - '${FORWARD_MAILPIT_PORT:-1025}:1025'
            - '${FORWARD_MAILPIT_DASHBOARD_PORT:-8025}:8025'
        networks:
            - sail
    selenium:
        image: selenium/standalone-chromium
        volumes:
            - '/dev/shm:/dev/shm'
        networks:
            - sail
networks:
    sail:
        driver: bridge
volumes:
    sail-redis:
        driver: local
    sail-meilisearch:
        driver: local
EOF
    ok "compose.cloud.yaml creado."
else
    ok "compose.cloud.yaml ya existe."
fi

echo ""
ok "¡Todo listo! Ejecuta './arena-cloud.sh' para conectarte a AWS."