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

# Configurer Apache pour utiliser le dossier public
RUN echo '<VirtualHost *:80>' > /etc/apache2/sites-available/000-default.conf && \
    echo '    DocumentRoot /var/www/html/public' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    <Directory /var/www/html/public>' >> /etc/apache2/sites-available/000-default.conf && \
    echo '        AllowOverride All' >> /etc/apache2/sites-available/000-default.conf && \
    echo '        Require all granted' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    </Directory>' >> /etc/apache2/sites-available/000-default.conf && \
    echo '</VirtualHost>' >> /etc/apache2/sites-available/000-default.conf

# Configurer les permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 755 /var/www/html/public \
    && mkdir -p /var/log/apache2 \
    && chown -R www-data:www-data /var/log/apache2 \
    && chmod -R 755 /var/log/apache2

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