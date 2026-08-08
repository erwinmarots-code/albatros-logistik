FROM php:8.2-apache

# Install sistem dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory di dalam container
WORKDIR /var/www/html

# Copy semua file project ke container
COPY . /var/www/html

# Pindah ke folder backend dan install Laravel
RUN cd backend && composer install --no-dev --optimize-autoloader \
    && cd backend && php artisan key:generate \
    && cd backend && php artisan migrate --force

# Expose port 80
EXPOSE 80

# Jalankan Apache
CMD ["apache2-foreground"]