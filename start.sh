#!/bin/sh
# Script de inicio para Railway

# Usar el puerto de Railway o 8080 por defecto
PORT=${PORT:-8080}

echo "Starting PHP server on port $PORT"
php -S 0.0.0.0:$PORT -t public
