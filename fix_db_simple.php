<?php
// Script ultra-simple pour corriger la base de données
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== CORRECTION BASE DE DONNÉES ===\n\n";

$host = 'metro.proxy.rlwy.net';
$port = '29698';
$dbname = 'railway';
$username = 'root';
$password = 'UJxUfmCzEGIdbYPVwFXKUbAQoFzmByrI';

try {
    echo "Connexion à la base de données...\n";
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connecté!\n\n";
    
    // Vérifier si la colonne password existe
    echo "Vérification de la colonne password...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'password'");
    
    if ($stmt->rowCount() == 0) {
        echo "→ Colonne password manquante. Ajout...\n";
        $pdo->exec("ALTER TABLE users ADD COLUMN password VARCHAR(255) NOT NULL AFTER email");
        echo "✓ Colonne ajoutée!\n\n";
    } else {
        echo "✓ Colonne existe déjà\n\n";
    }
    
    // Mettre à jour les mots de passe
    echo "Mise à jour des mots de passe...\n";
    $hash = '$2y$10$lTQPxN3oNtlsC6amZiLsneY9RTknKoDl6Tj7D1FtoYPkZu8M/Yczy'; // 'password'
    $pdo->exec("UPDATE users SET password = '$hash'");
    echo "✓ Mots de passe mis à jour!\n\n";
    
    // Afficher les utilisateurs
    echo "=== UTILISATEURS ===\n";
    $stmt = $pdo->query("SELECT id, username, email, role, nom, prenom FROM users");
    foreach ($stmt->fetchAll() as $user) {
        echo "ID: {$user['id']} | {$user['username']} | {$user['role']} | {$user['prenom']} {$user['nom']}\n";
    }
    
    echo "\n=== SUCCÈS ===\n";
    echo "Connexion: admin / password\n";
    
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
}

