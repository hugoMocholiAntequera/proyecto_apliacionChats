#!/bin/sh
set -e

echo "================================"
echo "=== Starting Application ==="
echo "================================"

echo ""
echo "--- Environment Check ---"
echo "APP_ENV: ${APP_ENV}"
echo "DATABASE_URL: ${DATABASE_URL:0:30}..." 

echo ""
echo "--- Running Migrations ---"
php bin/console doctrine:migrations:migrate --no-interaction || {
    echo "ERROR: Migration failed"
    echo "Continuing anyway..."
}

echo ""
echo "--- Loading Fixtures ---"
php bin/console doctrine:fixtures:load --no-interaction --append || {
    echo "ERROR: Fixtures failed"
    echo "Continuing anyway..."
}

echo ""
echo "--- Starting PHP Server ---"
echo "Server starting on 0.0.0.0:8080"
exec php -S 0.0.0.0:8080 -t public
