FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    postgresql-dev \
    icu-dev \
    && docker-php-ext-install \
    pdo_pgsql \
    intl \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first (for caching)
COPY composer.json composer.lock* ./

# Install dependencies (if composer files exist)
RUN if [ -f composer.json ]; then composer install --no-dev --no-scripts --no-autoloader; fi

# Copy source code
COPY . .

# Generate autoloader (if vendor exists)
RUN if [ -d vendor ]; then composer dump-autoload --optimize; fi

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
