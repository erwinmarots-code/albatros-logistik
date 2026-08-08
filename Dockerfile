FROM php:8.2-apache

# Install sistem dependencies termasuk zip/unzip
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy semua file project
COPY . /var/www/html

# Install Laravel dependencies (dari folder backend)
RUN cd backend && composer install --no-dev --optimize-autoloader \
    && cd backend && php artisan key:generate \
    && cd backend && php artisan migrate --force

# Expose port 80
EXPOSE 80

# Jalankan Apache
CMD ["apache2-foreground"]