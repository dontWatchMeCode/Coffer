FROM dunglas/frankenphp:1-php8.5 AS assets

WORKDIR /app

ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
    DB_CONNECTION=sqlite

RUN apt-get update \
    && apt-get install -y --no-install-recommends nodejs npm \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock package.json package-lock.json ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts \
    && npm ci

COPY app ./app
COPY artisan ./artisan
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY routes ./routes
COPY resources ./resources
COPY vite.config.* ./
COPY tsconfig.json components.json ./
RUN mkdir -p bootstrap/cache storage/framework/views storage/framework/cache storage/framework/sessions \
    && touch database/database.sqlite \
    && php artisan wayfinder:generate --with-form \
    && npm run build

FROM dunglas/frankenphp:1-php8.5 AS app

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends postgresql-client \
    && rm -rf /var/lib/apt/lists/*

RUN install-php-extensions \
    pdo_pgsql \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
COPY --from=assets /app/vendor ./vendor

COPY . .
COPY --from=assets /app/public/build ./public/build
COPY Caddyfile /etc/caddy/Caddyfile

RUN composer dump-autoload --optimize \
    && php artisan package:discover --ansi \
    && mkdir -p storage/app/public \
    && php artisan storage:link \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
