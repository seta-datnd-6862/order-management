# =========================================================
# Stage 1 — build asset frontend (Vite + Tailwind v4)
# =========================================================
FROM node:22-bookworm-slim AS assets
WORKDIR /app
# Repo không có package-lock.json nên dùng `npm install`, không phải `npm ci`.
COPY package.json ./
RUN npm install --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build          # -> /app/public/build

# =========================================================
# Stage 2 — runtime PHP-FPM (cũng là image cho scheduler)
# =========================================================
FROM php:8.3-fpm-bookworm AS app

# libzip/libpng/libonig: bắt buộc cho maatwebsite/excel (PhpSpreadsheet)
# default-mysql-client: cho `php artisan db:backup` (mysqldump)
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip default-mysql-client \
        libicu-dev libzip-dev libpng-dev libjpeg62-turbo-dev \
        libfreetype6-dev libonig-dev libxml2-dev \
 && docker-php-ext-configure gd --with-jpeg --with-freetype \
 && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql mbstring bcmath zip gd intl exif pcntl opcache \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html

# Cài vendor trước, tách layer để đổi code không phải cài lại composer.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader \
        --prefer-dist --no-interaction

COPY . .
COPY --from=assets /app/public/build ./public/build

# Cache của máy dev nếu lọt vào thì sẽ trỏ tới package --no-dev không có.
RUN rm -f bootstrap/cache/packages.php bootstrap/cache/services.php

# --no-scripts: package:discover cần .env, mà lúc build thì chưa có.
# entrypoint sẽ chạy nó khi container khởi động.
RUN composer dump-autoload --optimize --no-dev --no-scripts \
 && chown -R www-data:www-data storage bootstrap/cache

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# =========================================================
# Stage 3 — Nginx nội bộ, serve public/ và đẩy .php sang om-app
# =========================================================
FROM nginx:alpine AS web
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
