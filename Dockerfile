FROM dunglas/frankenphp:php8.5-alpine

RUN install-php-extensions zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .