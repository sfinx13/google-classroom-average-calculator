FROM dunglas/frankenphp:php8.4

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN install-php-extensions \
    pdo_mysql \
    intl \
    zip \
    opcache

WORKDIR /app
