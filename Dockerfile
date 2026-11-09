FROM php:8.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache bash nginx supervisor curl

# Install PHP extensions using official installer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql pdo_mysql intl zip opcache bcmath

# Copy composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure PHP, Nginx, Supervisor, and Start script
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
COPY conf/php/docker-vars.ini /usr/local/etc/php/conf.d/docker-vars.ini
COPY conf/php/www-overrides.conf /usr/local/etc/php-fpm.d/zz-overrides.conf
COPY conf/nginx/nginx-site.conf /etc/nginx/http.d/default.conf
COPY conf/supervisor/supervisord.conf /etc/supervisord.conf
COPY conf/start.sh /start.sh
RUN sed -i 's/\r$//' /start.sh && chmod +x /start.sh

WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP dependencies
ENV COMPOSER_ALLOW_SUPERUSER 1
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction --ignore-platform-reqs

# Set permissions for Laravel storage and cache
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Default production environment variables
ENV APP_ENV production
ENV APP_DEBUG false
ENV LOG_CHANNEL stderr

EXPOSE 80

CMD ["/start.sh"]
