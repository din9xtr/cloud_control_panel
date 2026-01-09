FROM php:8.5-cli-alpine
RUN apk add --no-cache libzip-dev zip \
    && docker-php-ext-install zip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www
COPY . .
CMD ["php", "-S", "0.0.0.0:8001", "-t", "public"]