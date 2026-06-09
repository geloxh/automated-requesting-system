FROM php:8.2-apache

# ── System deps ───────────────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev \
        libjpeg-dev \
        libzip-dev \
        zip \
        unzip \
        git \
        curl \
    && docker-php-ext-install pdo pdo_mysql gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# ── Apache config ─────────────────────────────────────────────────────────────
RUN a2enmod rewrite ssl

COPY docker/apache/000-default.conf  /etc/apache2/sites-available/000-default.conf
COPY docker/apache/default-ssl.conf  /etc/apache2/sites-available/default-ssl.conf
RUN a2ensite 000-default default-ssl

# ── Self-signed cert for local HTTPS ─────────────────────────────────────────
RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/ars-selfsigned.key \
    -out    /etc/ssl/certs/ars-selfsigned.crt \
    -subj "/C=PH/ST=NCR/L=Manila/O=AutomatedRequestingSystem/CN=localhost"

# ── Composer ──────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ── App files ─────────────────────────────────────────────────────────────────
WORKDIR /var/www/automated-requesting-system

COPY . .

# Fix: Linux is case-sensitive but Windows is not, so all app/ subfolders were
# committed with lowercase names. Every namespace and PSR-4 autoloader reference
# uses capital letters (App\Controllers, App\Middleware, etc).
# Rename all four affected folders once at build time.
RUN for dir in controllers Controllers middleware Middleware helpers Helpers model Model services Services; do :; done \
    && { [ -d app/helpers ] && [ ! -d app/Helpers ] && mv app/helpers app/Helpers; true; } \
    && { [ -d app/controllers ] && [ ! -d app/Controllers ] && mv app/controllers app/Controllers; true; } \
    && { [ -d app/middleware ] && [ ! -d app/Middleware ] && mv app/middleware  app/Middleware;  true; } \
    && { [ -d app/model ] && [ ! -d app/Model ] && mv app/model app/Model; true; } \
    && { [ -d app/services ] && [ ! -d app/Services ] && mv app/services app/Services; true; }

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Uploads directory must be writable by www-data
RUN chown -R www-data:www-data public/uploads \
    && chmod -R 775 public/uploads

EXPOSE 80 443