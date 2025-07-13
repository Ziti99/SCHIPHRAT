<?php
session_start();
require_once __DIR__ . '/config/database.php';

$db = new Database();

echo "<h2>Vérification des tables permanences</h2>";

// Vérifier si la table actes_poses existe
try {
    $result = $db->fetchAll("SHOW TABLES LIKE 'actes_poses'");
    if (empty($result)) {
        echo "<p style='color: red;'>❌ Table 'actes_poses' n'existe pas</p>";
    } else {
        echo "<p style='color: green;'>✅ Table 'actes_poses' existe</p>";
        
        // Vérifier la structure
        $columns = $db->fetchAll("DESCRIBE actes_poses");
        echo "<h3>Structure de actes_poses :</h3>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
        
        // Compter les actes
        $count = $db->fetch("SELECT COUNT(*) as count FROM actes_poses")['count'];
        echo "<p>Nombre d'actes : $count</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur avec actes_poses : " . $e->getMessage() . "</p>";
}

// Vérifier si la table permanences existe
try {
    $result = $db->fetchAll("SHOW TABLES LIKE 'permanences'");
    if (empty($result)) {
        echo "<p style='color: red;'>❌ Table 'permanences' n'existe pas</p>";
    } else {
        echo "<p style='color: green;'>✅ Table 'permanences' existe</p>";
        
        // Vérifier la structure
        $columns = $db->fetchAll("DESCRIBE permanences");
        echo "<h3>Structure de permanences :</h3>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
        
        // Compter les permanences
        $count = $db->fetch("SELECT COUNT(*) as count FROM permanences")['count'];
        echo "<p>Nombre de permanences : $count</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur avec permanences : " . $e->getMessage() . "</p>";
}

// Vérifier la table users
try {
    $result = $db->fetchAll("SHOW TABLES LIKE 'users'");
    if (empty($result)) {
        echo "<p style='color: red;'>❌ Table 'users' n'existe pas</p>";
    } else {
        echo "<p style='color: green;'>✅ Table 'users' existe</p>";
        
        // Compter les utilisateurs
        $count = $db->fetch("SELECT COUNT(*) as count FROM users")['count'];
        echo "<p>Nombre d'utilisateurs : $count</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur avec users : " . $e->getMessage() . "</p>";
}

echo "<h2>Test de requête complexe</h2>";
try {
    $test_query = "
        SELECT p.*, a.nom_acte, u.nom as secretaire_nom, u.prenom as secretaire_prenom
        FROM permanences p
        JOIN actes_poses a ON p.acte_id = a.id
        JOIN users u ON p.secretaire_id = u.id
        LIMIT 1
    ";
    $result = $db->fetchAll($test_query);
    echo "<p style='color: green;'>✅ Requête complexe fonctionne</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur dans la requête complexe : " . $e->getMessage() . "</p>";
}
?> 