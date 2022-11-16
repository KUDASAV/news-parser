FROM php:7.4-apache
 
RUN a2enmod rewrite
 
RUN apt-get update \
  && apt-get install -y libzip-dev git wget --no-install-recommends \
  && apt-get install -y cron \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
 
RUN docker-php-ext-install pdo mysqli pdo_mysql zip;
 
RUN wget https://getcomposer.org/download/2.0.9/composer.phar \
    && mv composer.phar /usr/bin/composer && chmod +x /usr/bin/composer
 
COPY apache/apache.conf /etc/apache2/sites-enabled/000-default.conf
COPY ./php.ini /usr/local/etc/php/php.ini

COPY php.ini /usr/local/etc/php
RUN docker-php-ext-install sockets
RUN docker-php-ext-install bcmath

RUN docker-php-ext-configure pcntl --enable-pcntl \
  && docker-php-ext-install \
    pcntl

WORKDIR /var/www
COPY . .

RUN chmod +x entrypoint.sh
RUN chmod +x bin/console
 
CMD ["apache2-foreground"]
ENTRYPOINT ["sh", "./entrypoint.sh"]