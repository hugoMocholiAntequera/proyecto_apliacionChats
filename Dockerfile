FROM php:8.2-cli

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    curl \
    && docker-php-ext-install pdo_mysql zip intl

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Node.js y npm
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs

WORKDIR /app

# Copiar archivos de dependencias primero (para aprovechar cache de Docker)
COPY composer.json composer.lock ./
COPY package.json ./

# Instalar dependencias de PHP (sin scripts)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Instalar dependencias de Node
RUN npm install

# Copiar el resto de la aplicación
COPY . .

# Hacer el script ejecutable
RUN chmod +x start.sh

# Variables de entorno por defecto
ENV APP_ENV=prod

# Generar archivos de entorno optimizados
RUN composer dump-env prod || true

# Limpiar y calentar el caché de Symfony en modo producción
RUN php bin/console cache:clear --env=prod --no-debug || true
RUN php bin/console cache:warmup --env=prod || true

# Compilar assets
RUN php bin/console asset-map:compile || true

# Exponer puerto
EXPOSE 8080

# Comando de inicio
CMD ["./start.sh"]
