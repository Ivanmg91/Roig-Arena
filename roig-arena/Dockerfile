FROM composer:2 AS composer_deps

WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --no-scripts \
    --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize --no-dev

FROM node:20-alpine AS frontend_builder

WORKDIR /var/www/html
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY public ./public
COPY vite.config.js ./
RUN npm run build

FROM php:8.2-cli-alpine AS runtime

WORKDIR /var/www/html

RUN apk add --no-cache bash icu-dev libzip-dev oniguruma-dev sqlite \
    && docker-php-ext-install pdo_mysql pdo_pgsql pdo_sqlite bcmath intl

COPY --from=composer_deps /var/www/html /var/www/html
COPY --from=frontend_builder /var/www/html/public/build /var/www/html/public/build
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

RUN chmod +x /usr/local/bin/entrypoint \
    && mkdir -p storage bootstrap/cache \
    && chown -R www-data:www-data /var/www/html

USER www-data

EXPOSE 10000

ENTRYPOINT ["entrypoint"]
