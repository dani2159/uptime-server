FROM php:8.2-fpm-alpine

# Timezone Asia/Jakarta
ENV TZ=Asia/Jakarta
RUN apk add --no-cache tzdata \
    && cp /usr/share/zoneinfo/Asia/Jakarta /etc/localtime \
    && echo "Asia/Jakarta" > /etc/timezone \
    && apk del tzdata

# System dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    mysql-client \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    icu-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql zip gd intl \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# Install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --optimize-autoloader --no-dev --no-interaction --no-scripts

# Copy application source
COPY . .

# Run post-install scripts after source is copied
RUN composer run-script post-autoload-dump \
    && php artisan storage:link \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Copy Docker config
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create log directories
RUN mkdir -p /var/log/supervisor /var/log/nginx \
    && touch /var/log/nginx/access.log /var/log/nginx/error.log

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
