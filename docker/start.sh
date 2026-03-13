#!/usr/bin/env sh
set -e

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  php artisan migrate --force
fi

exec apache2-foreground
