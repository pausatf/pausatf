FROM wordpress:php8.3-fpm-alpine

# Install additional PHP extensions
RUN apk add --no-cache \
    ${PHPIZE_DEPS} \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev

# Configure and install GD with support for multiple image formats
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp && \
    docker-php-ext-install -j$(nproc) \
    gd \
    mysqli \
    opcache \
    zip \
    intl \
    exif \
    bcmath

# Install and enable Redis extension
RUN pecl install redis && \
    docker-php-ext-enable redis

# Configure PHP
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=4000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

RUN { \
    echo 'upload_max_filesize=100M'; \
    echo 'post_max_size=100M'; \
    echo 'max_execution_time=300'; \
    echo 'max_input_time=300'; \
    echo 'memory_limit=256M'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

# Security settings
RUN { \
    echo 'expose_php=Off'; \
    echo 'display_errors=Off'; \
    echo 'log_errors=On'; \
    echo 'error_log=/var/log/php_errors.log'; \
    } > /usr/local/etc/php/conf.d/security.ini

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

# Healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD php-fpm-healthcheck || exit 1

EXPOSE 9000

CMD ["php-fpm"]
