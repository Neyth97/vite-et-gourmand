#!/bin/sh
php-fpm -y /app/php-fpm.conf
caddy run --config /app/Caddyfile --adapter caddyfile
