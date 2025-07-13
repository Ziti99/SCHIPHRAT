<?php
require_once 'vendor/autoload.php';

use Clinique\Config\Database;

try {
    $db = Database::getInstance();
    
    // Vérifier si les tables existent
    $tables = $db->fetchAll("SHOW TABLES LIKE 'actes_poses'");
    if (empty($tables)) {
        echo "❌ Table actes_poses n'existe pas\n";
        echo "📋 Import du schéma...\n";
        
        // Lire et exécuter le fichier SQL
        $sql = file_get_contents('database/permanence_schema.sql');
        $queries = explode(';', $sql);
        
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                $db->query($query);
                echo "✅ Requête exécutée\n";
            }
        }
        
        echo "✅ Schéma importé avec succès !\n";
    } else {
        echo "✅ Table actes_poses existe\n";
    }
    
    // Vérifier le nombre d'actes
    $count = $db->fetch("SELECT COUNT(*) as count FROM actes_poses");
    echo "📊 Nombre d'actes configurés : " . $count['count'] . "\n";
    
    // Vérifier les tables de permanence
    $permanences = $db->fetchAll("SHOW TABLES LIKE 'permanences'");
    if (empty($permanences)) {
        echo "❌ Table permanences n'existe pas\n";
    } else {
        echo "✅ Table permanences existe\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
}
?> 