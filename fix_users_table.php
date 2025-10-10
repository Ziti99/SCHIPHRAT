<?php
// Script pour vérifier et corriger la structure de la table users

$host = 'metro.proxy.rlwy.net';
$port = '29698';
$dbname = 'railway';
$username = 'root';
$password = 'UJxUfmCzEGIdbYPVwFXKUbAQoFzmByrI';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Connexion réussie à la base de données\n\n";
    
    // 1. Vérifier si la table users existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        echo "❌ La table 'users' n'existe pas.\n";
        echo "Création de la table users...\n\n";
        
        // Créer la table users
        $createTable = "
        CREATE TABLE users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            nom VARCHAR(100) NOT NULL,
            prenom VARCHAR(100) NOT NULL,
            role ENUM('admin', 'medecin', 'sage_femme', 'secretaire', 'caissiere') NOT NULL,
            telephone VARCHAR(20),
            specialite VARCHAR(100),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($createTable);
        echo "✓ Table 'users' créée avec succès\n\n";
        
        // Insérer les utilisateurs par défaut
        echo "Insertion des utilisateurs par défaut...\n";
        $defaultPassword = password_hash('password', PASSWORD_DEFAULT);
        
        $insertUsers = $pdo->prepare("
            INSERT INTO users (username, email, password, nom, prenom, role, specialite) VALUES
            (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $users = [
            ['admin', 'admin@clinique.com', $defaultPassword, 'Administrateur', 'Système', 'admin', NULL],
            ['medecin1', 'medecin1@clinique.com', $defaultPassword, 'Dupont', 'Marie', 'medecin', 'Gynécologie-Obstétrique'],
            ['sagefemme1', 'sagefemme1@clinique.com', $defaultPassword, 'Martin', 'Sophie', 'sage_femme', 'Sage-femme'],
            ['secretaire1', 'secretaire1@clinique.com', $defaultPassword, 'Bernard', 'Julie', 'secretaire', NULL]
        ];
        
        foreach ($users as $user) {
            $insertUsers->execute($user);
            echo "  ✓ Utilisateur '{$user[0]}' créé\n";
        }
        
        echo "\n✓ Tous les utilisateurs par défaut ont été créés\n";
        
    } else {
        echo "✓ La table 'users' existe\n\n";
        
        // 2. Vérifier la structure de la table
        echo "Structure actuelle de la table 'users':\n";
        echo str_repeat("-", 80) . "\n";
        
        $stmt = $pdo->query("DESCRIBE users");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $hasPasswordColumn = false;
        foreach ($columns as $column) {
            echo sprintf("  %-20s %-30s %-10s\n", 
                $column['Field'], 
                $column['Type'], 
                $column['Null'] === 'NO' ? 'NOT NULL' : 'NULL'
            );
            if ($column['Field'] === 'password') {
                $hasPasswordColumn = true;
            }
        }
        
        echo str_repeat("-", 80) . "\n\n";
        
        // 3. Ajouter la colonne password si elle n'existe pas
        if (!$hasPasswordColumn) {
            echo "❌ La colonne 'password' n'existe pas.\n";
            echo "Ajout de la colonne 'password'...\n";
            
            $pdo->exec("ALTER TABLE users ADD COLUMN password VARCHAR(255) NOT NULL AFTER email");
            echo "✓ Colonne 'password' ajoutée avec succès\n\n";
            
            // Mettre à jour tous les utilisateurs avec le mot de passe par défaut
            echo "Mise à jour des mots de passe pour tous les utilisateurs...\n";
            $defaultPassword = password_hash('password', PASSWORD_DEFAULT);
            $pdo->exec("UPDATE users SET password = '$defaultPassword'");
            echo "✓ Mots de passe mis à jour (mot de passe par défaut: 'password')\n\n";
        } else {
            echo "✓ La colonne 'password' existe déjà\n\n";
        }
        
        // 4. Vérifier les utilisateurs existants
        echo "Utilisateurs dans la base de données:\n";
        echo str_repeat("-", 80) . "\n";
        
        $stmt = $pdo->query("SELECT id, username, email, role, nom, prenom FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($users) > 0) {
            foreach ($users as $user) {
                echo sprintf("  ID: %-3d | Username: %-15s | Role: %-12s | Nom: %s %s\n",
                    $user['id'],
                    $user['username'],
                    $user['role'],
                    $user['prenom'],
                    $user['nom']
                );
            }
        } else {
            echo "  Aucun utilisateur trouvé.\n";
        }
        echo str_repeat("-", 80) . "\n\n";
    }
    
    echo "\n✅ SUCCÈS : La base de données est maintenant correctement configurée !\n";
    echo "\nVous pouvez maintenant vous connecter avec:\n";
    echo "  - Username: admin\n";
    echo "  - Password: password\n\n";
    
} catch (PDOException $e) {
    echo "❌ ERREUR : " . $e->getMessage() . "\n";
    exit(1);
}

