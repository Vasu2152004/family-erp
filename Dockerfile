# Builder stage - lightweight Composer only
FROM composer:2 AS builder

WORKDIR /app

COPY composer.json composer.lock ./
RUN echo "==> Installing Composer dependencies..." && \
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev --no-scripts --verbose && \
    echo "==> Composer dependencies installed successfully"

# Production stage
FROM php:8.3-apache AS production

# Install runtime libraries and PHP extensions
RUN echo "==> Updating package lists..." && \
    apt-get update && \
    echo "==> Installing system dependencies..." && \
    apt-get install -y --no-install-recommends \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev && \
    echo "==> Configuring GD extension..." && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    echo "==> Installing PHP extensions..." && \
    docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip && \
    echo "==> Cleaning up package cache..." && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
    echo "==> System dependencies and PHP extensions installed successfully"

# Configure Apache
RUN echo "==> Configuring Apache..." && \
    a2enmod rewrite && \
    sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf && \
    echo "==> Apache configured successfully"

# Configure PHP
RUN echo "==> Configuring PHP settings..." && \
    echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/custom.ini && \
    echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/custom.ini && \
    echo "upload_max_filesize = 20M" >> /usr/local/etc/php/conf.d/custom.ini && \
    echo "post_max_size = 20M" >> /usr/local/etc/php/conf.d/custom.ini && \
    echo "==> PHP settings configured successfully"

# Set Apache document root
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN echo "==> Setting Apache document root..." && \
    sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf && \
    echo "==> Apache document root configured successfully"

WORKDIR /var/www/html

# Copy application code
COPY --chown=www-data:www-data . /var/www/html

# Copy vendor from builder
COPY --from=builder --chown=www-data:www-data /app/vendor /var/www/html/vendor

# Set Laravel-safe permissions (only storage and bootstrap/cache need write access)
RUN echo "==> Setting file permissions..." && \
    chown -R www-data:www-data /var/www/html && \
    chmod -R 775 storage bootstrap/cache && \
    echo "==> Permissions set successfully"

EXPOSE 80

CMD ["apache2-foreground"]
