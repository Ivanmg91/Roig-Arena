#!/usr/bin/env bash

if [ -z "${BASH_VERSION:-}" ]; then
    exec bash "$0" "$@"
fi

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
has_cmd() { command -v "$1" >/dev/null 2>&1; }

upsert_env_var() {
    local key="$1"
    local value="$2"
    if grep -qE "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    elif grep -qE "^#\s*${key}=" .env; then
        sed -i "s|^#\s*${key}=.*|${key}=${value}|" .env
    else
        echo "${key}=${value}" >> .env
    fi
}

log "Iniciando configuración inicial para Arena con Base de Datos en la Nube (AWS)..."

if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
        ok "Archivo .env creado desde .env.example"
    else
        fail "No se encontró .env.example"
    fi
else
    ok "Archivo .env ya existe"
fi

# Pedir la IP de AWS al usuario
echo -e "${YELLOW}"
read -p "¿Cuál es la IP pública de tu servidor AWS EC2?: " AWS_IP
echo -e "${NC}"

if [ -z "$AWS_IP" ]; then
    fail "La IP no puede estar vacía. Abortando."
fi

log "Aplicando variables de entorno para conectarse a AWS MySQL..."
upsert_env_var "DB_CONNECTION" "mysql"
upsert_env_var "DB_HOST" "$AWS_IP"
upsert_env_var "DB_PORT" "3306"
upsert_env_var "DB_DATABASE" "arena2"
upsert_env_var "DB_USERNAME" "arena2_user"
upsert_env_var "DB_PASSWORD" "Arena2Pass2024\!Student"
ok "Variables DB_* configuradas para apuntar a $AWS_IP."

log "Instalando dependencias de Composer..."
COMPOSER_DONE=0

if has_cmd composer && has_cmd php; then
    LOCAL_PHP_VERSION="$(php -r 'echo PHP_VERSION;')"
    if [ "$(printf '%s\n' "8.2.0" "$LOCAL_PHP_VERSION" | sort -V | head -n1)" = "8.2.0" ]; then
        if composer install; then
            COMPOSER_DONE=1
        else
            warn "composer install local falló. Se reintenta con contenedor Composer."
        fi
    else
        warn "PHP local ($LOCAL_PHP_VERSION) es < 8.2. Se usa contenedor Composer."
    fi
else
    warn "Composer o PHP local no disponible. Se usa contenedor Composer."
fi

if [ "$COMPOSER_DONE" -ne 1 ]; then
    docker run --rm \
        -u "$(id -u):$(id -g)" \
        -v "$(pwd):/var/www/html" \
        -w /var/www/html \
        laravelsail/php82-composer:latest \
        composer install --ignore-platform-reqs
fi

[ -x ./vendor/bin/sail ] || fail "No se pudo generar ./vendor/bin/sail. Revisa el resultado de composer install."
ok "Sail instalado correctamente."

echo ""
ok "Bootstrap completado. Ejecuta './arena-cloud.sh' para levantar el entorno y migrar la base de datos remota."