<?php
echo "🧪 Test des modules de la clinique obstétrique\n";
echo "=============================================\n\n";

// Test de la classe Database
echo "1. Test de la classe Database...\n";
try {
    require_once 'config/database.php';
    $db = new Database();
    echo "✅ Classe Database OK\n";
    
    // Test fetchOne
    $result = $db->fetchOne("SELECT COUNT(*) as total FROM users");
    echo "✅ Méthode fetchOne() OK\n";
    
} catch (Exception $e) {
    echo "❌ Erreur Database: " . $e->getMessage() . "\n";
}

// Test des tables
echo "\n2. Test des tables...\n";
$tables = ['users', 'patientes', 'grossesses', 'consultations_prenatales', 'accouchements', 'suivi_postnatal', 'registres'];

foreach ($tables as $table) {
    try {
        $result = $db->fetchOne("SELECT COUNT(*) as total FROM $table");
        echo "✅ Table '$table' OK\n";
    } catch (Exception $e) {
        echo "❌ Table '$table' manquante: " . $e->getMessage() . "\n";
    }
}

// Test des modules
echo "\n3. Test des modules...\n";
$modules = [
    'modules/patientes/dashboard.php',
    'modules/consultations/dashboard.php', 
    'modules/accouchements/dashboard.php',
    'modules/registres/dashboard.php',
    'modules/statistiques/dashboard.php',
    'modules/suivi-postnatal/dashboard.php'
];

foreach ($modules as $module) {
    if (file_exists($module)) {
        echo "✅ Module '$module' existe\n";
    } else {
        echo "❌ Module '$module' manquant\n";
    }
}

echo "\n🎉 Test terminé !\n";
echo "Vous pouvez maintenant tester l'application sur http://localhost:8000\n";
?> 