# Build version 3.0 - Force rebuild no cache - 2026-02-09
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

# NO ejecutar dump-env para que use variables de Railway en runtime
# RUN composer dump-env prod || true
RUN php bin/console cache:clear --env=prod --no-debug || true
RUN php bin/console cache:warmup --env=prod || true
RUN php bin/console asset-map:compile || true

# Copy entrypoint at the end to ensure it's the latest version
COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh && \
    echo "Entrypoint copied and made executable" && \
    ls -la /docker-entrypoint.sh

EXPOSE 8080

CMD ["/docker-entrypoint.sh"]
