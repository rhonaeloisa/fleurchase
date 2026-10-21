FROM php:8.2-apache
RUN groupadd --gid 1000 render-secrets \
    && usermod -a -G render-secrets www-data

RUN docker-php-ext-install mysqli pdo_mysql

COPY . /var/www/html/fleurchase/

RUN mkdir -p /var/www/html/fleurchase/images \
    /var/www/html/fleurchase/uploads/receipts \
    && chown -R www-data:www-data \
       /var/www/html/fleurchase/images \
       /var/www/html/fleurchase/uploads

WORKDIR /var/www/html/fleurchase
