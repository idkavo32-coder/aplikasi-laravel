FROM node:22-alpine AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY vite.config.js ./
COPY resources ./resources

RUN npm run build


FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libonig-dev \
    libpq-dev \
    && docker-php-ext-install \
    pdo \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    bcmath \
    intl \
    zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

COPY --from=frontend /app/public/build ./public/build

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN mkdir -p database \
    && touch database/database.sqlite

RUN chown -R www-data:www-data storage bootstrap/cache database

RUN printf '%s\n' \
'<VirtualHost *:10000>' \
'    DocumentRoot /var/www/html/public' \
'    <Directory /var/www/html/public>' \
'        AllowOverride All' \
'        Require all granted' \
'    </Directory>' \
'</VirtualHost>' \
> /etc/apache2/sites-available/000-default.conf

RUN sed -i 's/^Listen 80$/Listen 10000/' /etc/apache2/ports.conf

EXPOSE 10000

CMD ["sh", "-c", "php artisan migrate --force && apache2-foreground"]