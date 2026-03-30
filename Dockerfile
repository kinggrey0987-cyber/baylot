FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    nginx \
    postgresql-dev \
    libpq \
    nodejs \
    npm \
    bash \
    && docker-php-ext-install pdo_pgsql \
    && rm -rf /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

RUN if [ -f package.json ]; then npm install && npm run build; fi

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 10000

CMD ["/usr/local/bin/start.sh"]