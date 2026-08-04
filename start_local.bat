@echo off
REM Script de démarrage local Windows – PHP + SQLite
echo Clinique Obstetrique – Demarrage local PHP

where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo PHP non trouve. Installez PHP 8.0+
    pause
    exit /b
)

php -v | findstr /C:"PHP"

if not exist vendor\autoload.php (
    echo Installation Composer...
    where composer >nul 2>nul
    if %ERRORLEVEL% EQU 0 (
        composer install --no-interaction
    ) else (
        echo Composer non trouve, tentative sans...
    )
)

if not exist database\clinique.db (
    echo Base SQLite manquante – generation...
    where python >nul 2>nul
    if %ERRORLEVEL% EQU 0 (
        python database\generate.py
    ) else (
        echo Python non trouve, utilisez schema.sql
    )
)

if not exist .env (
    echo Creation .env depuis .env.example
    copy .env.example .env
)

echo Demarrage serveur PHP sur http://localhost:8000
echo Comptes: admin / password
php -S 0.0.0.0:8000 -t .
pause
