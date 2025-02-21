FROM php:8.0-fpm

RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    && docker-php-ext-install pdo_mysql mbstring zip curl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
