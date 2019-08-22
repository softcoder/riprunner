# Use the official PHP 7.2 image.
# https://hub.docker.com/_/php
FROM php:7.2-apache
MAINTAINER Mark Vejvoda <mark_vejvoda@hotmail.com>
ENV REFRESHED_AT 2019-08-21

# Install git
RUN apt-get update && \
    apt-get install -y git
    
# Install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    mv composer.phar /usr/local/bin/composer

# Install extensions through the scripts the image provides
# Here we install the pdo_mysql extension to access MySQL.
RUN docker-php-ext-install pdo_mysql

# Copy local code to the container image.
COPY ./ /var/www/html/

# Allow twig to create its cache etc
RUN chown -R www-data:www-data /var/www/html/

# Install php app dependencies
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --working-dir /var/www/html/

# Enable mode rewrite for routes.php (http://flightphp.com/)
RUN a2enmod rewrite

# Use the PORT environment variable in Apache configuration files.
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Configure PHP for development.
# Switch to the production php.ini for production operations.
# RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
# https://hub.docker.com/_/php#configuration
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

