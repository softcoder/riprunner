#!/bin/bash
echo "=> Installing RipRunner default database ..."
cd /app

/usr/bin/mysqld_safe > /dev/null 2>&1 &

RET=1
while [[ RET -ne 0 ]]; do
    echo "=> Waiting for confirmation of MySQL service startup"
    sleep 5
    mysql -uroot -e "status" > /dev/null 2>&1
    RET=$?
done

php install-db.php --fhid=0 --form_action=install --adminpwd=riprunner

mysqladmin -uroot shutdown

rm /app/install-db.php
cd ../
