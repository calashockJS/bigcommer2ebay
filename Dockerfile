FROM php:8.1-fpm

RUN apt-get update && apt-get install -y \
    libssl-dev \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo pdo_mysql

WORKDIR /var/www/html

COPY . .

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer install --no-dev --optimize-autoloader

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
