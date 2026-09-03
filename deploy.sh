#!/bin/bash
set -e

echo "=== Deploy fo23 ==="

echo "[1/4] git pull..."
git pull

echo "[2/4] Rebuild i restart kontenerow..."
docker compose up -d --build app web

echo "[3/4] Czyszczenie cache (autoloader odbuduje sie automatycznie)..."
docker compose exec app sh -c 'find cache -type f ! -name ".htaccess" ! -name "dummy.txt" -delete'

echo "[4/4] Uprawnienia dla www-data (uid 33) na upload/cache/tmp..."
docker compose exec app chown -R www-data:www-data upload cache tmp

echo "=== Deploy zakonczony ==="
