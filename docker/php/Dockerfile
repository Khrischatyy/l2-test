FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libonig-dev

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    zip \
    intl \
    opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer.json only first
COPY composer.json ./

# Install dependencies
RUN composer install --no-scripts --no-autoloader

# Copy the rest of the application
COPY . .

# Set up environment file
COPY .env.dist .env.dist
RUN if [ ! -f .env ]; then cp .env.dist .env; fi

# Generate optimized autoloader and run scripts
RUN composer dump-autoload --optimize && \
    composer run-script post-install-cmd

# Set permissions
RUN chown -R www-data:www-data /var/www/html/var 