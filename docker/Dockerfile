FROM php:8.2-apache

# ── System deps ──
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

# ── Apache config ──
# Enable mod_rewrite (needed by .htaccess router) and SSL
RUN a2enmod rewrite ssl

# Copy our vhost configs
COPY docker/apache/000-default.conf  /etc/apache2/sites-available/000-default.conf
COPY docker/apache/default-ssl.conf  /etc/apache2/sites-available/default-ssl.conf
RUN a2ensite 000-default default-ssl

# ── Self-signed cert for local HTTPS ──
RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/ars-selfsigned.key \
    -out    /etc/ssl/certs/ars-selfsigned.crt \
    -subj "/C=PH/ST=NCR/L=Manila/O=ProcessingSystem/CN=localhost"

# ── Composer ──
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ── App files ──
WORKDIR /var/www/automated-requesting-system

# Copy project (composer install runs at build time; vendor/ is in .dockerignore
# for fast local dev, or baked in for a production image)
COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Uploads directory must be writable by www-data
RUN chown -R www-data:www-data public/uploads \
    && chmod -R 775 public/uploads

EXPOSE 80 443