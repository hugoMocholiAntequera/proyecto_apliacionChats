# Build version 2.0 - Force rebuild
FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libicu-dev curl \
    && docker-php-ext-install pdo_mysql zip intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs

WORKDIR /app

COPY composer.json composer.lock package.json ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
RUN npm install

COPY . .

ENV APP_ENV=prod

RUN composer dump-env prod || true
RUN php bin/console cache:clear --env=prod --no-debug || true
RUN php bin/console cache:warmup --env=prod || true
RUN php bin/console asset-map:compile || true

EXPOSE 8080

# Crear wrapper script directamente en el contenedor
RUN printf '#!/bin/sh\nphp -S 0.0.0.0:8080 -t public\n' > /run.sh && chmod +x /run.sh

CMD ["/run.sh"]
