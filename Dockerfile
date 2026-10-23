# Railway memakai file ini (bukan auto-deteksi Railpack/Node) karena ada
# Dockerfile di root. `npm run build` TIDAK dijalankan — tidak ada Blade
# yang memakai @vite (frontend murni Bootstrap CDN + inline script).
FROM php:8.2-cli

# Ekstensi PHP yang dibutuhkan Laravel 10 + dompdf + maatwebsite/excel
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libpng-dev libzip-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Batas upload foto 20 MB (default php:8.2-cli hanya 2 MB / 8 MB).
RUN printf "upload_max_filesize=20M\npost_max_size=25M\n" > /usr/local/etc/php/conf.d/zz-uploads.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

# migrate --seed aman dijalankan tiap start: semua seeder idempotent
# (updateOrCreate/firstOrCreate) dan akun demo otomatis di-skip
# saat APP_ENV=production.
CMD php artisan migrate --force --seed && php artisan storage:link; php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
