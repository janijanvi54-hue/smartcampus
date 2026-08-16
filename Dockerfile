# DSVV SmartCampus - Docker image for Render (PHP + Apache)
# Render no longer provides a native PHP runtime, so we ship a Docker image.
FROM php:8.2-apache

# PHP extensions required by the app (PDO + MySQL driver).
RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite headers

# Deploy the application to the Apache document root.
WORKDIR /var/www/html
COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

# Apache listens on port 80; Render routes external traffic to it.
EXPOSE 80
