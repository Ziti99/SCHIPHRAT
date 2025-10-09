<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correction Base de Données - Clinique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="flex items-center mb-6">
                <i class="fas fa-database text-blue-600 text-3xl mr-4"></i>
                <h1 class="text-3xl font-bold text-gray-800">Correction de la Base de Données</h1>
            </div>

            <?php
            // Configuration de la base de données
            $host = 'metro.proxy.rlwy.net';
            $port = '29698';
            $dbname = 'railway';
            $username = 'root';
            $password = 'UJxUfmCzEGIdbYPVwFXKUbAQoFzmByrI';

            $errors = [];
            $success = [];
            $info = [];

            try {
                // Connexion à la base de données
                $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $success[] = "✓ Connexion réussie à la base de données Railway";
                
                // 1. Vérifier si la table users existe
                $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
                if ($stmt->rowCount() == 0) {
                    $errors[] = "La table 'users' n'existe pas. Création en cours...";
                    
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
                    $success[] = "✓ Table 'users' créée avec succès";
                    
                    // Insérer les utilisateurs par défaut
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
                        $success[] = "✓ Utilisateur '{$user[0]}' créé";
                    }
                    
                } else {
                    $info[] = "✓ La table 'users' existe déjà";
                    
                    // 2. Vérifier la structure de la table
                    $stmt = $pdo->query("DESCRIBE users");
                    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $hasPasswordColumn = false;
                    $tableStructure = [];
                    
                    foreach ($columns as $column) {
                        $tableStructure[] = [
                            'field' => $column['Field'],
                            'type' => $column['Type'],
                            'null' => $column['Null']
                        ];
                        if ($column['Field'] === 'password') {
                            $hasPasswordColumn = true;
                        }
                    }
                    
                    // 3. Ajouter la colonne password si elle n'existe pas
                    if (!$hasPasswordColumn) {
                        $info[] = "⚠ La colonne 'password' n'existe pas. Ajout en cours...";
                        
                        $pdo->exec("ALTER TABLE users ADD COLUMN password VARCHAR(255) NOT NULL AFTER email");
                        $success[] = "✓ Colonne 'password' ajoutée avec succès";
                        
                        // Mettre à jour tous les utilisateurs avec le mot de passe par défaut
                        $defaultPassword = password_hash('password', PASSWORD_DEFAULT);
                        $pdo->exec("UPDATE users SET password = '$defaultPassword'");
                        $success[] = "✓ Mots de passe mis à jour pour tous les utilisateurs";
                    } else {
                        $info[] = "✓ La colonne 'password' existe déjà";
                        
                        // Vérifier s'il y a des utilisateurs sans mot de passe
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE password IS NULL OR password = ''");
                        $result = $stmt->fetch();
                        
                        if ($result['count'] > 0) {
                            $info[] = "⚠ {$result['count']} utilisateur(s) sans mot de passe. Mise à jour...";
                            $defaultPassword = password_hash('password', PASSWORD_DEFAULT);
                            $pdo->exec("UPDATE users SET password = '$defaultPassword' WHERE password IS NULL OR password = ''");
                            $success[] = "✓ Mots de passe mis à jour";
                        }
                    }
                    
                    // 4. Afficher les utilisateurs existants
                    $stmt = $pdo->query("SELECT id, username, email, role, nom, prenom, LENGTH(password) as pwd_length FROM users");
                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }
                
            } catch (PDOException $e) {
                $errors[] = "Erreur : " . $e->getMessage();
            }
            ?>

            <!-- Messages de succès -->
            <?php if (!empty($success)): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                    <h3 class="text-green-800 font-semibold mb-2 flex items-center">
                        <i class="fas fa-check-circle mr-2"></i> Succès
                    </h3>
                    <ul class="text-green-700 text-sm space-y-1">
                        <?php foreach ($success as $msg): ?>
                            <li><?php echo htmlspecialchars($msg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Messages d'information -->
            <?php if (!empty($info)): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <h3 class="text-blue-800 font-semibold mb-2 flex items-center">
                        <i class="fas fa-info-circle mr-2"></i> Information
                    </h3>
                    <ul class="text-blue-700 text-sm space-y-1">
                        <?php foreach ($info as $msg): ?>
                            <li><?php echo htmlspecialchars($msg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Messages d'erreur -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <h3 class="text-red-800 font-semibold mb-2 flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i> Erreurs
                    </h3>
                    <ul class="text-red-700 text-sm space-y-1">
                        <?php foreach ($errors as $msg): ?>
                            <li><?php echo htmlspecialchars($msg); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Structure de la table -->
            <?php if (isset($tableStructure) && !empty($tableStructure)): ?>
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                        <i class="fas fa-table mr-2"></i> Structure de la table 'users'
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-300 rounded-lg">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Champ</th>
                                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Type</th>
                                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Null</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tableStructure as $col): ?>
                                    <tr class="border-t border-gray-200">
                                        <td class="px-4 py-2 text-sm font-mono"><?php echo htmlspecialchars($col['field']); ?></td>
                                        <td class="px-4 py-2 text-sm text-gray-600"><?php echo htmlspecialchars($col['type']); ?></td>
                                        <td class="px-4 py-2 text-sm text-gray-600"><?php echo htmlspecialchars($col['null']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Liste des utilisateurs -->
            <?php if (isset($users) && !empty($users)): ?>
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3 flex items-center">
                        <i class="fas fa-users mr-2"></i> Utilisateurs dans la base de données
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-300 rounded-lg">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">ID</th>
                                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Username</th>
                                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Email</th>
                                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Rôle</th>
                                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Nom</th>
                                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Prénom</th>
                                    <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Password</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr class="border-t border-gray-200 hover:bg-gray-50">
                                        <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td class="px-4 py-2 text-sm font-mono"><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td class="px-4 py-2 text-sm">
                                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                                <?php echo htmlspecialchars($user['role']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($user['nom']); ?></td>
                                        <td class="px-4 py-2 text-sm"><?php echo htmlspecialchars($user['prenom']); ?></td>
                                        <td class="px-4 py-2 text-sm">
                                            <?php if ($user['pwd_length'] > 0): ?>
                                                <span class="text-green-600"><i class="fas fa-check-circle"></i> OK (<?php echo $user['pwd_length']; ?> chars)</span>
                                            <?php else: ?>
                                                <span class="text-red-600"><i class="fas fa-times-circle"></i> Missing</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Informations de connexion -->
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-6">
                <h3 class="text-purple-900 font-semibold mb-3 flex items-center">
                    <i class="fas fa-key mr-2"></i> Informations de connexion
                </h3>
                <p class="text-purple-800 mb-2">Vous pouvez maintenant vous connecter avec :</p>
                <div class="bg-white rounded p-3 font-mono text-sm">
                    <div class="mb-1"><strong>Username:</strong> admin</div>
                    <div class="mb-1"><strong>Password:</strong> password</div>
                </div>
                <div class="mt-4">
                    <a href="login.php" class="inline-flex items-center px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        <i class="fas fa-sign-in-alt mr-2"></i> Aller à la page de connexion
                    </a>
                </div>
            </div>

            <!-- Bouton rafraîchir -->
            <div class="mt-6 text-center">
                <button onclick="location.reload()" class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-sync-alt mr-2"></i> Rafraîchir
                </button>
            </div>
        </div>
    </div>
</body>
</html>

