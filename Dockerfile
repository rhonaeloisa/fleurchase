FROM php:8.2-apache
RUN usermod -a -G 1000 www-data

RUN docker-php-ext-install mysqli pdo_mysql

COPY . /var/www/html/fleurchase/

RUN mkdir -p /var/www/html/fleurchase/images \
    /var/www/html/fleurchase/uploads/receipts \
    && chown -R www-data:www-data \
       /var/www/html/fleurchase/images \
       /var/www/html/fleurchase/uploads

WORKDIR /var/www/html/fleurchase
