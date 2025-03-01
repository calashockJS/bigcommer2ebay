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
    # PostgreSQL dependencies
    libpq-dev \
    postgresql-client \
    # Puppeteer dependencies
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
    # End Puppeteer dependencies
    && docker-php-ext-install pdo pdo_mysql zip \
    # Install and configure PostgreSQL extension
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pgsql pdo_pgsql

# Tell Puppeteer to skip downloading Chrome (we installed Chromium via apt)
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && node --version \
    && npm --version

WORKDIR /var/www/html

# Copy the entire Laravel project before running composer
COPY . .

# Install PHP dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Run Composer install after ensuring all files are present
RUN composer install --no-dev --optimize-autoloader

# Install Node.js dependencies including Puppeteer
RUN npm install

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 8080
EXPOSE 8080

#CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
CMD php artisan config:clear && \
    php artisan cache:clear && \
    php artisan config:cache && \
    php artisan route:cache && \
    php artisan migrate --force && \
    php -S 0.0.0.0:80 -t public