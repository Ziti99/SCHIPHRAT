FROM php:8.2-cli AS base

# Install system deps in one layer
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    git \
    zip \
    unzip \
    libzip-dev \
    libonig-dev \
    libicu-dev \
    && docker-php-ext-install -j$(nproc) \
        zip \
        pdo \
        pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

# Copy composer files first for better cache
COPY composer.json composer.lock* ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts || \
    composer install --no-dev --optimize-autoloader --no-interaction

# Copy rest
COPY . .

# Create .env if missing from .env.example (safe copy)
RUN if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || echo "APP_ENV=production" > .env; fi

# Permissions & non-root user
RUN useradd -m -u 1000 appuser && chown -R appuser:appuser /app && chmod -R 755 /app

# PHP production config
RUN echo "expose_php=Off\n\
display_errors=Off\n\
log_errors=On\n\
error_log=/tmp/php-error.log\n\
opcache.enable=1\n\
opcache.memory_consumption=128\n\
opcache.max_accelerated_files=20000\n\
opcache.validate_timestamps=0\n" > /usr/local/etc/php/conf.d/production.ini

USER appuser

EXPOSE 8000

# Healthcheck
HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
  CMD curl -f http://localhost:${PORT:-8000}/ || exit 1

# Start server – bind 0.0.0.0 for Railway / preview
CMD php -S 0.0.0.0:${PORT:-8000} -t /app /app/index.php
