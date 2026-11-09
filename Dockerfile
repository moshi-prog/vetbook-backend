FROM php:8.4-fpm-alpine

# Install system dependencies and required PHP extensions for Laravel + PostgreSQL
RUN apk add --no-cache bash nginx supervisor libpq libzip icu-libs curl \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS postgresql-dev libzip-dev icu-dev \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql intl zip opcache bcmath \
    && apk del .build-deps

# Copy composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure PHP, Nginx, Supervisor, and Start script
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
COPY conf/php/docker-vars.ini /usr/local/etc/php/conf.d/docker-vars.ini
COPY conf/php/www-overrides.conf /usr/local/etc/php-fpm.d/zz-overrides.conf
COPY conf/nginx/nginx-site.conf /etc/nginx/http.d/default.conf
COPY conf/supervisor/supervisord.conf /etc/supervisord.conf
COPY conf/start.sh /start.sh
RUN chmod +x /start.sh

WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP dependencies
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=php+

# Set permissions for Laravel storage and cache
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Default production environment variables
ENV APP_ENV production
ENV APP_DEBUG false
ENV LOG_CHANNEL stderr

EXPOSE 80

CMD ["/start.sh"]
