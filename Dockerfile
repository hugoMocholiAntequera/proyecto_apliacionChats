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

# Copiar archivos de dependencias primero
COPY composer.json composer.lock ./
COPY package.json ./

# Instalar dependencias
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
RUN npm install

# Copiar aplicación
COPY . .

# Variables de entorno
ENV APP_ENV=prod
ENV PORT=8080

# Build de la aplicación
RUN composer dump-env prod || true
RUN php bin/console cache:clear --env=prod --no-debug || true
RUN php bin/console cache:warmup --env=prod || true
RUN php bin/console asset-map:compile || true

# Exponer puerto
EXPOSE 8080

# Usar shell form para que expanda variables
CMD /bin/sh -c "php -S 0.0.0.0:${PORT} -t public"
