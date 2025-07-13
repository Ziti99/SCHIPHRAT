<?php
echo "🧪 Test des pages du module permanence\n";
echo "=====================================\n\n";

// Test 1: Vérifier que les fichiers existent
$files = [
    'public/permanence.php',
    'public/admin/permanences.php', 
    'public/admin/actes.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file existe\n";
    } else {
        echo "❌ $file n'existe pas\n";
    }
}

echo "\n";

// Test 2: Vérifier la base de données
require_once 'vendor/autoload.php';
use Clinique\Config\Database;

try {
    $db = Database::getInstance();
    
    // Vérifier les actes
    $actes = $db->fetchAll("SELECT * FROM actes_poses WHERE is_active = 1");
    echo "📊 Actes actifs : " . count($actes) . "\n";
    
    // Vérifier les permanences
    $permanences = $db->fetchAll("SELECT COUNT(*) as count FROM permanences");
    echo "📊 Permanences totales : " . $permanences[0]['count'] . "\n";
    
    echo "✅ Base de données OK\n";
    
} catch (Exception $e) {
    echo "❌ Erreur base de données : " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Vérifier les liens dans le dashboard
$dashboard_content = file_get_contents('public/dashboard.php');
if (strpos($dashboard_content, 'permanence.php') !== false) {
    echo "✅ Lien permanence dans le dashboard\n";
} else {
    echo "❌ Lien permanence manquant dans le dashboard\n";
}

if (strpos($dashboard_content, 'admin/permanences.php') !== false) {
    echo "✅ Lien admin permanences dans le dashboard\n";
} else {
    echo "❌ Lien admin permanences manquant dans le dashboard\n";
}

echo "\n🎯 URLs à tester :\n";
echo "- http://localhost:8000/permanence.php (secrétaire)\n";
echo "- http://localhost:8000/admin/permanences.php (admin)\n";
echo "- http://localhost:8000/admin/actes.php (admin)\n";

echo "\n📋 Instructions :\n";
echo "1. Connectez-vous en tant que secrétaire pour voir le lien permanence\n";
echo "2. Connectez-vous en tant qu'admin pour voir les liens admin\n";
echo "3. Testez la saisie d'une permanence\n";
echo "4. Testez la validation par l'admin\n";
?> 