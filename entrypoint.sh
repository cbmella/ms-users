#!/bin/sh

# Ajustar permisos para los directorios storage y logs
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache

# Dar permisos de escritura al grupo y a otros
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Iniciar PHP-FPM en segundo plano
php-fpm &

# Iniciar Nginx en primer plano
nginx -g "daemon off;"
