<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des Utilisateurs - SHIPHRAT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-6xl mx-auto">
        <div class="bg-white rounded-lg shadow-2xl p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">
                <i class="fas fa-users text-purple-600 mr-3"></i>
                Liste Complète des Utilisateurs
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
                
                // Récupérer TOUS les utilisateurs
                $stmt = $pdo->query("SELECT * FROM users ORDER BY role, nom, prenom");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $success[] = "✓ " . count($users) . " utilisateur(s) trouvé(s)";
                
                // Compter par rôle
                $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
                $roles_count = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                $errors[] = "Erreur : " . $e->getMessage();
            }
            ?>

            <!-- Messages -->
            <?php if (!empty($success)): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                    <ul class="text-green-700 text-sm space-y-1">
                        <?php foreach ($success as $msg): ?>
                            <li><?php echo $msg; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Statistiques par rôle -->
            <?php if (isset($roles_count)): ?>
                <div class="grid md:grid-cols-5 gap-4 mb-6">
                    <?php foreach ($roles_count as $role): ?>
                        <?php
                        $colors = [
                            'admin' => 'from-purple-500 to-purple-600',
                            'medecin' => 'from-blue-500 to-blue-600',
                            'sage_femme' => 'from-green-500 to-green-600',
                            'sagefemme' => 'from-green-500 to-green-600',
                            'secretaire' => 'from-yellow-500 to-yellow-600',
                            'caissiere' => 'from-pink-500 to-pink-600'
                        ];
                        $color = $colors[$role['role']] ?? 'from-gray-500 to-gray-600';
                        ?>
                        <div class="bg-gradient-to-r <?php echo $color; ?> text-white rounded-lg p-4 text-center">
                            <p class="text-3xl font-bold"><?php echo $role['count']; ?></p>
                            <p class="text-sm capitalize"><?php echo str_replace('_', ' ', $role['role']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Table des utilisateurs -->
            <?php if (isset($users) && !empty($users)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border rounded-lg">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Username</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Nom</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Prénom</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Rôle</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Téléphone</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Actif</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700">Mot de passe</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                                <?php
                                $role_colors = [
                                    'admin' => 'bg-purple-100 text-purple-800',
                                    'medecin' => 'bg-blue-100 text-blue-800',
                                    'sage_femme' => 'bg-green-100 text-green-800',
                                    'sagefemme' => 'bg-green-100 text-green-800',
                                    'secretaire' => 'bg-yellow-100 text-yellow-800',
                                    'caissiere' => 'bg-pink-100 text-pink-800'
                                ];
                                $role_color = $role_colors[$user['role']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-semibold"><?php echo $user['id']; ?></td>
                                    <td class="px-4 py-3 text-sm font-mono"><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($user['email'] ?? '-'); ?></td>
                                    <td class="px-4 py-3 text-sm font-semibold"><?php echo htmlspecialchars($user['nom']); ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($user['prenom']); ?></td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-1 text-xs rounded-full <?php echo $role_color; ?>">
                                            <?php echo str_replace('_', ' ', $user['role']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($user['telephone'] ?? '-'); ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <?php if ($user['is_active']): ?>
                                            <span class="text-green-600"><i class="fas fa-check-circle"></i></span>
                                        <?php else: ?>
                                            <span class="text-red-600"><i class="fas fa-times-circle"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm">
                                        <?php 
                                        $has_password = !empty($user['password_hash']) || !empty($user['password']);
                                        if ($has_password): ?>
                                            <span class="text-green-600"><i class="fas fa-check"></i> OK</span>
                                        <?php else: ?>
                                            <span class="text-red-600"><i class="fas fa-times"></i> Manquant</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Analyse des rôles -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-6">
                <h3 class="text-blue-900 font-semibold mb-3">📊 Analyse des Rôles</h3>
                <?php if (isset($roles_count)): ?>
                    <div class="text-sm text-blue-800 space-y-2">
                        <?php foreach ($roles_count as $role): ?>
                            <div class="flex justify-between items-center">
                                <span class="capitalize font-semibold"><?php echo str_replace('_', ' ', $role['role']); ?>:</span>
                                <span class="text-lg font-bold"><?php echo $role['count']; ?> utilisateur(s)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-blue-200">
                        <p class="text-sm text-blue-700">
                            <strong>Note importante :</strong> Le système recherche les sages-femmes avec les rôles suivants :
                            <code class="bg-blue-100 px-2 py-1 rounded">sage_femme</code> et <code class="bg-blue-100 px-2 py-1 rounded">sagefemme</code>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-6 flex gap-4">
                <a href="utilisateurs.php" class="flex-1 bg-blue-500 text-white py-3 rounded-lg hover:bg-blue-600 text-center font-semibold">
                    <i class="fas fa-users-cog mr-2"></i>Gérer les Utilisateurs
                </a>
                <a href="consultations/ajouter.php" class="flex-1 bg-green-500 text-white py-3 rounded-lg hover:bg-green-600 text-center font-semibold">
                    <i class="fas fa-plus mr-2"></i>Tester Nouvelle Consultation
                </a>
            </div>
        </div>
    </div>
</body>
</html>

