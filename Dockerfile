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

# Create Laravel Scheduler Log File
RUN touch /var/log/laravel_scheduler.log && chmod 666 /var/log/laravel_scheduler.log

# Add Laravel scheduler to crontab
RUN echo "* * * * * /usr/local/bin/php /var/www/html/artisan schedule:run >> /var/log/laravel_scheduler.log 2>&1" > /etc/cron.d/laravel_scheduler \
    && chmod 0644 /etc/cron.d/laravel_scheduler \
    && crontab /etc/cron.d/laravel_scheduler

# Ensure the cron service runs
RUN mkdir -p /var/log/cron && touch /var/log/cron/cron.log

# Copy Supervisor configuration
COPY supervisor.conf /etc/supervisor/conf.d/supervisord.conf

# Ensure Supervisor log files exist
RUN mkdir -p /var/log/supervisor && touch /var/log/supervisor/supervisord.log

# Expose port for PHP server
EXPOSE 8000

# Ensure Cron, Laravel Queue, and PHP Server are running via Supervisor
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
