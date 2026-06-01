# --------------------------------------------------------------------
# Stage 1: build frontend assets
# --------------------------------------------------------------------
FROM node:18-alpine AS frontend
WORKDIR /app
COPY package*.json ./
RUN npm install --legacy-peer-deps --no-audit --no-fund
COPY webpack.mix.js webpack-rtl.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run prod || npm run production

# --------------------------------------------------------------------
# Stage 2: PHP/composer build
# --------------------------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader \
    --prefer-dist --no-progress --no-interaction
COPY . ./
RUN composer dump-autoload --optimize --no-dev

# --------------------------------------------------------------------
# Stage 3: runtime image
# --------------------------------------------------------------------
FROM php:8.2-fpm-alpine AS runtime
RUN apk add --no-cache \
        nginx supervisor curl bash icu-dev libpng-dev libjpeg-turbo-dev \
        libwebp-dev libzip-dev oniguruma-dev tzdata \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        intl pdo pdo_mysql mbstring bcmath zip gd opcache pcntl

# Production-grade php.ini tweaks
COPY docker/php/php.ini  /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-www.conf

# Nginx + supervisor
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf

WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
COPY --from=frontend /app/public /var/www/html/public

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && mkdir -p /var/log/supervisor

EXPOSE 80
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
