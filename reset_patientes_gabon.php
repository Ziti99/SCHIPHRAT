<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Patientes - Données Gabonaises</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-cyan-50 min-h-screen p-8">
    <div class="max-w-6xl mx-auto">
        <div class="bg-white rounded-lg shadow-2xl p-8">
            <div class="flex items-center mb-6">
                <i class="fas fa-users-medical text-purple-600 text-3xl mr-4"></i>
                <h1 class="text-3xl font-bold text-gray-800">Reset Patientes - Données Gabonaises</h1>
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
            $patientes = [];

            try {
                // Connexion à la base de données
                $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $success[] = "✓ Connexion réussie à la base de données";
                
                // 1. Supprimer toutes les patientes existantes
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM patientes");
                $oldCount = $stmt->fetch()['count'];
                
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                $pdo->exec("TRUNCATE TABLE patientes");
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                
                $success[] = "✓ {$oldCount} patiente(s) supprimée(s)";
                
                // 2. Données gabonaises réalistes
                $nomsGabonais = [
                    ['nom' => 'AKENDENGUE', 'prenom' => 'Marie-Claire'],
                    ['nom' => 'OBAME', 'prenom' => 'Sylvie'],
                    ['nom' => 'MBADINGA', 'prenom' => 'Georgette'],
                    ['nom' => 'NDONG', 'prenom' => 'Fabiola'],
                    ['nom' => 'ONDO', 'prenom' => 'Rachel'],
                    ['nom' => 'MBOUMBA', 'prenom' => 'Josiane'],
                    ['nom' => 'NZOGHE', 'prenom' => 'Véronique'],
                    ['nom' => 'KOMBILA', 'prenom' => 'Paulette'],
                    ['nom' => 'NGUEMA', 'prenom' => 'Isabelle'],
                    ['nom' => 'OYANE', 'prenom' => 'Florence'],
                    ['nom' => 'MBOUMBOU', 'prenom' => 'Sandrine'],
                    ['nom' => 'KOUMBA', 'prenom' => 'Christelle'],
                    ['nom' => 'ANGUE', 'prenom' => 'Lydiane'],
                    ['nom' => 'MOUSSAVOU', 'prenom' => 'Laurence'],
                    ['nom' => 'EYEGHE', 'prenom' => 'Nadège'],
                    ['nom' => 'PAMBOU', 'prenom' => 'Yvonne'],
                    ['nom' => 'ELLA', 'prenom' => 'Martine'],
                    ['nom' => 'NTOUTOUME', 'prenom' => 'Denise'],
                    ['nom' => 'MAKAYA', 'prenom' => 'Patricia'],
                    ['nom' => 'BOUEYA', 'prenom' => 'Emmanuelle']
                ];
                
                $quartiers = [
                    'Libreville - Nombakélé',
                    'Libreville - Lalala',
                    'Libreville - Glass',
                    'Libreville - Akébé',
                    'Libreville - Oloumi',
                    'Port-Gentil - Quartier Aviation',
                    'Franceville - Potos',
                    'Oyem - Centre-ville',
                    'Libreville - Nzeng Ayong',
                    'Libreville - PK8',
                    'Port-Gentil - Quartier Cite',
                    'Libreville - Bambouchine',
                    'Libreville - Mont-Bouët',
                    'Libreville - Batterie IV',
                    'Libreville - Soweto'
                ];
                
                $groupesSanguins = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                
                // 3. Insérer les 20 nouvelles patientes
                $insertStmt = $pdo->prepare("
                    INSERT INTO patientes 
                    (nom, prenom, date_naissance, adresse, telephone, groupe_sanguin, nombre_grossesses, nombre_fausses_couches, antecedents_medicaux, allergies) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                foreach ($nomsGabonais as $index => $personne) {
                    // Générer des données réalistes
                    $age = rand(18, 42);
                    $anneeNaissance = date('Y') - $age;
                    $dateNaissance = "$anneeNaissance-" . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . "-" . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
                    
                    $adresse = $quartiers[array_rand($quartiers)];
                    $telephone = '+241 0' . rand(1, 7) . ' ' . rand(10, 99) . ' ' . rand(10, 99) . ' ' . rand(10, 99);
                    $groupeSanguin = $groupesSanguins[array_rand($groupesSanguins)];
                    
                    $nombreGrossesses = rand(0, 5);
                    $nombreFaussesCouches = $nombreGrossesses > 0 ? rand(0, min(2, $nombreGrossesses)) : 0;
                    
                    // Antécédents médicaux variés
                    $antecedents = [
                        'Aucun antécédent particulier',
                        'Hypertension artérielle légère',
                        'Diabète gestationnel lors de grossesse précédente',
                        'Césarienne précédente',
                        'Paludisme traité',
                        'Anémie ferriprive',
                        'RAS - Rien à signaler',
                        'Asthme léger',
                        'Fibrome utérin',
                        'Allergie à la pénicilline'
                    ];
                    
                    $allergies_list = [
                        'Aucune allergie connue',
                        'Pénicilline',
                        'Iode',
                        'Pollen',
                        'Aucune',
                        'Fruits de mer',
                        'RAS'
                    ];
                    
                    $antecedent = $antecedents[array_rand($antecedents)];
                    $allergie = $allergies_list[array_rand($allergies_list)];
                    
                    $insertStmt->execute([
                        $personne['nom'],
                        $personne['prenom'],
                        $dateNaissance,
                        $adresse,
                        $telephone,
                        $groupeSanguin,
                        $nombreGrossesses,
                        $nombreFaussesCouches,
                        $antecedent,
                        $allergie
                    ]);
                    
                    $success[] = "✓ Patiente " . ($index + 1) . " créée : {$personne['prenom']} {$personne['nom']}";
                }
                
                $success[] = "✓ 20 nouvelles patientes gabonaises créées avec succès !";
                
                // 4. Récupérer toutes les patientes pour affichage
                $stmt = $pdo->query("
                    SELECT id, nom, prenom, date_naissance, age, adresse, telephone, 
                           groupe_sanguin, nombre_grossesses, nombre_fausses_couches 
                    FROM patientes 
                    ORDER BY nom, prenom
                ");
                $patientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            } catch (PDOException $e) {
                $errors[] = "Erreur : " . $e->getMessage();
            }
            ?>

            <!-- Messages de succès -->
            <?php if (!empty($success)): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6 max-h-96 overflow-y-auto">
                    <h3 class="text-green-800 font-semibold mb-2 flex items-center sticky top-0 bg-green-50">
                        <i class="fas fa-check-circle mr-2"></i> Opérations réussies
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

            <!-- Liste des patientes -->
            <?php if (!empty($patientes)): ?>
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-list mr-2"></i> 
                        Liste des patientes (<?php echo count($patientes); ?> au total)
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white border border-gray-300 rounded-lg text-sm">
                            <thead class="bg-gradient-to-r from-purple-500 to-pink-500 text-white">
                                <tr>
                                    <th class="px-3 py-2 text-left">ID</th>
                                    <th class="px-3 py-2 text-left">Nom</th>
                                    <th class="px-3 py-2 text-left">Prénom</th>
                                    <th class="px-3 py-2 text-left">Âge</th>
                                    <th class="px-3 py-2 text-left">Date Naissance</th>
                                    <th class="px-3 py-2 text-left">Téléphone</th>
                                    <th class="px-3 py-2 text-left">Groupe Sang.</th>
                                    <th class="px-3 py-2 text-left">Adresse</th>
                                    <th class="px-3 py-2 text-center">Grossesses</th>
                                    <th class="px-3 py-2 text-center">F.C.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patientes as $i => $p): ?>
                                    <tr class="border-t border-gray-200 hover:bg-purple-50 transition-colors">
                                        <td class="px-3 py-2 font-semibold text-purple-600"><?php echo $p['id']; ?></td>
                                        <td class="px-3 py-2 font-semibold"><?php echo htmlspecialchars($p['nom']); ?></td>
                                        <td class="px-3 py-2"><?php echo htmlspecialchars($p['prenom']); ?></td>
                                        <td class="px-3 py-2"><?php echo $p['age']; ?> ans</td>
                                        <td class="px-3 py-2 text-gray-600"><?php echo date('d/m/Y', strtotime($p['date_naissance'])); ?></td>
                                        <td class="px-3 py-2 font-mono text-xs"><?php echo htmlspecialchars($p['telephone']); ?></td>
                                        <td class="px-3 py-2">
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                                <?php echo $p['groupe_sanguin']; ?>
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-gray-600 text-xs"><?php echo htmlspecialchars($p['adresse']); ?></td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">
                                                <?php echo $p['nombre_grossesses']; ?>
                                            </span>
                                        </td>
                                        <td class="px-3 py-2 text-center text-gray-600"><?php echo $p['nombre_fausses_couches']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="flex gap-4 mt-8">
                <a href="login.php" class="flex-1 bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-3 rounded-lg hover:shadow-lg transition-all text-center">
                    <i class="fas fa-sign-in-alt mr-2"></i> Aller à la connexion
                </a>
                <a href="patientes.php" class="flex-1 bg-gradient-to-r from-blue-500 to-cyan-500 text-white px-6 py-3 rounded-lg hover:shadow-lg transition-all text-center">
                    <i class="fas fa-users mr-2"></i> Voir les patientes
                </a>
                <button onclick="location.reload()" class="flex-1 bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors">
                    <i class="fas fa-sync-alt mr-2"></i> Rafraîchir
                </button>
            </div>

            <!-- Note importante -->
            <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h4 class="text-yellow-800 font-semibold mb-2 flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i> Note importante
                </h4>
                <p class="text-yellow-700 text-sm">
                    Ce script a supprimé toutes les anciennes données des patientes et les a remplacées par 20 nouvelles patientes avec des noms gabonais. 
                    Toutes les données associées (grossesses, consultations, accouchements) ont également été supprimées.
                </p>
                <p class="text-yellow-700 text-sm mt-2">
                    <strong>Pour des raisons de sécurité, supprimez ce fichier après utilisation :</strong>
                    <code class="bg-yellow-100 px-2 py-1 rounded">rm reset_patientes_gabon.php</code>
                </p>
            </div>
        </div>
    </div>
</body>
</html>

