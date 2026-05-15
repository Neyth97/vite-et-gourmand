#!/bin/sh
php-fpm --nodaemonize &
caddy run --config /app/Caddyfile --adapter caddyfile
