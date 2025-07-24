FROM php:8.3-fpm

# Install dependency
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working dir
WORKDIR /var/www/html

# Copy source code
COPY . .

EXPOSE 8000

# Install Laravel deps
RUN composer install --no-dev --optimize-autoloader \
    && php artisan config:cache

# Set permission for storage and bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Expose port for Laravel's built-in server
# EXPOSE $PORT

# Start Laravel server
# CMD php -r "\$port = getenv('PORT') ?: 8000; passthru('php artisan serve --host=0.0.0.0 --port='.\$port);"
CMD php artisan serve --host=0.0.0.0 --port=8000

