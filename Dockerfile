FROM php:8.2-alpine

# Install required packages
RUN apk add --no-cache \
    curl \
    git \
    zip \
    unzip \
    libzip-dev \
    oniguruma-dev \
    sqlite-dev \
    && docker-php-ext-install \
    zip \
    pdo \
    pdo_mysql \
    pdo_sqlite

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /app

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader 2>/dev/null || composer install

# Create .env file if not exists
RUN if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || echo "APP_ENV=production" > .env; fi

# Expose port
EXPOSE 8000

# Set permissions
RUN chmod -R 755 /app

# Start PHP built-in server
CMD php -S 0.0.0.0:${PORT:-8000}