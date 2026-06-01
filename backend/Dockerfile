FROM php:8.3-apache

# ── System dependencies ──────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg-dev libfreetype6-dev \
        libzip-dev libonig-dev libxml2-dev \
        zip unzip git curl \
        python3 python3-venv chromium chromium-driver \
    && rm -rf /var/lib/apt/lists/*

# ── Node.js 20 (for admin panel / Vite builds) ──────────────────────
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# ── PHP extensions ───────────────────────────────────────────────────
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo pdo_mysql mbstring exif pcntl bcmath gd zip

# ── Composer ─────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# ── Apache configuration ─────────────────────────────────────────────
RUN a2enmod rewrite \
    && sed -i 's|DocumentRoot /var/www/html.*|DocumentRoot /var/www/html/public|g' \
        /etc/apache2/sites-available/000-default.conf \
    && sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' \
        /etc/apache2/apache2.conf

WORKDIR /var/www/html

ENV BOXINGDB_SCRAPER_PYTHON=/opt/boxingdb-scraper-venv/bin/python

# ── Copy application code ────────────────────────────────────────────
COPY . .

# ── BoxingDB scraper Python dependencies ─────────────────────────────
RUN if [ -f tools/boxingdb_scraper/requirements.txt ]; then \
        python3 -m venv /opt/boxingdb-scraper-venv \
        && /opt/boxingdb-scraper-venv/bin/python -m pip install --no-cache-dir -r tools/boxingdb_scraper/requirements.txt; \
    fi

# ── Install PHP dependencies ─────────────────────────────────────────
RUN if [ -f composer.json ]; then \
        composer install --no-interaction --optimize-autoloader; \
    fi

# ── File permissions ─────────────────────────────────────────────────
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# ── Entrypoint ────────────────────────────────────────────────────────
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
