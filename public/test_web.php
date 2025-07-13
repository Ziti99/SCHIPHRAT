<?php
echo "<h1>Test Web - Connexion MySQL</h1>";

// Test 1: Connexion directe PDO
echo "<h2>Test 1: Connexion PDO directe</h2>";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=clinique_obstetrique", "root", "admin");
    echo "<p style='color: green;'>✅ Connexion PDO réussie avec mot de passe 'admin'</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur PDO avec 'admin': " . $e->getMessage() . "</p>";
}

// Test 2: Connexion sans mot de passe
echo "<h2>Test 2: Connexion sans mot de passe</h2>";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=clinique_obstetrique", "root", "");
    echo "<p style='color: green;'>✅ Connexion PDO réussie sans mot de passe</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur PDO sans mot de passe: " . $e->getMessage() . "</p>";
}

// Test 3: Test de la classe Database
echo "<h2>Test 3: Classe Database</h2>";
try {
    require_once '../config/database.php';
    $db = new Database();
    echo "<p style='color: green;'>✅ Classe Database OK</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur Database: " . $e->getMessage() . "</p>";
}

echo "<h2>Configuration PHP</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Extensions MySQL: " . (extension_loaded('pdo_mysql') ? '✅ PDO MySQL chargé' : '❌ PDO MySQL non chargé') . "</p>";
?> 