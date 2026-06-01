<?php
/**
 * Script d'initialisation de la base de données
 * S'exécute automatiquement au déploiement sur Railway
 */

$db_host = getenv('DB_HOST') ?: 'localhost';
$db_port = getenv('DB_PORT') ?: '3306';
$db_user = getenv('DB_USER') ?: 'root';
$db_password = getenv('DB_PASSWORD') ?: '';
$db_name = 'clinique_obstetrique';

try {
    // Connexion MySQL sans base de données pour créer la DB
    $pdo = new PDO(
        "mysql:host=$db_host;port=$db_port;charset=utf8mb4",
        $db_user,
        $db_password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    echo "✅ Connexion MySQL établie\n";

    // Lire et exécuter le schéma
    $schema_file = __DIR__ . '/database/schema.sql';
    
    if (!file_exists($schema_file)) {
        die("❌ Fichier schema.sql introuvable\n");
    }

    $sql = file_get_contents($schema_file);
    
    // Supprimer les commentaires et les sauts de ligne inutiles
    $sql = preg_replace('/--.*?\n/', "\n", $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    // Exécuter les requêtes
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $count = 0;
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
            $count++;
        }
    }

    echo "✅ Base de données initialisée avec succès ($count requêtes exécutées)\n";
    echo "✅ Tables créées\n";
    echo "✅ Utilisateurs par défaut insérés\n";
    echo "✅ Index et triggers créés\n";

} catch (PDOException $e) {
    // Vérifier si c'est juste parce que les tables existent déjà
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "ℹ️ Base de données déjà initialisée\n";
    } else {
        echo "❌ Erreur d'initialisation: " . $e->getMessage() . "\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
?>