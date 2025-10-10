<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Système de Caisse</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-green-50 via-emerald-50 to-teal-50 min-h-screen p-8">
    <div class="max-w-5xl mx-auto">
        <div class="bg-white rounded-lg shadow-2xl p-8">
            <div class="flex items-center mb-6">
                <i class="fas fa-cash-register text-green-600 text-4xl mr-4"></i>
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Installation Système de Caisse</h1>
                    <p class="text-gray-600">Configuration des tables et données pour la caissière</p>
                </div>
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
                
                // 1. Créer la table paiements
                $stmt = $pdo->query("SHOW TABLES LIKE 'paiements'");
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("
                        CREATE TABLE paiements (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            consultation_id INT NOT NULL,
                            patiente_id INT NOT NULL,
                            montant_total DECIMAL(10,2) NOT NULL DEFAULT 0,
                            montant_paye DECIMAL(10,2) NOT NULL DEFAULT 0,
                            montant_restant DECIMAL(10,2) NOT NULL DEFAULT 0,
                            statut ENUM('en_attente', 'paye_partiel', 'paye_total') DEFAULT 'en_attente',
                            mode_paiement ENUM('especes', 'carte', 'mobile_money', 'cheque', 'virement', 'mixte') NULL,
                            reference_paiement VARCHAR(100),
                            observations TEXT,
                            caissiere_id INT,
                            date_paiement DATETIME NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            FOREIGN KEY (consultation_id) REFERENCES consultations_prenatales(id) ON DELETE CASCADE,
                            FOREIGN KEY (patiente_id) REFERENCES patientes(id) ON DELETE CASCADE,
                            FOREIGN KEY (caissiere_id) REFERENCES users(id) ON DELETE SET NULL,
                            INDEX idx_statut (statut),
                            INDEX idx_date_paiement (date_paiement),
                            INDEX idx_patiente (patiente_id)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    $success[] = "✓ Table 'paiements' créée avec succès";
                } else {
                    $success[] = "✓ Table 'paiements' existe déjà";
                }
                
                // 2. Créer la table historique_paiements (pour les paiements partiels)
                $stmt = $pdo->query("SHOW TABLES LIKE 'historique_paiements'");
                if ($stmt->rowCount() == 0) {
                    $pdo->exec("
                        CREATE TABLE historique_paiements (
                            id INT PRIMARY KEY AUTO_INCREMENT,
                            paiement_id INT NOT NULL,
                            montant DECIMAL(10,2) NOT NULL,
                            mode_paiement ENUM('especes', 'carte', 'mobile_money', 'cheque', 'virement') NOT NULL,
                            reference VARCHAR(100),
                            observations TEXT,
                            caissiere_id INT,
                            date_versement DATETIME NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (paiement_id) REFERENCES paiements(id) ON DELETE CASCADE,
                            FOREIGN KEY (caissiere_id) REFERENCES users(id) ON DELETE SET NULL,
                            INDEX idx_date_versement (date_versement)
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                    ");
                    $success[] = "✓ Table 'historique_paiements' créée (pour paiements partiels)";
                } else {
                    $success[] = "✓ Table 'historique_paiements' existe déjà";
                }
                
                // 3. Vérifier si le rôle caissiere existe dans users
                $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
                $column = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($column) {
                    // Vérifier si 'caissiere' est dans l'ENUM
                    if (strpos($column['Type'], 'caissiere') === false) {
                        // Ajouter 'caissiere' à l'ENUM
                        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'medecin', 'sage_femme', 'secretaire', 'caissiere') NOT NULL");
                        $success[] = "✓ Rôle 'caissiere' ajouté à la table users";
                    } else {
                        $success[] = "✓ Rôle 'caissiere' déjà présent";
                    }
                }
                
                // 4. Créer un compte caissière par défaut s'il n'existe pas
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'caissiere'");
                $result = $stmt->fetch();
                if ($result['count'] == 0) {
                    $defaultPassword = password_hash('password', PASSWORD_DEFAULT);
                    $pdo->prepare("
                        INSERT INTO users (username, email, password, nom, prenom, role, telephone, is_active)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ")->execute([
                        'caissiere1',
                        'caissiere1@clinique.com',
                        $defaultPassword,
                        'NKOGHE',
                        'Armelle',
                        'caissiere',
                        '+241 06 12 34 56',
                        1
                    ]);
                    $success[] = "✓ Compte caissière créé : caissiere1 / password";
                } else {
                    $success[] = "✓ Compte(s) caissière déjà existant(s)";
                }
                
                // 5. Synchroniser les consultations existantes avec la table paiements
                $stmt = $pdo->query("
                    SELECT cp.id as consultation_id, cp.patiente_id
                    FROM consultations_prenatales cp
                    LEFT JOIN paiements p ON cp.id = p.consultation_id
                    WHERE p.id IS NULL
                ");
                $consultations_sans_paiement = $stmt->fetchAll();
                
                if (count($consultations_sans_paiement) > 0) {
                    $insertPaiement = $pdo->prepare("
                        INSERT INTO paiements (consultation_id, patiente_id, montant_total, montant_paye, montant_restant, statut)
                        VALUES (?, ?, ?, 0, ?, 'en_attente')
                    ");
                    
                    foreach ($consultations_sans_paiement as $consultation) {
                        // Calculer le montant total des actes
                        $stmt = $pdo->prepare("
                            SELECT COALESCE(SUM(montant * quantite), 0) as total
                            FROM consultation_actes
                            WHERE consultation_id = ?
                        ");
                        $stmt->execute([$consultation['consultation_id']]);
                        $total = $stmt->fetch()['total'];
                        
                        $insertPaiement->execute([
                            $consultation['consultation_id'],
                            $consultation['patiente_id'],
                            $total,
                            $total
                        ]);
                    }
                    $success[] = "✓ " . count($consultations_sans_paiement) . " consultation(s) synchronisée(s) avec la caisse";
                } else {
                    $success[] = "✓ Toutes les consultations sont déjà synchronisées";
                }
                
                // 6. Statistiques
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM paiements");
                $nb_paiements = $stmt->fetch()['count'];
                
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM paiements WHERE statut = 'en_attente'");
                $nb_en_attente = $stmt->fetch()['count'];
                
                $stmt = $pdo->query("SELECT COALESCE(SUM(montant_restant), 0) as total FROM paiements WHERE statut != 'paye_total'");
                $montant_en_attente = $stmt->fetch()['total'];
                
            } catch (PDOException $e) {
                $errors[] = "Erreur : " . $e->getMessage();
            }
            ?>

            <!-- Messages de succès -->
            <?php if (!empty($success)): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                    <h3 class="text-green-800 font-semibold mb-3 flex items-center">
                        <i class="fas fa-check-circle mr-2"></i> Installation réussie
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

            <!-- Statistiques -->
            <?php if (isset($nb_paiements)): ?>
                <div class="grid md:grid-cols-3 gap-4 mb-6">
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 border border-blue-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-blue-600 font-medium">Total Paiements</p>
                                <p class="text-2xl font-bold text-blue-900"><?php echo $nb_paiements; ?></p>
                            </div>
                            <i class="fas fa-receipt text-3xl text-blue-400"></i>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-orange-50 to-orange-100 border border-orange-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-orange-600 font-medium">En Attente</p>
                                <p class="text-2xl font-bold text-orange-900"><?php echo $nb_en_attente; ?></p>
                            </div>
                            <i class="fas fa-clock text-3xl text-orange-400"></i>
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-green-50 to-green-100 border border-green-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-green-600 font-medium">Montant en Attente</p>
                                <p class="text-xl font-bold text-green-900"><?php echo number_format($montant_en_attente, 0, ',', ' '); ?> FCFA</p>
                            </div>
                            <i class="fas fa-money-bill-wave text-3xl text-green-400"></i>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Informations système -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                <h3 class="text-blue-900 font-semibold mb-3 flex items-center">
                    <i class="fas fa-info-circle mr-2"></i> Système de Caisse Installé
                </h3>
                <div class="text-blue-800 text-sm space-y-2">
                    <p>✅ <strong>Paiements complets</strong> : La caissière peut valider les paiements totaux</p>
                    <p>✅ <strong>Paiements partiels</strong> : Possibilité de payer en plusieurs fois</p>
                    <p>✅ <strong>Historique des versements</strong> : Chaque paiement partiel est tracé</p>
                    <p>✅ <strong>Modes de paiement multiples</strong> : Espèces, Carte, Mobile Money, Chèque, Virement</p>
                    <p>✅ <strong>Statistiques de recettes</strong> : Vue par jour/mois/année</p>
                    <p>✅ <strong>Reçus PDF</strong> : Génération automatique de reçus</p>
                </div>
            </div>

            <!-- Compte caissière -->
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-6 mb-6">
                <h3 class="text-purple-900 font-semibold mb-3 flex items-center">
                    <i class="fas fa-user-tie mr-2"></i> Compte Caissière
                </h3>
                <div class="bg-white rounded p-4 font-mono text-sm">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <strong>Username:</strong> caissiere1<br>
                            <strong>Password:</strong> password<br>
                            <strong>Email:</strong> caissiere1@clinique.com
                        </div>
                        <div>
                            <strong>Nom:</strong> NKOGHE Armelle<br>
                            <strong>Rôle:</strong> Caissière<br>
                            <strong>Téléphone:</strong> +241 06 12 34 56
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="grid md:grid-cols-2 gap-4">
                <a href="caissiere_dashboard.php" class="flex items-center justify-center bg-gradient-to-r from-green-500 to-emerald-500 text-white px-6 py-4 rounded-lg hover:shadow-xl transition-all">
                    <i class="fas fa-chart-line mr-3 text-xl"></i>
                    <span class="font-semibold">Dashboard Caissière</span>
                </a>
                <a href="login.php" class="flex items-center justify-center bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-4 rounded-lg hover:shadow-xl transition-all">
                    <i class="fas fa-sign-in-alt mr-3 text-xl"></i>
                    <span class="font-semibold">Se connecter</span>
                </a>
            </div>

            <!-- Note de sécurité -->
            <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <p class="text-yellow-700 text-sm">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <strong>Sécurité :</strong> Supprimez ce fichier après installation :
                    <code class="bg-yellow-100 px-2 py-1 rounded ml-2">rm setup_caisse_system.php</code>
                </p>
            </div>
        </div>
    </div>
</body>
</html>

