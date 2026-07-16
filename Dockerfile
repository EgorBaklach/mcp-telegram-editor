FROM php:8.3-cli-alpine

# Copy composer from the official image
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Install system dependencies and compile PHP extensions for pdo_pgsql and zip
RUN apk add --no-cache libzip-dev zip unzip postgresql-dev && \
    docker-php-ext-install pdo pdo_pgsql zip

# Set memory limits for PHP to preserve host resources
RUN echo "memory_limit=128M" > /usr/local/etc/php/conf.d/memory-limit.ini

WORKDIR /app

# Run composer install and start the PHP dev server serving the public/ directory
CMD composer install --no-interaction --prefer-dist --optimize-autoloader && \
    php -S 0.0.0.0:9000 -t public public/index.php
