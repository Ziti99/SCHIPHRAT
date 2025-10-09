<?php
/**
 * Fichier d'initialisation global de l'application
 * À inclure en haut de chaque page PHP
 */

// Démarrer la session si pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Charger les helpers
require_once __DIR__ . '/helpers.php';

// Configuration de l'affichage des erreurs (à désactiver en production)
if (getenv('APP_ENV') === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Timezone par défaut
date_default_timezone_set('Africa/Libreville');

// Charset par défaut
ini_set('default_charset', 'UTF-8');

// Charger la base de données si nécessaire
if (file_exists(__DIR__ . '/../config/database.php')) {
    require_once __DIR__ . '/../config/database.php';
}

