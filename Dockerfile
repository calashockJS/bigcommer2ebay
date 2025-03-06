FROM php:8.1-fpm

# Install system dependencies for PHP, Node.js, PostgreSQL, and Puppeteer
RUN apt-get update && apt-get install -y \
    libssl-dev \
    unzip \
    git \
    curl \
    gnupg \
    ca-certificates \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libpq-dev \
    postgresql-client \
    chromium \
    libatk-bridge2.0-0 \
    libdrm2 \
    libxkbcommon0 \
    libxcomposite1 \
    libxdamage1 \
    libxfixes3 \
    libxrandr2 \
    libgbm1 \
    libasound2 \
    libatspi2.0-0 \
    libxshmfence1 \
    nginx \
    openssl \
    && docker-php-ext-install pdo pdo_mysql zip \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pgsql pdo_pgsql

# Install Node.js and npm
RUN curl -sL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs

WORKDIR /var/www/html

COPY . .

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN composer install --no-dev --optimize-autoloader

RUN npm install

RUN chown -R www-data:www-data /var/www/html

# Generate self-signed certificate (for development/testing)
RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout /etc/ssl/private/nginx-selfsigned.key -out /etc/ssl/certs/nginx-selfsigned.crt -subj "/CN=localhost"

# Copy Nginx configuration
COPY nginx.conf /etc/nginx/sites-available/default

# Expose ports 80 and 443
EXPOSE 80 443

RUN echo "#!/bin/bash\n\
php artisan config:clear\n\
php artisan cache:clear\n\
php artisan config:cache\n\
php artisan route:cache\n\
php artisan migrate --force\n\
php artisan queue:work --queue=bc2ebay-uqueue --tries=3 --timeout=90 &\n\
nginx -g 'daemon off;' &\n\
php-fpm" > /startup.sh && chmod +x /startup.sh

CMD ["/startup.sh"]