#!/bin/bash

# Obtenir le port de Railway (variable d'environnement PORT)
PORT=${PORT:-8000}

echo "🚀 Démarrage du serveur PHP sur le port $PORT..."

# Exécuter PHP server
php -S 0.0.0.0:$PORT
