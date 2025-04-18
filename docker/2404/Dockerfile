FROM phusion/baseimage:noble-1.0.1
MAINTAINER Matthew Rayner <hello@rayner.io>
ENV REFRESHED_AT 2025-04-02

# based on dgraziotin/lamp
# MAINTAINER Daniel Graziotin <daniel@ineed.coffee>

ENV DOCKER_USER_ID 501 
ENV DOCKER_USER_GID 20

ENV BOOT2DOCKER_ID 1000
ENV BOOT2DOCKER_GID 50

#ENV PHPMYADMIN_VERSION=4.9.0.1
ENV PHPMYADMIN_VERSION=5.2.2

# RipRunner Environment variables - START
ENV APP_DSN mysql:host=localhost;dbname=riprunner
# RipRunner Environment variables - END

WORKDIR /
# Use baseimage-docker's init system.
CMD ["/sbin/my_init"]

# Tweaks to give Apache/PHP write permissions to the app
#RUN usermod -u ${BOOT2DOCKER_ID} www-data && \
#    usermod -G staff www-data && \
#    useradd -r mysql && \
#    usermod -G staff mysql
RUN usermod -G staff www-data && \
    useradd -r mysql && \
    usermod -G staff mysql

RUN groupmod -g $(($BOOT2DOCKER_GID + 10000)) $(getent group $BOOT2DOCKER_GID | cut -d: -f1)
RUN groupmod -g ${BOOT2DOCKER_GID} staff

# Install packages
ENV DEBIAN_FRONTEND noninteractive
RUN add-apt-repository -y ppa:ondrej/php && \
  apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 4F4EA0AAE5267A6C && \
  apt-get update && \
  apt-get -y upgrade && \
  apt-get -y install supervisor wget git apache2 php-xdebug libapache2-mod-php mysql-server php-mysql pwgen php-apcu php8.4-mcrypt php-gd php-xml php-mbstring zip unzip php-zip curl php-curl && \
  apt-get -y autoremove && \
  echo "ServerName localhost" >> /etc/apache2/apache2.conf

# needed for phpMyAdmin
#RUN ln -s /etc/php/8.3/mods-available/mcrypt.ini /etc/php/8.3/mods-available/ && phpenmod mcrypt

# Add image configuration and scripts
ADD /docker/supporting_files/start-apache2.sh /start-apache2.sh
ADD /docker/supporting_files/start-mysqld.sh /start-mysqld.sh
ADD /docker/supporting_files/run.sh /run.sh
RUN chmod 755 /*.sh
ADD /docker/supporting_files/supervisord-apache2.conf /etc/supervisor/conf.d/supervisord-apache2.conf
ADD /docker/supporting_files/supervisord-mysqld.conf /etc/supervisor/conf.d/supervisord-mysqld.conf
ADD /docker/supporting_files/mysqld_innodb.cnf /etc/mysql/conf.d/mysqld_innodb.cnf

# Allow mysql to bind on 0.0.0.0
RUN sed -i "s/.*bind-address.*/bind-address = 0.0.0.0/" /etc/mysql/my.cnf && \
  sed -i "s/.*bind-address.*/bind-address = 0.0.0.0/" /etc/mysql/mysql.conf.d/mysqld.cnf

# Set PHP timezones to America/Vancouver
RUN sed -i "s/;date.timezone =/date.timezone = America\/Vancouver/g" /etc/php/8.4/apache2/php.ini
RUN sed -i "s/;date.timezone =/date.timezone = America\/Vancouver/g" /etc/php/8.4/cli/php.ini

# Remove pre-installed database
RUN rm -rf /var/lib/mysql

# Add MySQL utils
ADD /docker/supporting_files/create_mysql_users.sh /create_mysql_users.sh
RUN chmod 755 /*.sh

# Add RipRunner scripts
ADD /docker/supporting_files/create_riprunner_db.sh /create_riprunner_db.sh
RUN chmod 755 /*.sh

# Add phpmyadmin
RUN wget -O /tmp/phpmyadmin.tar.gz https://files.phpmyadmin.net/phpMyAdmin/${PHPMYADMIN_VERSION}/phpMyAdmin-${PHPMYADMIN_VERSION}-all-languages.tar.gz
RUN tar xfvz /tmp/phpmyadmin.tar.gz -C /var/www
RUN ln -s /var/www/phpMyAdmin-${PHPMYADMIN_VERSION}-all-languages /var/www/phpmyadmin
RUN mv /var/www/phpmyadmin/config.sample.inc.php /var/www/phpmyadmin/config.inc.php

# Tweaks to give Apache/PHP write permissions to the app
RUN chown -R www-data:staff /var/www

# Add composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php && \
    php -r "unlink('composer-setup.php');" && \
    mv composer.phar /usr/local/bin/composer

ENV MYSQL_PASS:-$(pwgen -s 12 1)
# config to enable .htaccess
ADD /docker/supporting_files/apache_default /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# Configure /app folder with riprunner app
RUN mkdir -p /app && rm -fr /var/www/html && ln -s /app /var/www/html
ADD /docker/app/ /app

# Remove temp app files
RUN rm -rf /app/temp/cache
RUN rm -rf /app/temp/twig

ADD /*.php /app/
ADD /*.json /app/
ADD /*.lock /app/
ADD /*.xml /app/
ADD /*.sql /app/
ADD /.check_version /app/
ADD /.htaccess /app/
ADD /angular-services/ /app/angular-services
ADD /apk/ /app/apk
ADD /authentication/ /app/authentication
ADD /cache/ /app/cache
ADD /config/ /app/config
ADD /controllers/ /app/controllers
ADD /core/ /app/core
ADD /data/ /app/data
ADD /db/ /app/db
ADD /fcm/ /app/fcm
ADD /gcm/ /app/gcm
ADD /googleae/ /app/googleae
ADD /images/ /app/images
ADD /js/ /app/js
ADD /kml/ /app/kml
ADD /ldap/ /app/ldap
ADD /models/ /app/models
ADD /ngui/ /app/ngui
ADD /plugins/ /app/plugins
ADD /rest/ /app/rest
ADD /secrets/config-secrets-default.json /app/secrets/config-secrets.json
ADD /signals/ /app/signals
ADD /sounds/ /app/sounds/
ADD /sql/ /app/sql
ADD /styles/ /app/styles
#ADD /tests/ /app/tests
ADD /url/ /app/url
ADD /views/ /app/views
ADD /webhooks/ /app/webhooks

ADD /docker/app_config/ /app

# Install php app dependencies
ENV COMPOSER_ALLOW_SUPERUSER 1
# RUN composer update --no-dev --prefer-dist --no-interaction --optimize-autoloader --working-dir app/
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --working-dir app/

#Environment variables to configure php
ENV PHP_UPLOAD_MAX_FILESIZE 10M
ENV PHP_POST_MAX_SIZE 10M

# Add volumes for the app and MySql
#VOLUME  ["/etc/mysql", "/var/lib/mysql", "/app" ]
VOLUME  ["/etc/mysql", "/var/lib/mysql" ]

# Tweaks to give Apache/PHP write permissions to the app
RUN chown -R www-data:staff /app
#RUN chown -R www-data:staff /var/lib/mysql
#RUN chown -R www-data:staff /var/run/mysqld

EXPOSE 80 3306
CMD ["/run.sh"]
