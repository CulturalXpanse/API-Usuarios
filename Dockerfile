FROM php:8.0

RUN mkdir /app

WORKDIR /app

EXPOSE 80

COPY . /app

RUN composer install

CMD ["apache2-foreground"]