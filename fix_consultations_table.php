<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Correction Table Consultations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-2xl p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">
                <i class="fas fa-wrench text-blue-600 mr-3"></i>
                Correction Table Consultations
            </h1>

            <?php
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
                
                $success[] = "✓ Connexion réussie";
                
                // Vérifier structure actuelle
                echo '<div class="mb-6"><h2 class="text-xl font-bold mb-3">Structure AVANT correction</h2>';
                $stmt = $pdo->query("DESCRIBE consultations_prenatales");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<table class="min-w-full border text-sm mb-4"><thead class="bg-gray-100"><tr>
                    <th class="border px-3 py-2">Colonne</th><th class="border px-3 py-2">Type</th></tr></thead><tbody>';
                
                $has_sagefemme_id = false;
                $has_patiente_id = false;
                
                foreach ($columns as $col) {
                    echo "<tr><td class='border px-3 py-2 font-mono'>{$col['Field']}</td><td class='border px-3 py-2'>{$col['Type']}</td></tr>";
                    if ($col['Field'] === 'sagefemme_id') $has_sagefemme_id = true;
                    if ($col['Field'] === 'patiente_id') $has_patiente_id = true;
                }
                echo '</tbody></table></div>';
                
                // Ajouter la colonne sagefemme_id si elle n'existe pas
                if (!$has_sagefemme_id) {
                    $success[] = "⚠️ Colonne 'sagefemme_id' manquante - Ajout en cours...";
                    $pdo->exec("ALTER TABLE consultations_prenatales ADD COLUMN sagefemme_id INT NULL AFTER medecin_id");
                    $pdo->exec("ALTER TABLE consultations_prenatales ADD FOREIGN KEY (sagefemme_id) REFERENCES users(id)");
                    $success[] = "✓ Colonne 'sagefemme_id' ajoutée avec succès";
                } else {
                    $success[] = "✓ Colonne 'sagefemme_id' existe déjà";
                }
                
                // Ajouter la colonne patiente_id si elle n'existe pas
                if (!$has_patiente_id) {
                    $success[] = "⚠️ Colonne 'patiente_id' manquante - Ajout en cours...";
                    $pdo->exec("ALTER TABLE consultations_prenatales ADD COLUMN patiente_id INT NOT NULL AFTER id");
                    $pdo->exec("ALTER TABLE consultations_prenatales ADD FOREIGN KEY (patiente_id) REFERENCES patientes(id) ON DELETE CASCADE");
                    $success[] = "✓ Colonne 'patiente_id' ajoutée avec succès";
                } else {
                    $success[] = "✓ Colonne 'patiente_id' existe déjà";
                }
                
                // Afficher structure finale
                echo '<div class="mb-6"><h2 class="text-xl font-bold mb-3 text-green-600">Structure APRÈS correction</h2>';
                $stmt = $pdo->query("DESCRIBE consultations_prenatales");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<table class="min-w-full border text-sm"><thead class="bg-green-100"><tr>
                    <th class="border px-3 py-2">Colonne</th><th class="border px-3 py-2">Type</th><th class="border px-3 py-2">Null</th></tr></thead><tbody>';
                foreach ($columns as $col) {
                    $isNew = ($col['Field'] === 'sagefemme_id' || $col['Field'] === 'patiente_id');
                    $rowClass = $isNew ? 'bg-green-50' : '';
                    echo "<tr class='$rowClass'><td class='border px-3 py-2 font-mono font-semibold'>{$col['Field']}</td><td class='border px-3 py-2'>{$col['Type']}</td><td class='border px-3 py-2'>{$col['Null']}</td></tr>";
                }
                echo '</tbody></table></div>';
                
            } catch (PDOException $e) {
                $errors[] = "Erreur : " . $e->getMessage();
            }
            ?>

            <!-- Messages succès -->
            <?php if (!empty($success)): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                    <h3 class="text-green-800 font-semibold mb-2">✅ Opérations effectuées</h3>
                    <ul class="text-green-700 text-sm space-y-1">
                        <?php foreach ($success as $msg): ?>
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
                <a href="consultations/ajouter.php" class="flex-1 bg-blue-500 text-white py-3 rounded-lg hover:bg-blue-600 text-center font-semibold">
                    <i class="fas fa-plus mr-2"></i>Tester Nouvelle Consultation
                </a>
                <a href="caissiere_dashboard.php" class="flex-1 bg-green-500 text-white py-3 rounded-lg hover:bg-green-600 text-center font-semibold">
                    <i class="fas fa-cash-register mr-2"></i>Dashboard Caissière
                </a>
            </div>
        </div>
    </div>
</body>
</html>

