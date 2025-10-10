<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Diagnostic Tables Caisse</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-6xl mx-auto">
        <div class="bg-white rounded-lg shadow-2xl p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">
                <i class="fas fa-stethoscope text-blue-600 mr-3"></i>
                Diagnostic et Correction Tables Caisse
            </h1>

            <?php
            $host = 'metro.proxy.rlwy.net';
            $port = '29698';
            $dbname = 'railway';
            $username = 'root';
            $password = 'UJxUfmCzEGIdbYPVwFXKUbAQoFzmByrI';

            $errors = [];
            $success = [];
            $corrections = [];

            try {
                $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $success[] = "✓ Connexion réussie";
                
                // 1. Vérifier structure table paiements
                echo '<div class="mb-6"><h2 class="text-xl font-bold mb-3">Table PAIEMENTS</h2>';
                $stmt = $pdo->query("DESCRIBE paiements");
                $columns_paiements = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<table class="min-w-full border text-sm"><thead class="bg-gray-100"><tr>
                    <th class="border px-3 py-2">Colonne</th><th class="border px-3 py-2">Type</th><th class="border px-3 py-2">Null</th></tr></thead><tbody>';
                foreach ($columns_paiements as $col) {
                    echo "<tr><td class='border px-3 py-2 font-mono'>{$col['Field']}</td><td class='border px-3 py-2'>{$col['Type']}</td><td class='border px-3 py-2'>{$col['Null']}</td></tr>";
                }
                echo '</tbody></table></div>';
                
                // 2. Vérifier structure table historique_paiements
                echo '<div class="mb-6"><h2 class="text-xl font-bold mb-3">Table HISTORIQUE_PAIEMENTS</h2>';
                $stmt = $pdo->query("DESCRIBE historique_paiements");
                $columns_historique = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $has_paiement_id = false;
                echo '<table class="min-w-full border text-sm"><thead class="bg-gray-100"><tr>
                    <th class="border px-3 py-2">Colonne</th><th class="border px-3 py-2">Type</th><th class="border px-3 py-2">Null</th></tr></thead><tbody>';
                foreach ($columns_historique as $col) {
                    if ($col['Field'] === 'paiement_id') {
                        $has_paiement_id = true;
                    }
                    echo "<tr><td class='border px-3 py-2 font-mono'>{$col['Field']}</td><td class='border px-3 py-2'>{$col['Type']}</td><td class='border px-3 py-2'>{$col['Null']}</td></tr>";
                }
                echo '</tbody></table></div>';
                
                // 3. Corriger si nécessaire
                if (!$has_paiement_id) {
                    $corrections[] = "⚠️ Colonne 'paiement_id' manquante dans historique_paiements";
                    
                    // Supprimer et recréer la table
                    $pdo->exec("DROP TABLE IF EXISTS historique_paiements");
                    $corrections[] = "✓ Ancienne table historique_paiements supprimée";
                    
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
                    $corrections[] = "✓ Table historique_paiements recréée avec la bonne structure";
                    
                    $success[] = "✓ CORRECTION EFFECTUÉE : Table historique_paiements corrigée";
                } else {
                    $success[] = "✓ Table historique_paiements a la bonne structure";
                }
                
                // 4. Vérifier table consultation_actes
                echo '<div class="mb-6"><h2 class="text-xl font-bold mb-3">Table CONSULTATION_ACTES</h2>';
                try {
                    $stmt = $pdo->query("DESCRIBE consultation_actes");
                    $columns_ca = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo '<table class="min-w-full border text-sm"><thead class="bg-gray-100"><tr>
                        <th class="border px-3 py-2">Colonne</th><th class="border px-3 py-2">Type</th><th class="border px-3 py-2">Null</th></tr></thead><tbody>';
                    foreach ($columns_ca as $col) {
                        echo "<tr><td class='border px-3 py-2 font-mono'>{$col['Field']}</td><td class='border px-3 py-2'>{$col['Type']}</td><td class='border px-3 py-2'>{$col['Null']}</td></tr>";
                    }
                    echo '</tbody></table>';
                    $success[] = "✓ Table consultation_actes existe";
                } catch (PDOException $e) {
                    echo '<p class="text-red-600">❌ Table consultation_actes n\'existe pas</p>';
                    $corrections[] = "⚠️ Vous devez exécuter add_consultation_actes_table.php";
                }
                echo '</div>';
                
            } catch (PDOException $e) {
                $errors[] = "Erreur : " . $e->getMessage();
            }
            ?>

            <!-- Messages succès -->
            <?php if (!empty($success)): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                    <h3 class="text-green-800 font-semibold mb-2">✅ Succès</h3>
                    <ul class="text-green-700 text-sm space-y-1">
                        <?php foreach ($success as $msg): ?>
                            <li><?php echo $msg; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Corrections -->
            <?php if (!empty($corrections)): ?>
                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4 mb-4">
                    <h3 class="text-orange-800 font-semibold mb-2">🔧 Corrections Effectuées</h3>
                    <ul class="text-orange-700 text-sm space-y-1">
                        <?php foreach ($corrections as $msg): ?>
                            <li><?php echo $msg; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Erreurs -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <h3 class="text-red-800 font-semibold mb-2">❌ Erreurs</h3>
                    <ul class="text-red-700 text-sm space-y-1">
                        <?php foreach ($errors as $msg): ?>
                            <li><?php echo $msg; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="flex gap-4 mt-6">
                <a href="caissiere_dashboard.php" class="flex-1 bg-green-500 text-white py-3 rounded-lg hover:bg-green-600 text-center font-semibold">
                    <i class="fas fa-cash-register mr-2"></i>Tester le Dashboard
                </a>
                <button onclick="location.reload()" class="flex-1 bg-blue-500 text-white py-3 rounded-lg hover:bg-blue-600 font-semibold">
                    <i class="fas fa-sync mr-2"></i>Re-diagnostiquer
                </button>
            </div>
        </div>
    </div>
</body>
</html>

