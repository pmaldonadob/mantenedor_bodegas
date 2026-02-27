# Dockerfile
# PHP 7.4 con Apache + extensión PDO PostgreSQL

FROM php:7.4-apache

RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

EXPOSE 80
