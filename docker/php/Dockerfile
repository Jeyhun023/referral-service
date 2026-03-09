FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql bcmath gd zip pcntl

COPY --from=composer:2.7.4 /usr/bin/composer /usr/bin/composer

COPY ./docker/php/php.ini /usr/local/etc/php/php.ini
COPY ./docker/php/entrypoint.sh /usr/local/bin
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/ninacare
COPY . .

RUN addgroup --gid 1000 laravel
RUN adduser --ingroup laravel --shell /bin/sh laravel
USER laravel

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
