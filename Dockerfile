FROM php:8.4-fpm-alpine

# Install dependencies for PHP extensions
RUN apk add --no-cache \
  libzip-dev \
  zip \
  unzip \
  && docker-php-ext-install pdo pdo_mysql zip

WORKDIR /var/www/html

COPY . .
COPY php.ini-production /usr/local/etc/php/conf.d/custom.ini

RUN mkdir -p /var/www/html/uploads && \
  mkdir -p /var/www/html/media && \
  mkdir -p /var/www/html/panel-admin/uploads && \
  chown -R www-data:www-data /var/www/html && \
  chmod -R 755 /var/www/html/uploads
