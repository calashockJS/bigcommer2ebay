FROM php:8.1-fpm

# Install system dependencies for PHP, Node.js, and Puppeteer
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
    libxshmfence1

# Install required PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring zip exif pcntl bcmath gd

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && node --version \
    && npm --version

WORKDIR /var/www/html

# Copy the entire Laravel project before running composer
COPY . .

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Install Node.js dependencies including Puppeteer
RUN npm install

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 8080
EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]