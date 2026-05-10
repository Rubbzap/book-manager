FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html

COPY . /var/www/html/

RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \; \
    && sed -i 's/\r$//' /var/www/html/docker/start-apache.sh \
    && chmod +x /var/www/html/docker/start-apache.sh \
    && cp /var/www/html/docker/start-apache.sh /usr/local/bin/start-apache \
    && (a2dismod mpm_event mpm_worker >/dev/null 2>&1 || true) \
    && (a2enmod mpm_prefork >/dev/null 2>&1 || true)

ENTRYPOINT ["start-apache", "docker-php-entrypoint"]
CMD ["apache2-foreground"]
