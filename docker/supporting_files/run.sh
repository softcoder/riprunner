#!/bin/bash

VOLUME_HOME="/var/lib/mysql"

echo PHP Apache max upload config...
if [ -e /etc/php/5.6/apache2/php.ini ]
then
    sed -ri -e "s/^upload_max_filesize.*/upload_max_filesize = ${PHP_UPLOAD_MAX_FILESIZE}/" \
        -e "s/^post_max_size.*/post_max_size = ${PHP_POST_MAX_SIZE}/" /etc/php/5.6/apache2/php.ini
elif [ -e /etc/php/7.3/apache2/php.ini ]
then
    sed -ri -e "s/^upload_max_filesize.*/upload_max_filesize = ${PHP_UPLOAD_MAX_FILESIZE}/" \
        -e "s/^post_max_size.*/post_max_size = ${PHP_POST_MAX_SIZE}/" /etc/php/7.3/apache2/php.ini
elif [ -e /etc/php/8.3/apache2/php.ini ]
then
    sed -ri -e "s/^upload_max_filesize.*/upload_max_filesize = ${PHP_UPLOAD_MAX_FILESIZE}/" \
        -e "s/^post_max_size.*/post_max_size = ${PHP_POST_MAX_SIZE}/" /etc/php/8.3/apache2/php.ini
else
    sed -ri -e "s/^upload_max_filesize.*/upload_max_filesize = ${PHP_UPLOAD_MAX_FILESIZE}/" \
        -e "s/^post_max_size.*/post_max_size = ${PHP_POST_MAX_SIZE}/" /etc/php/8.4/apache2/php.ini
fi


sed -i "s/export APACHE_RUN_GROUP=www-data/export APACHE_RUN_GROUP=staff/" /etc/apache2/envvars

if [ -n "$APACHE_ROOT" ];then
    echo APACHE root handler...
    ls -lat /var/www/html
    ls -lat /app
    ls -lat /app/${APACHE_ROOT}

    rm -f /var/www/html && ln -s "/app/${APACHE_ROOT}" /var/www/html
fi

echo PHP myadmin config...
sed -i -e "s/cfg\['blowfish_secret'\] = ''/cfg['blowfish_secret'] = '`date | md5sum`'/" /var/www/phpmyadmin/config.inc.php

mkdir -p /var/run/mysqld

echo Apache/MySQL write permissions config...
if [ -n "$VAGRANT_OSX_MODE" ];then
    echo VAGRANT : Apache/MySQL write permissions chown...
    usermod -u $DOCKER_USER_ID www-data
    groupmod -g $(($DOCKER_USER_GID + 10000)) $(getent group $DOCKER_USER_GID | cut -d: -f1)
    groupmod -g ${DOCKER_USER_GID} staff
    chmod -R 770 /var/lib/mysql
    chmod -R 770 /var/run/mysqld
    chown -R www-data:staff /var/lib/mysql
    chown -R www-data:staff /var/run/mysqld
else
    # Tweaks to give Apache/PHP write permissions to the app
    echo Apache/MySQL write permissions chown...
    #chown -R www-data:staff /var/www
    #echo Apache/MySQL write permissions no-1 chown...
    #chown -R www-data:staff /app
    echo Apache/MySQL write permissions no-2 chown...
    chown -R www-data:staff /var/lib/mysql
    echo Apache/MySQL write permissions no-3 chown...
    chown -R www-data:staff /var/run/mysqld
    echo Apache/MySQL write permissions no-4 chown...
    chmod -R 770 /var/lib/mysql
    echo Apache/MySQL write permissions no-5 chown...
    chmod -R 770 /var/run/mysqld
fi

echo MySQL delete sock...
rm /var/run/mysqld/mysqld.sock

echo MySQL config...
sed -i "s/bind-address.*/bind-address = 0.0.0.0/" /etc/mysql/my.cnf
sed -i "s/user.*/user = www-data/" /etc/mysql/my.cnf

echo Checking MySQL $VOLUME_HOME/mysql ...

if [[ ! -d $VOLUME_HOME/mysql ]]; then
    echo "=> An empty or uninitialized MySQL volume is detected in $VOLUME_HOME"
    echo "=> Installing MySQL ..."

    # Try the 'preferred' solution
    mysqld --initialize-insecure > /dev/null 2>&1

    # IF that didn't work
    if [ $? -ne 0 ]; then
        # Fall back to the 'depreciated' solution
        mysql_install_db > /dev/null 2>&1
    fi

    echo "=> Done!"  
    /create_mysql_users.sh

    /create_riprunner_db.sh
else
    echo "=> Using an existing volume of MySQL"
fi

echo Running: supervisord -n
exec supervisord -n
