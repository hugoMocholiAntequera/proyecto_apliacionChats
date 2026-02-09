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

# Create a startup script that will run migrations and fixtures
RUN printf '#!/bin/sh\n\
echo "Running database migrations..."\n\
php bin/console doctrine:migrations:migrate --no-interaction --env=prod || echo "Migration failed, continuing..."\n\
echo "Loading fixtures..."\n\
php bin/console doctrine:fixtures:load --no-interaction --env=prod --append || echo "Fixtures failed, continuing..."\n\
echo "Starting PHP server on port 8080"\n\
php -S 0.0.0.0:8080 -t public\n' > /run.sh && chmod +x /run.sh

EXPOSE 8080

CMD ["/run.sh"]
