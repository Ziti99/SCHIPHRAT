<?php
require_once 'config/database.php';

try {
    $db = new Database();
    
    // Lire le fichier SQL
    $sql = file_get_contents('database/permanence_schema.sql');
    
    // Exécuter les requêtes SQL yo
    $queries = explode(';', $sql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            $db->query($query);
            echo "Requête exécutée avec succès.\n";
        }
    }
    
    echo "\n✅ Schéma de permanence importé avec succès !\n";
    echo "📋 Tables créées :\n";
    echo "   - actes_poses (actes configurables)\n";
    echo "   - permanences (données de permanence)\n";
    echo "\n🎯 Fonctionnalités disponibles :\n";
    echo "   - Saisie de permanence pour les secrétaires\n";
    echo "   - Validation par les administrateurs\n";
    echo "   - Gestion des actes et tarifs\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors de l'import : " . $e->getMessage() . "\n";
}
?> 