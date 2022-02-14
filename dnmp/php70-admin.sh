#!/bin/sh

nohup /usr/bin/php /var/www/html/admin/yii monitor 2>&1 &
/usr/sbin/php-fpm7.0 -c /etc/php/7.0/fpm/php.ini -y /etc/php/7.0/fpm/php-fpm.conf