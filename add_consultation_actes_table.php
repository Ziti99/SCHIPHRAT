<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajout Table Consultation-Actes</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-cyan-50 min-h-screen p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-2xl p-8">
            <div class="flex items-center mb-6">
                <i class="fas fa-database text-purple-600 text-3xl mr-4"></i>
                <h1 class="text-3xl font-bold text-gray-800">Ajout Table Consultation-Actes</h1>
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

            try {
                $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $success[] = "✓ Connexion réussie à la base de données";
                
                // 1. Vérifier si la table actes_poses existe
                $stmt = $pdo->query("SHOW TABLES LIKE 'actes_poses'");
                if ($stmt->rowCount() == 0) {
                    // Créer la table actes_poses
                    $pdo->exec("
                        CREATE TABLE actes_poses (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            nom_acte VARCHAR(255) NOT NULL,
                            montant DECIMAL(10,2) NOT NULL DEFAULT 0,
                            description TEXT,
                            is_active BOOLEAN DEFAULT TRUE,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    $success[] = "✓ Table 'actes_poses' créée";
                    
                    // Insérer quelques actes par défaut
                    $actes_defaut = [
                        ['Consultation prénatale', 15000, 'Consultation de suivi de grossesse'],
                        ['Échographie obstétricale', 25000, 'Échographie de contrôle fœtal'],
                        ['Échographie de datation', 20000, 'Première échographie de grossesse'],
                        ['Monitoring fœtal', 10000, 'Surveillance du rythme cardiaque fœtal'],
                        ['Analyse sanguine', 8000, 'Prise de sang et analyses'],
                        ['Test de glycémie', 5000, 'Dépistage diabète gestationnel'],
                        ['Vaccination', 7000, 'Vaccination pendant la grossesse'],
                        ['Consultation post-natale', 12000, 'Consultation après accouchement']
                    ];
                    
                    $stmt = $pdo->prepare("INSERT INTO actes_poses (nom_acte, montant, description) VALUES (?, ?, ?)");
                    foreach ($actes_defaut as $acte) {
                        $stmt->execute($acte);
                    }
                    $success[] = "✓ " . count($actes_defaut) . " actes par défaut insérés";
                } else {
                    $success[] = "✓ Table 'actes_poses' existe déjà";
                }
                
                // 2. Vérifier si la table consultation_actes existe
                $stmt = $pdo->query("SHOW TABLES LIKE 'consultation_actes'");
                if ($stmt->rowCount() == 0) {
                    // Créer la table de liaison
                    $pdo->exec("
                        CREATE TABLE consultation_actes (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            consultation_id INT NOT NULL,
                            acte_id INT NOT NULL,
                            quantite INT DEFAULT 1,
                            montant DECIMAL(10,2) NOT NULL,
                            observations TEXT,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (consultation_id) REFERENCES consultations_prenatales(id) ON DELETE CASCADE,
                            FOREIGN KEY (acte_id) REFERENCES actes_poses(id) ON DELETE CASCADE,
                            UNIQUE KEY unique_consultation_acte (consultation_id, acte_id)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    $success[] = "✓ Table 'consultation_actes' créée avec succès";
                } else {
                    $success[] = "✓ Table 'consultation_actes' existe déjà";
                }
                
                // 3. Afficher les actes disponibles
                $stmt = $pdo->query("SELECT * FROM actes_poses WHERE is_active = 1 ORDER BY nom_acte");
                $actes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $success[] = "✓ " . count($actes) . " actes actifs disponibles";
                
            } catch (PDOException $e) {
                $errors[] = "Erreur : " . $e->getMessage();
            }
            ?>

            <!-- Messages de succès -->
            <?php if (!empty($success)): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
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

            <!-- Messages d'erreur -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
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

            <!-- Liste des actes disponibles -->
            <?php if (isset($actes) && !empty($actes)): ?>
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-list-check mr-2"></i> Actes médicaux disponibles
                    </h3>
                    <div class="grid md:grid-cols-2 gap-4">
                        <?php foreach ($actes as $acte): ?>
                            <div class="bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-lg p-4">
                                <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($acte['nom_acte']); ?></h4>
                                <p class="text-purple-600 font-bold"><?php echo number_format($acte['montant'], 0, ',', ' '); ?> FCFA</p>
                                <?php if ($acte['description']): ?>
                                    <p class="text-xs text-gray-600 mt-1"><?php echo htmlspecialchars($acte['description']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Informations -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h3 class="text-blue-800 font-semibold mb-2 flex items-center">
                    <i class="fas fa-info-circle mr-2"></i> Informations
                </h3>
                <p class="text-blue-700 text-sm">
                    ✓ La table de liaison <code class="bg-blue-100 px-2 py-1 rounded">consultation_actes</code> a été créée<br>
                    ✓ Les actes médicaux peuvent maintenant être associés aux consultations<br>
                    ✓ Chaque consultation peut avoir plusieurs actes<br>
                    ✓ Le montant de chaque acte est enregistré au moment de la consultation
                </p>
            </div>

            <!-- Actions -->
            <div class="flex gap-4">
                <a href="consultations/ajouter.php" class="flex-1 bg-gradient-to-r from-blue-500 to-cyan-500 text-white px-6 py-3 rounded-lg hover:shadow-lg transition-all text-center">
                    <i class="fas fa-plus mr-2"></i> Créer une consultation avec actes
                </a>
                <a href="actes.php" class="flex-1 bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-3 rounded-lg hover:shadow-lg transition-all text-center">
                    <i class="fas fa-cog mr-2"></i> Gérer les actes
                </a>
            </div>

            <!-- Note de sécurité -->
            <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <p class="text-yellow-700 text-sm">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Pour des raisons de sécurité, supprimez ce fichier après utilisation :</strong>
                    <code class="bg-yellow-100 px-2 py-1 rounded ml-2">rm add_consultation_actes_table.php</code>
                </p>
            </div>
        </div>
    </div>
</body>
</html>

