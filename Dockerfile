FROM dunglas/frankenphp:latest-php8.3

WORKDIR /app

# Outils système requis par Composer
RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev \
    && rm -rf /var/lib/apt/lists/*

# Extensions PHP
RUN install-php-extensions pdo_mysql mongodb zip

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Dépendances PHP
COPY composer.json composer.lock ./
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Fichiers du projet
COPY . .

EXPOSE 80

CMD ["frankenphp", "run", "--config", "/app/Caddyfile", "--adapter", "caddyfile"]
