#!/usr/bin/env sh
set -eu
cd "$(dirname "$0")/.."
if [ ! -f .env ]; then
  KEY=$(openssl rand -base64 32 | tr -d '\n')
  PASS=$(openssl rand -hex 24)
  sed -e "s|^APP_KEY=$|APP_KEY=base64:${KEY}|" -e "s|^DB_PASSWORD=$|DB_PASSWORD=${PASS}|" .env.example > .env
  chmod 600 .env
  echo 'Created .env with random development credentials.'
fi
if grep -q '^APP_KEY=$\|^DB_PASSWORD=$' .env; then
  echo 'APP_KEY or DB_PASSWORD is empty.' >&2
  exit 1
fi
docker compose up -d --build
docker compose exec -T app php artisan migrate --force
echo 'API http://localhost:8080 | Swagger http://localhost:8080/docs/'
