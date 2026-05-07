FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends msmtp msmtp-mta ca-certificates \
    && rm -rf /var/lib/apt/lists/*

RUN { \
      echo "defaults"; \
      echo "auth off"; \
      echo "tls off"; \
      echo "logfile /tmp/msmtp.log"; \
      echo "account default"; \
      echo "host mailpit"; \
      echo "port 1025"; \
      echo "from no-reply@book-manager.local"; \
    } > /etc/msmtprc \
    && chmod 644 /etc/msmtprc \
    && echo 'sendmail_path = "/usr/bin/msmtp -t"' > /usr/local/etc/php/conf.d/mail.ini

RUN docker-php-ext-install pdo pdo_mysql
