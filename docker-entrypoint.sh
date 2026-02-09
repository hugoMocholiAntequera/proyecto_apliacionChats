#!/bin/sh
set -e

echo "================================"
echo "=== Starting Application ==="
echo "================================"

echo ""
echo "--- Environment Check ---"
echo "APP_ENV: ${APP_ENV}"
echo "DATABASE_URL: ${DATABASE_URL:0:40}..." 
echo "PHP Version: $(php -v | head -n 1)"

echo ""
echo "--- Testing Database Connection ---"
php bin/console dbal:run-sql "SELECT 1 as test" || {
    echo "ERROR: Cannot connect to database"
    echo "Trying to get more details..."
    php bin/console doctrine:database:create --if-not-exists || true
}

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
