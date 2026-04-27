FROM php:8.3-fpm-alpine

# Définir le répertoire de travail
WORKDIR /var/www

# Installer les dépendances système et Node.js
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite-dev \
    nodejs \
    npm

# Installer pnpm
RUN npm install -g pnpm

# Installer les extensions PHP nécessaires pour Laravel
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    linux-headers \
    && pecl install redis \
    && docker-php-ext-install pdo_sqlite pdo_mysql mbstring exif pcntl bcmath gd \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier les fichiers de l'application
COPY . .

# Installer les dépendances PHP
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --optimize-autoloader

# Installer les dépendances Node.js et compiler les assets
RUN pnpm install
RUN pnpm run build

# Ajuster les permissions pour Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Exposer le port de PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
