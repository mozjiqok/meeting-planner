FROM php:8.4-fpm-alpine

# Install system deps
RUN apk add --no-cache \
    git curl unzip sqlite-dev \
    libzip-dev oniguruma-dev \
    && docker-php-ext-install pdo pdo_sqlite zip bcmath opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy app
COPY . .

# Install PHP dependencies (no dev)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
