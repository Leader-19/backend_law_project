
# ==========================================
# Laravel Production Dockerfile
# PHP 8.3 + PHP-FPM + Composer
# ==========================================

FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

# System dependencies
RUN apk add --no-cache \
    bash \
    git \
    curl \
    unzip \
    icu-libs \
    libzip \
    libpng \
    freetype \
    libjpeg-turbo \
    oniguruma

# PHP extension build dependencies
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    icu-dev \
    libzip-dev \
    libpng-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    oniguruma-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        bcmath \
        exif \
        pcntl \
        gd \
        zip \
        intl \
        opcache \
    && apk del .build-deps

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_NO_INTERACTION=1

# Copy Composer files first for Docker layer caching
COPY composer.json composer.lock ./

# Install production dependencies
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --no-scripts

# Copy Laravel application
COPY . .

# Prepare Laravel directories and autoload files
RUN mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && composer dump-autoload \
        --no-dev \
        --optimize \
        --no-scripts \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

# Production PHP configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Enable OPcache
RUN { \
    echo "opcache.enable=1"; \
    echo "opcache.enable_cli=0"; \
    echo "opcache.memory_consumption=128"; \
    echo "opcache.interned_strings_buffer=16"; \
    echo "opcache.max_accelerated_files=20000"; \
    echo "opcache.validate_timestamps=0"; \
    } > "$PHP_INI_DIR/conf.d/opcache-recommended.ini"

EXPOSE 9000

CMD ["php-fpm"]
