#!/bin/bash
# Script de démarrage local – PHP + SQLite (base conséquente)
# Usage: ./start_local.sh

set -e

echo "🏥 Clinique Obstétrique – Démarrage local PHP"

# Vérifie PHP
if ! command -v php &> /dev/null; then
    echo "❌ PHP non trouvé. Installez PHP 8.0+ : https://www.php.net/downloads"
    exit 1
fi

echo "✅ PHP $(php -v | head -n1)"

# Vérifie composer
if [ ! -f "vendor/autoload.php" ]; then
    if command -v composer &> /dev/null; then
        echo "📦 Installation dépendances Composer..."
        composer install --no-interaction
    else
        echo "⚠️  vendor/ manquant mais composer non trouvé. Tentative sans..."
        echo "   Installez Composer: https://getcomposer.org"
    fi
else
    echo "✅ Vendor présent"
fi

# Vérifie base SQLite
if [ ! -f "database/clinique.db" ]; then
    echo "📦 Base SQLite manquante – génération..."
    if command -v python3 &> /dev/null; then
        python3 database/generate.py
    else
        echo "❌ python3 non trouvé pour générer la base. Utilisez database/schema.sql + seed si besoin"
    fi
else
    SIZE=$(du -h database/clinique.db | cut -f1)
    echo "✅ Base SQLite présente: database/clinique.db ($SIZE)"
    # Stats rapides
    if command -v sqlite3 &> /dev/null; then
        sqlite3 database/clinique.db "SELECT 'Users:' || COUNT(*) FROM users UNION ALL SELECT 'Patientes:' || COUNT(*) FROM patientes UNION ALL SELECT 'Consultations:' || COUNT(*) FROM consultations;"
    fi
fi

# Vérifie .env
if [ ! -f ".env" ]; then
    echo "📄 Création .env depuis .env.example"
    cp .env.example .env
fi

echo "✅ .env configuré:"
cat .env | grep DB_

echo ""
echo "🚀 Démarrage serveur PHP sur http://localhost:8000"
echo "   Comptes test: admin / password, medecin1 / password, sagefemme1 / password"
echo "   Base: 200 patientes, 600 consultations, 120 accouchements"
echo "   Ctrl+C pour arrêter"
echo ""

# Kill ancien serveur Node sur 8000 si présent (conflit)
if command -v lsof &> /dev/null; then
    PID=$(lsof -ti:8000 || true)
    if [ ! -z "$PID" ]; then
        echo "⚠️  Port 8000 occupé par PID $PID – arrêt..."
        kill $PID || true
        sleep 1
    fi
fi

# Démarre PHP built-in server
php -S 0.0.0.0:8000 -t .
