FROM php:8.1-fpm

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
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN echo "* * * * * root php /var/www/html/artisan schedule:run >> /dev/null 2>&1" > /etc/cron.d/laravel_scheduler \
    && chmod 0644 /etc/cron.d/laravel_scheduler \
    && crontab /etc/cron.d/laravel_scheduler

CMD cron && php artisan serve --host=0.0.0.0 --port=8000

EXPOSE 8000
