FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true \
    && a2enmod mpm_prefork >/dev/null 2>&1 || true \
    && printf '%s\n' '#!/bin/sh' 'set -e' 'PORT="${PORT:-80}"' 'rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf' 'a2enmod mpm_prefork >/dev/null 2>&1 || true' 'echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf' 'a2enconf servername >/dev/null 2>&1 || true' 'echo "Listen ${PORT}" > /etc/apache2/ports.conf' 'cat > /etc/apache2/sites-available/000-default.conf <<EOF' '<VirtualHost *:${PORT}>' '    ServerAdmin webmaster@localhost' '    DocumentRoot /var/www/html' '    DirectoryIndex index.php index.html' '    <Directory /var/www/html>' '        Options -Indexes +FollowSymLinks' '        AllowOverride All' '        Require all granted' '    </Directory>' '    ErrorLog ${APACHE_LOG_DIR}/error.log' '    CustomLog ${APACHE_LOG_DIR}/access.log combined' '</VirtualHost>' 'EOF' 'exec "$@"' > /usr/local/bin/render-start \
    && chmod +x /usr/local/bin/render-start

ENTRYPOINT ["render-start", "docker-php-entrypoint"]
CMD ["apache2-foreground"]
