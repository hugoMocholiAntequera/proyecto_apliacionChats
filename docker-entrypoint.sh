#!/bin/bash
set -e

echo "================================"
echo "=== Starting Application ==="
echo "================================"

# Eliminar archivo de env cached si existe
if [ -f ".env.local.php" ]; then
    echo "Removing cached .env.local.php to use Railway env vars"
    rm .env.local.php
fi

echo ""
echo "--- Environment Check ---"
echo "APP_ENV: ${APP_ENV}"
# Verificar si DATABASE_URL está definida
if [ -z "${DATABASE_URL}" ]; then
    echo "❌ DATABASE_URL: NOT SET (variable is empty or undefined)"
    echo "Available env variables:"
    env | grep -E "(DATABASE|MYSQL)" || echo "No DATABASE or MYSQL variables found"
else
    echo "✅ DATABASE_URL: ${DATABASE_URL:0:40}..." 
fi
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
