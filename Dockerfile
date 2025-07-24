FROM php:8.3-cli

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

# Install Laravel deps
RUN composer install --no-dev --optimize-autoloader

# Set permission for storage and bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000

# Laravel akan otomatis baca environment variables dari Railway
CMD php artisan config:cache && \
    php artisan serve --host=0.0.0.0 --port=8000