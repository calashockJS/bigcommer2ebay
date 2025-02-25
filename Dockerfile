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
    && docker-php-ext-install pdo pdo_mysql zip

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && node --version \
    && npm --version

WORKDIR /var/www/html

# Copy composer files first to leverage Docker cache
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-dev --optimize-autoloader

# Copy package.json for Node.js dependencies
COPY package.json package-lock.json* ./

# Install Node.js dependencies including Puppeteer
RUN npm install

# Create node_scripts directory
RUN mkdir -p node_scripts

# Copy the Puppeteer script
COPY node_scripts/ebay_auth.js node_scripts/

# Tell Puppeteer to skip downloading Chrome (we installed Chromium via apt)
ENV PUPPETEER_SKIP_CHROMIUM_DOWNLOAD=true
ENV PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium

# Copy the rest of the application
COPY . .

# Permissions fix
RUN chown -R www-data:www-data /var/www/html

# Expose port 8080
EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]