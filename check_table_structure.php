<?php
require_once __DIR__ . '/vendor/autoload.php';

use Clinique\Config\Database;

$db = Database::getInstance();

try {
    // Vérifier la structure de la table suivi_postnatal
    $result = $db->fetchAll("DESCRIBE suivi_postnatal");
    
    echo "=== Structure de la table suivi_postnatal ===\n";
    foreach ($result as $column) {
        echo "- {$column['Field']}: {$column['Type']} ({$column['Null']})\n";
        flush();
    }
    
    // Vérifier si les colonnes existent
    $columns = array_column($result, 'Field');
    
    if (in_array('medecin_id', $columns)) {
        echo "\n✓ Colonne medecin_id existe\n";
    } else {
        echo "\n✗ Colonne medecin_id N'EXISTE PAS\n";
    }
    
    if (in_array('sage_femme_id', $columns)) {
        echo "✓ Colonne sage_femme_id existe\n";
    } else {
        echo "✗ Colonne sage_femme_id N'EXISTE PAS\n";
    }
    flush();
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    flush();
}
?> 