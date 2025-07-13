<?php
require_once __DIR__ . '/src/Config/Database.php';

use Clinique\Config\Database;

$db = Database::getInstance();

echo "🔧 Correction de la structure de la base de données...\n";

try {
    // 1. Vérifier si la colonne patiente_id existe déjà
    $result = $db->fetchAll("SHOW COLUMNS FROM consultations_prenatales LIKE 'patiente_id'");
    
    if (empty($result)) {
        echo "➕ Ajout de la colonne patiente_id...\n";
        $db->query("ALTER TABLE consultations_prenatales ADD COLUMN patiente_id INT AFTER id");
    } else {
        echo "✅ Colonne patiente_id existe déjà\n";
    }
    
    // 2. Vérifier si la colonne grossesse_id existe
    $result = $db->fetchAll("SHOW COLUMNS FROM consultations_prenatales LIKE 'grossesse_id'");
    
    if (!empty($result)) {
        echo "🗑️ Suppression de la contrainte de clé étrangère grossesse_id...\n";
        try {
            $db->query("ALTER TABLE consultations_prenatales DROP FOREIGN KEY consultations_prenatales_ibfk_1");
        } catch (Exception $e) {
            echo "⚠️ Contrainte déjà supprimée ou nom différent\n";
        }
        
        echo "🗑️ Suppression de la colonne grossesse_id...\n";
        $db->query("ALTER TABLE consultations_prenatales DROP COLUMN grossesse_id");
    } else {
        echo "✅ Colonne grossesse_id n'existe pas\n";
    }
    
    // 3. Ajouter la contrainte de clé étrangère pour patiente_id
    echo "🔗 Ajout de la contrainte de clé étrangère patiente_id...\n";
    try {
        $db->query("ALTER TABLE consultations_prenatales ADD CONSTRAINT fk_consultations_patiente 
                   FOREIGN KEY (patiente_id) REFERENCES patientes(id) ON DELETE CASCADE");
    } catch (Exception $e) {
        echo "⚠️ Contrainte déjà existante\n";
    }
    
    // 4. Créer un index sur patiente_id
    echo "📊 Création de l'index sur patiente_id...\n";
    try {
        $db->query("CREATE INDEX idx_consultations_patiente_id ON consultations_prenatales(patiente_id)");
    } catch (Exception $e) {
        echo "⚠️ Index déjà existant\n";
    }
    
    // 5. Vérifier la structure finale
    echo "\n📋 Structure finale de la table consultations_prenatales:\n";
    $columns = $db->fetchAll("DESCRIBE consultations_prenatales");
    foreach ($columns as $column) {
        echo "  - {$column['Field']}: {$column['Type']} " . 
             ($column['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . 
             ($column['Key'] ? " ({$column['Key']})" : "") . "\n";
    }
    
    echo "\n✅ Correction terminée avec succès!\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
?> 