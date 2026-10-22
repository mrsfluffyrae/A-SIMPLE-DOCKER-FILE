FROM php:8.4-apache

# pdo_mysql is what the PHP files use; mysqli is optional
RUN docker-php-ext-install pdo pdo_mysql mysqli

# msmtp lets PHP's mail() send through the Docker 'mail' container
RUN apt-get update \
    && apt-get install -y --no-install-recommends msmtp msmtp-mta \
    && rm -rf /var/lib/apt/lists/*

COPY msmtprc /etc/msmtprc
COPY php-mail.ini /usr/local/etc/php/conf.d/mail.ini

# Copy the app into Apache's web root, owned by the web server user
COPY --chown=www-data:www-data . /var/www/html/

EXPOSE 80
