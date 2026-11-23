#!/usr/bin/env bash
# Membuat project Laravel baru lalu menimpa dengan source dashboard ini.
# Usage:  bash setup.sh [nama-folder]      (butuh PHP >= 8.2, Composer)
set -euo pipefail
SRC="$(cd "$(dirname "$0")" && pwd)"
TARGET="${1:-trusmi-kpi}"

composer create-project laravel/laravel "$TARGET"
cd "$TARGET"

cp -R "$SRC/app/."      app/
cp -R "$SRC/routes/."   routes/
cp -R "$SRC/resources/." resources/
cp -R "$SRC/public/."   public/
cp -R "$SRC/database/." database/
cp -R "$SRC/tests/."    tests/
cp -R "$SRC/docs"       docs
cp    "$SRC/README.md"  README.md

[ -f .env ] || cp .env.example .env
php artisan key:generate --ansi || true
php artisan migrate:fresh --seed --force
php artisan test || true

echo
echo "Selesai. Jalankan:  cd $TARGET && php artisan serve   -> http://127.0.0.1:8000"
