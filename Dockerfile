# ---------- 1. Assets front (Vite + Tailwind) ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY resources ./resources
COPY vite.config.js ./
COPY app/Livewire ./app/Livewire
RUN npm run build

# ---------- 2. Application PHP (Nginx + PHP-FPM) ----------
FROM serversideup/php:8.3-fpm-nginx AS app
USER root
RUN install-php-extensions intl gd bcmath exif pdo_mysql
USER www-data
WORKDIR /var/www/html

COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader

COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev \
 && php artisan filament:assets \
 && php artisan livewire:publish --assets

# Au démarrage : migrations, storage:link et caches Laravel
ENV AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_MIGRATION=true \
    AUTORUN_LARAVEL_STORAGE_LINK=true \
    PHP_OPCACHE_ENABLE=1 \
    PHP_UPLOAD_MAX_FILE_SIZE=10M \
    PHP_POST_MAX_SIZE=40M \
    SSL_MODE=off
