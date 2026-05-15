FROM dunglas/frankenphp:latest-php8.3

WORKDIR /app

# Extensions PHP requises
RUN install-php-extensions pdo_mysql mongodb

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Dépendances PHP
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Fichiers du projet
COPY . .

EXPOSE 80

CMD ["frankenphp", "run", "--config", "/app/Caddyfile", "--adapter", "caddyfile"]
