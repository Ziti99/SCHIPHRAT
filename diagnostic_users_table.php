<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Diagnostic Table Users</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-2xl p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">
                <i class="fas fa-user-cog text-blue-600 mr-3"></i>
                Diagnostic et Correction Table Users
            </h1>

            <?php
            $host = 'metro.proxy.rlwy.net';
            $port = '29698';
            $dbname = 'railway';
            $username = 'root';
            $password = 'UJxUfmCzEGIdbYPVwFXKUbAQoFzmByrI';

            $success = [];
            $errors = [];

            try {
                $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $success[] = "✓ Connexion réussie";
                
                // Afficher structure actuelle
                echo '<div class="mb-6"><h2 class="text-xl font-bold mb-3">Structure Table USERS</h2>';
                $stmt = $pdo->query("DESCRIBE users");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<table class="min-w-full border text-sm"><thead class="bg-gray-100"><tr>
                    <th class="border px-3 py-2">Colonne</th>
                    <th class="border px-3 py-2">Type</th>
                    <th class="border px-3 py-2">Null</th>
                    <th class="border px-3 py-2">Default</th>
                    <th class="border px-3 py-2">Extra</th>
                </tr></thead><tbody>';
                
                $has_password = false;
                $has_password_hash = false;
                
                foreach ($columns as $col) {
                    $highlight = '';
                    if ($col['Field'] === 'password') {
                        $has_password = true;
                        $highlight = 'bg-yellow-100';
                    }
                    if ($col['Field'] === 'password_hash') {
                        $has_password_hash = true;
                        $highlight = 'bg-green-100';
                    }
                    
                    echo "<tr class='$highlight'>
                        <td class='border px-3 py-2 font-mono font-semibold'>{$col['Field']}</td>
                        <td class='border px-3 py-2'>{$col['Type']}</td>
                        <td class='border px-3 py-2'>{$col['Null']}</td>
                        <td class='border px-3 py-2'>" . ($col['Default'] ?? 'NULL') . "</td>
                        <td class='border px-3 py-2'>{$col['Extra']}</td>
                    </tr>";
                }
                echo '</tbody></table></div>';
                
                // Diagnostic et correction
                if ($has_password && $has_password_hash) {
                    $success[] = "⚠️ PROBLÈME : Deux colonnes password ET password_hash existent";
                    $success[] = "→ Suppression de la colonne 'password' (ancienne)...";
                    
                    // Copier données de password vers password_hash si nécessaire
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE password_hash IS NULL AND password IS NOT NULL");
                    $need_copy = $stmt->fetch()['count'];
                    
                    if ($need_copy > 0) {
                        $pdo->exec("UPDATE users SET password_hash = password WHERE password_hash IS NULL AND password IS NOT NULL");
                        $success[] = "✓ $need_copy utilisateur(s) migré(s) vers password_hash";
                    }
                    
                    // Supprimer la colonne password
                    $pdo->exec("ALTER TABLE users DROP COLUMN password");
                    $success[] = "✓ Colonne 'password' supprimée";
                    
                } elseif ($has_password && !$has_password_hash) {
                    $success[] = "→ Renommage de 'password' en 'password_hash'...";
                    $pdo->exec("ALTER TABLE users CHANGE COLUMN password password_hash VARCHAR(255) NOT NULL");
                    $success[] = "✓ Colonne renommée avec succès";
                    
                } elseif (!$has_password && $has_password_hash) {
                    $success[] = "✓ Structure correcte : colonne 'password_hash' présente";
                    
                } else {
                    $errors[] = "❌ Aucune colonne de mot de passe trouvée !";
                }
                
                // Afficher structure finale
                echo '<div class="mb-6"><h2 class="text-xl font-bold mb-3 text-green-600">Structure APRÈS correction</h2>';
                $stmt = $pdo->query("DESCRIBE users");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo '<table class="min-w-full border text-sm"><thead class="bg-green-100"><tr>
                    <th class="border px-3 py-2">Colonne</th>
                    <th class="border px-3 py-2">Type</th>
                    <th class="border px-3 py-2">Null</th>
                </tr></thead><tbody>';
                foreach ($columns as $col) {
                    echo "<tr><td class='border px-3 py-2 font-mono'>{$col['Field']}</td><td class='border px-3 py-2'>{$col['Type']}</td><td class='border px-3 py-2'>{$col['Null']}</td></tr>";
                }
                echo '</tbody></table></div>';
                
            } catch (PDOException $e) {
                $errors[] = "Erreur : " . $e->getMessage();
            }
            ?>

            <!-- Succès -->
            <?php if (!empty($success)): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                    <h3 class="text-green-800 font-semibold mb-2">✅ Opérations</h3>
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

            <div class="flex gap-4">
                <a href="utilisateurs.php" class="flex-1 bg-blue-500 text-white py-3 rounded-lg hover:bg-blue-600 text-center font-semibold">
                    <i class="fas fa-users mr-2"></i>Tester Création Utilisateur
                </a>
                <button onclick="location.reload()" class="flex-1 bg-gray-500 text-white py-3 rounded-lg hover:bg-gray-600 font-semibold">
                    <i class="fas fa-sync mr-2"></i>Re-diagnostiquer
                </button>
            </div>
        </div>
    </div>
</body>
</html>

