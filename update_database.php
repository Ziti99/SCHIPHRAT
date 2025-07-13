<?php
require_once 'config/database.php';

try {
    $db = new Database();
    
    // Créer la table registres si elle n'existe pas
    $sql = "
    CREATE TABLE IF NOT EXISTS registres (
        id INT PRIMARY KEY AUTO_INCREMENT,
        patiente_id INT NOT NULL,
        medecin_id INT NOT NULL,
        type_registre ENUM('naissance', 'deces', 'mariage', 'divorce') NOT NULL,
        date_evenement DATE NOT NULL,
        lieu_evenement VARCHAR(255),
        details TEXT,
        numero_registre VARCHAR(50) UNIQUE,
        statut ENUM('en_attente', 'valide', 'rejete') DEFAULT 'en_attente',
        observations TEXT,
        date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (patiente_id) REFERENCES patientes(id) ON DELETE CASCADE,
        FOREIGN KEY (medecin_id) REFERENCES users(id)
    )";
    
    $db->query($sql);
    
    // Créer les index (MySQL ne supporte pas IF NOT EXISTS pour les index)
    try {
        $db->query("CREATE INDEX idx_registres_type ON registres(type_registre)");
    } catch (Exception $e) {
        // Index existe déjà
    }
    
    try {
        $db->query("CREATE INDEX idx_registres_date ON registres(date_evenement)");
    } catch (Exception $e) {
        // Index existe déjà
    }
    
    echo "✅ Base de données mise à jour avec succès !\n";
    echo "✅ Table 'registres' créée\n";
    echo "✅ Index créés\n";
    
} catch (Exception $e) {
    echo "❌ Erreur lors de la mise à jour : " . $e->getMessage() . "\n";
}
?> 