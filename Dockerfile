FROM dunglas/frankenphp:latest-php8.3

WORKDIR /app

# Dépendances système + extensions PHP
RUN apt-get update && apt-get install -y git unzip zip libzip-dev \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions pdo_mysql mongodb zip

COPY php-session.ini /usr/local/etc/php/conf.d/session-veg.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Dépendances PHP
COPY composer.json composer.lock ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Fichiers du projet
COPY . .

# Notre Caddyfile à l'emplacement attendu par l'image FrankenPHP
COPY Caddyfile /etc/caddy/Caddyfile

EXPOSE 80
