FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && echo "DirectoryIndex index.php index.html" > /etc/apache2/conf-available/book-manager.conf \
    && a2enconf book-manager \
    && printf '%s\n' '#!/bin/sh' 'set -e' 'PORT="${PORT:-80}"' 'echo "Listen ${PORT}" > /etc/apache2/ports.conf' 'sed -i "s/<VirtualHost \\*:.*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf' 'exec "$@"' > /usr/local/bin/render-start \
    && chmod +x /usr/local/bin/render-start

ENTRYPOINT ["render-start", "docker-php-entrypoint"]
CMD ["apache2-foreground"]
