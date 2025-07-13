# Utiliser l'image PHP officielle avec Apache
FROM php:8.1-apache

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    default-mysql-client \
    pkg-config \
    libssl-dev \
    && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mysqli zip

# Activer les modules Apache nécessaires
RUN a2enmod rewrite headers

# Configurer PHP pour afficher les erreurs
RUN echo "display_errors = On" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier les fichiers de l'application
COPY . /var/www/html/

# Copier le contenu de public directement dans /var/www/html
RUN cp -r /var/www/html/public/* /var/www/html/

# Configurer les permissions de manière plus permissive pour le développement
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && mkdir -p /var/log/apache2 \
    && chown -R www-data:www-data /var/log/apache2 \
    && chmod -R 755 /var/log/apache2

# Installer Composer et les dépendances si vendor n'existe pas
RUN if [ ! -d "/var/www/html/vendor" ]; then \
        curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
        cd /var/www/html && composer install --no-dev --optimize-autoloader; \
    fi

# Exposer le port (Railway définira le port)
EXPOSE $PORT

# Créer un script de démarrage simple
RUN echo '#!/bin/bash' > /start.sh && \
    echo 'echo "🚀 Démarrage du conteneur..."' >> /start.sh && \
    echo 'echo "🌐 Démarrage dApache sur le port $PORT..."' >> /start.sh && \
    echo 'sed -i "s/Listen 80/Listen $PORT/g" /etc/apache2/ports.conf' >> /start.sh && \
    echo 'apache2-foreground' >> /start.sh && \
    chmod +x /start.sh

# Démarrer avec le script personnalisé
CMD ["/start.sh"] 