FROM php:8.4-fpm-alpine

# Install dependencies for PHP extensions
RUN apk add --no-cache \
  libzip-dev \
  zip \
  unzip \
  && docker-php-ext-install pdo pdo_mysql zip

WORKDIR /var/www/html

COPY . .

RUN chown -R www-data:www-data /var/www/html
