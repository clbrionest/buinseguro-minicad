
FROM php:8.4-cli-bookworm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libzip-dev \
    libsqlite3-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libicu-dev \
    libonig-dev \
    libxml2-dev \
    libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_sqlite \
        mysqli \
        bcmath \
        gd \
        zip \
        intl \
        mbstring \
        pcntl \
        sockets \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Xdebug para desarrollo local
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY docker/php/xdebug.ini \
    /usr/local/etc/php/conf.d/99-xdebug.ini

WORKDIR /var/www/html

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
