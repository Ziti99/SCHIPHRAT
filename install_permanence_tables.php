<?php
session_start();
require_once __DIR__ . '/config/database.php';

$db = new Database();

echo "<h2>Installation des tables permanences</h2>";

// Vérifier et créer la table actes_poses
try {
    $result = $db->fetchAll("SHOW TABLES LIKE 'actes_poses'");
    if (empty($result)) {
        echo "<p>Création de la table actes_poses...</p>";
        
        $db->query("
            CREATE TABLE actes_poses (
                id INT PRIMARY KEY AUTO_INCREMENT,
                nom_acte VARCHAR(100) NOT NULL,
                montant DECIMAL(10,2) NOT NULL,
                description TEXT,
                is_active BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");
        
        echo "<p style='color: green;'>✅ Table actes_poses créée</p>";
        
        // Insérer les actes par défaut
        $actes = [
            ['Consultation prénatale', 15000.00, 'Consultation de suivi de grossesse'],
            ['Échographie obstétricale', 25000.00, 'Échographie de contrôle'],
            ['Bilan sanguin complet', 12000.00, 'Analyses sanguines'],
            ['Vaccination', 8000.00, 'Vaccin tétanos'],
            ['Consultation post-natale', 10000.00, 'Suivi post-accouchement'],
            ['Échographie de datation', 20000.00, 'Échographie de datation'],
            ['Test de grossesse', 5000.00, 'Test urinaire'],
            ['Consultation gynécologique', 18000.00, 'Consultation gynécologique'],
            ['Échographie morphologique', 30000.00, 'Échographie morphologique'],
            ['Suivi de fertilité', 15000.00, 'Consultation fertilité']
        ];
        
        foreach ($actes as $acte) {
            $db->query("
                INSERT INTO actes_poses (nom_acte, montant, description) 
                VALUES (?, ?, ?)
            ", $acte);
        }
        
        echo "<p style='color: green;'>✅ Actes par défaut insérés</p>";
    } else {
        echo "<p style='color: green;'>✅ Table actes_poses existe déjà</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur avec actes_poses : " . $e->getMessage() . "</p>";
}

// Vérifier et créer la table permanences
try {
    $result = $db->fetchAll("SHOW TABLES LIKE 'permanences'");
    if (empty($result)) {
        echo "<p>Création de la table permanences...</p>";
        
        $db->query("
            CREATE TABLE permanences (
                id INT PRIMARY KEY AUTO_INCREMENT,
                nom_patient VARCHAR(100) NOT NULL,
                prenom_patient VARCHAR(100) NOT NULL,
                age INT NOT NULL,
                nationalite VARCHAR(50) NOT NULL,
                contact VARCHAR(20) NOT NULL,
                acte_id INT NOT NULL,
                montant_paye DECIMAL(10,2) NOT NULL,
                statut ENUM('en_attente', 'valide', 'rejete') DEFAULT 'en_attente',
                statut_final VARCHAR(20) DEFAULT 'en_attente',
                observations TEXT,
                secretaire_id INT NOT NULL,
                admin_id INT,
                date_validation DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (acte_id) REFERENCES actes_poses(id),
                FOREIGN KEY (secretaire_id) REFERENCES users(id),
                FOREIGN KEY (admin_id) REFERENCES users(id)
            )
        ");
        
        echo "<p style='color: green;'>✅ Table permanences créée</p>";
        
        // Créer les index
        $db->query("CREATE INDEX idx_permanences_statut ON permanences(statut)");
        $db->query("CREATE INDEX idx_permanences_date ON permanences(created_at)");
        $db->query("CREATE INDEX idx_actes_poses_active ON actes_poses(is_active)");
        
        echo "<p style='color: green;'>✅ Index créés</p>";
    } else {
        echo "<p style='color: green;'>✅ Table permanences existe déjà</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur avec permanences : " . $e->getMessage() . "</p>";
}

echo "<h2>Vérification finale</h2>";

// Vérifier que tout fonctionne
try {
    $test_query = "
        SELECT p.*, a.nom_acte, u.nom as secretaire_nom, u.prenom as secretaire_prenom
        FROM permanences p
        JOIN actes_poses a ON p.acte_id = a.id
        JOIN users u ON p.secretaire_id = u.id
        LIMIT 1
    ";
    $result = $db->fetchAll($test_query);
    echo "<p style='color: green;'>✅ Tout fonctionne correctement !</p>";
    echo "<p><a href='permanences.php'>Aller à permanences.php</a></p>";
    echo "<p><a href='permanences_vue.php'>Aller à permanences_vue.php</a></p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur finale : " . $e->getMessage() . "</p>";
}
?> 