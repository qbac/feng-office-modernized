#!/bin/bash
set -e

echo "=== Update fo23 (szybkie) ==="
git pull
docker compose restart app web
echo "=== Gotowe ==="
