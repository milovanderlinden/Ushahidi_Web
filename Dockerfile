FROM php:7.0-apache

ENV DB_HOST 127.0.0.1
ENV DB_NAME test
ENV DB_USER user
ENV DB_PASS password
ENV ROOT_PASS danger
ENV DEBUG_MODE false

RUN apt-get update && apt-get install -y \
      libfreetype6-dev \
      libjpeg62-turbo-dev \
      libpng-dev \
      libmcrypt-dev \
      libc-client2007e-dev \
      libkrb5-dev \
      libcurl4-openssl-dev \
      unzip \
      rsync \
      netcat-openbsd \
      supervisor \
      cron \
      git && \
    docker-php-ext-install curl json mcrypt mysqli pdo pdo_mysql && \
    docker-php-ext-configure imap --with-kerberos --with-imap-ssl && \
    docker-php-ext-install imap && \
    docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ && \
    docker-php-ext-install gd && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN a2enmod rewrite headers

COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html

COPY vhost.conf /etc/apache2/sites-enabled/000-default.conf
