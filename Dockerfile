# ---- Tahap 1: pasang dependensi Composer ----
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts --ignore-platform-reqs

# ---- Tahap 2: image akhir (Nginx + PHP-FPM dalam satu container) ----
FROM serversideup/php:8.4-fpm-nginx

USER root
# intl: Filament | gd + exif: konversi foto menu (Spatie Media Library)
RUN install-php-extensions intl gd exif
USER www-data

COPY --chown=www-data:www-data --from=vendor /app /var/www/html

# Salin aset Filament (CSS/JS/font) ke public/. Dilewati di tahap Composer karena memakai --no-scripts.
# APP_KEY di sini hanya tiruan supaya perintah bisa jalan saat build; kunci asli diambil dari .env saat container berjalan.
RUN APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= php artisan filament:assets

# Saat container start, image ini otomatis menjalankan: migrate --force, storage:link, dan optimize
ENV AUTORUN_ENABLED=true \
    PHP_OPCACHE_ENABLE=1
