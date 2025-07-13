<?php
echo "<h1>Test de l'environnement PHP</h1>";
echo "<p>PHP version: " . phpversion() . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script Path: " . __FILE__ . "</p>";
echo "<p>Current Directory: " . getcwd() . "</p>";

// Test d'inclusion de vendor/autoload.php
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    echo "<p style='color: green;'>✅ vendor/autoload.php trouvé</p>";
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
        echo "<p style='color: green;'>✅ Autoloader chargé avec succès</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erreur lors du chargement de l'autoloader: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ vendor/autoload.php non trouvé</p>";
}

// Test des permissions
echo "<h2>Test des permissions</h2>";
$testDirs = [
    '/var/www/html',
    '/var/www/html/public',
    '/var/www/html/vendor'
];

foreach ($testDirs as $dir) {
    if (is_readable($dir)) {
        echo "<p style='color: green;'>✅ $dir est lisible</p>";
    } else {
        echo "<p style='color: red;'>❌ $dir n'est pas lisible</p>";
    }
}

// Test de la classe Auth
if (class_exists('Clinique\Auth\Auth')) {
    echo "<p style='color: green;'>✅ Classe Auth trouvée</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Classe Auth non trouvée (normal si pas d'autoloader)</p>";
}

echo "<h2>Variables d'environnement</h2>";
echo "<p>PORT: " . ($_ENV['PORT'] ?? 'Non défini') . "</p>";
echo "<p>PWD: " . ($_ENV['PWD'] ?? 'Non défini') . "</p>";
?> 