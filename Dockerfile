FROM php:8.3-fpm

# Définir le répertoire de travail
WORKDIR /var/www

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev

# Installer Node.js (v20)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Nettoyer le cache apt
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP nécessaires pour Laravel
RUN apt-get update && apt-get install -y libhiredis-dev \
    && pecl install redis \
    && docker-php-ext-install pdo_sqlite pdo_mysql mbstring exif pcntl bcmath gd \
    && docker-php-ext-enable redis

# Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copier les fichiers de l'application
COPY . .

# Installer les dépendances PHP pour la production
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --optimize-autoloader

# Installer les dépendances Node.js et compiler les assets (Tailwind v4, Vue 3)
RUN npm install --legacy-peer-deps
RUN npm run build

# Ajuster les permissions pour Laravel
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Exposer le port de PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
