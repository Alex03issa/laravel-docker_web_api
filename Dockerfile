# Use PHP 8.1 FPM image
FROM php:8.1-fpm

# Install required dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    curl \
    zip \
    unzip \
    git \
    libzip-dev \
    cron \
    supervisor \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set the working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Set proper permissions for Laravel directories
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Ensure PHP extensions are installed
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install Laravel Sanctum
RUN composer require laravel/sanctum:^3.2

# Publish Sanctum's configuration
RUN php artisan vendor:publish --provider="Laravel\\Sanctum\\SanctumServiceProvider"

# Add Laravel scheduler to crontab
RUN echo "* * * * * /usr/local/bin/php /var/www/html/artisan schedule:run >> /var/log/laravel_scheduler.log 2>&1" >> /etc/crontab \
    && chmod 0644 /etc/crontab \
    && crontab /etc/crontab

# Copy Supervisor configuration
COPY supervisor.conf /etc/supervisor/conf.d/supervisord.conf

# Expose port for PHP server
EXPOSE 8000

# Run Supervisor to manage cron and PHP
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
