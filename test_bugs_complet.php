<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test Bugs Complet - SHIPHRAT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-6xl mx-auto">
        <div class="bg-white rounded-lg shadow-2xl p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">
                <i class="fas fa-bug text-red-600 mr-3"></i>
                Test Complet des Bugs - Clinique SHIPHRAT
            </h1>

            <?php
            $host = 'metro.proxy.rlwy.net';
            $port = '29698';
            $dbname = 'railway';
            $username = 'root';
            $password = 'UJxUfmCzEGIdbYPVwFXKUbAQoFzmByrI';

            $tests_passed = [];
            $tests_failed = [];
            $warnings = [];

            try {
                $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                echo '<div class="mb-6"><h2 class="text-2xl font-bold text-green-600 mb-3">✓ Connexion Base de Données</h2></div>';
                
                // TEST 1: Vérifier toutes les tables nécessaires
                echo '<div class="mb-6"><h2 class="text-xl font-bold mb-3">📋 Test 1: Vérification Tables</h2>';
                $required_tables = [
                    'users', 'patientes', 'consultations_prenatales', 'actes_poses', 
                    'consultation_actes', 'paiements', 'historique_paiements', 'examens'
                ];
                
                foreach ($required_tables as $table) {
                    $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                    if ($stmt->rowCount() > 0) {
                        $tests_passed[] = "✓ Table '$table' existe";
                    } else {
                        $tests_failed[] = "❌ Table '$table' manquante";
                    }
                }
                echo '</div>';
                
                // TEST 2: Vérifier colonnes critiques
                echo '<div class="mb-6"><h2 class="text-xl font-bold mb-3">🔍 Test 2: Colonnes Critiques</h2>';
                
                // Table users - colonne password
                $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'password'");
                if ($stmt->rowCount() > 0) {
                    $tests_passed[] = "✓ users.password existe";
                } else {
                    $tests_failed[] = "❌ users.password manquante";
                }
                
                // Table consultations_prenatales - colonnes essentielles
                $cols_consultation = ['patiente_id', 'medecin_id', 'sagefemme_id'];
                foreach ($cols_consultation as $col) {
                    $stmt = $pdo->query("SHOW COLUMNS FROM consultations_prenatales LIKE '$col'");
                    if ($stmt->rowCount() > 0) {
                        $tests_passed[] = "✓ consultations_prenatales.$col existe";
                    } else {
                        $tests_failed[] = "❌ consultations_prenatales.$col manquante";
                    }
                }
                
                // Table paiements - colonnes essentielles
                $cols_paiements = ['consultation_id', 'patiente_id', 'montant_total', 'montant_paye', 'montant_restant', 'statut'];
                foreach ($cols_paiements as $col) {
                    $stmt = $pdo->query("SHOW COLUMNS FROM paiements LIKE '$col'");
                    if ($stmt->rowCount() > 0) {
                        $tests_passed[] = "✓ paiements.$col existe";
                    } else {
                        $tests_failed[] = "❌ paiements.$col manquante";
                    }
                }
                echo '</div>';
                
                // TEST 3: Vérifier les utilisateurs
                echo '<div class="mb-6"><h2 class="text-xl font-bold mb-3">👥 Test 3: Utilisateurs</h2>';
                $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
                $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($roles as $role) {
                    $tests_passed[] = "✓ {$role['count']} utilisateur(s) avec rôle '{$role['role']}'";
                }
                
                // Vérifier si caissiere existe
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'caissiere'");
                $result = $stmt->fetch();
                if ($result['count'] > 0) {
                    $tests_passed[] = "✓ Compte(s) caissière configuré(s)";
                } else {
                    $warnings[] = "⚠️ Aucun compte caissière - Exécuter setup_caisse_system.php";
                }
                echo '</div>';
                
                // TEST 4: Test des requêtes critiques
                echo '<div class="mb-6"><h2 class="text-xl font-bold mb-3">🔬 Test 4: Requêtes SQL</h2>';
                
                // Test query paiements avec joins
                try {
                    $stmt = $pdo->query("
                        SELECT p.id, pat.nom, pat.prenom
                        FROM paiements p
                        INNER JOIN patientes pat ON p.patiente_id = pat.id
                        LIMIT 1
                    ");
                    $tests_passed[] = "✓ Requête paiements + patientes fonctionne";
                } catch (PDOException $e) {
                    $tests_failed[] = "❌ Requête paiements échoue: " . $e->getMessage();
                }
                
                // Test query consultation_actes
                try {
                    $stmt = $pdo->query("
                        SELECT ca.*, ap.nom_acte
                        FROM consultation_actes ca
                        INNER JOIN actes_poses ap ON ca.acte_id = ap.id
                        LIMIT 1
                    ");
                    $tests_passed[] = "✓ Requête consultation_actes + actes_poses fonctionne";
                } catch (PDOException $e) {
                    $tests_failed[] = "❌ Requête consultation_actes échoue: " . $e->getMessage();
                }
                
                // Test query historique_paiements
                try {
                    $stmt = $pdo->query("
                        SELECT hp.*
                        FROM historique_paiements hp
                        WHERE hp.paiement_id = 1
                        LIMIT 1
                    ");
                    $tests_passed[] = "✓ Requête historique_paiements fonctionne (colonne paiement_id OK)";
                } catch (PDOException $e) {
                    $tests_failed[] = "❌ Requête historique_paiements échoue: " . $e->getMessage();
                }
                echo '</div>';
                
                // TEST 5: Vérifier les fichiers critiques
                echo '<div class="mb-6"><h2 class="text-xl font-bold mb-3">📁 Test 5: Fichiers Système</h2>';
                $critical_files = [
                    '/config/database.php' => 'Configuration base de données',
                    '/includes/navbar.php' => 'Navigation',
                    '/includes/sidebar.php' => 'Menu latéral',
                    '/includes/helpers.php' => 'Fonctions helper',
                    '/assets/css/mobile-improvements.css' => 'CSS Mobile',
                    '/assets/js/mobile-responsive.js' => 'JS Mobile'
                ];
                
                foreach ($critical_files as $file => $description) {
                    if (file_exists(__DIR__ . $file)) {
                        $tests_passed[] = "✓ $description ($file)";
                    } else {
                        $tests_failed[] = "❌ $description manquant ($file)";
                    }
                }
                echo '</div>';
                
                // TEST 6: Test intégrité des données
                echo '<div class="mb-6"><h2 class="text-xl font-bold mb-3">🔗 Test 6: Intégrité Données</h2>';
                
                // Vérifier consultations orphelines
                $stmt = $pdo->query("
                    SELECT COUNT(*) as count 
                    FROM consultations_prenatales cp
                    LEFT JOIN patientes pat ON cp.patiente_id = pat.id
                    WHERE pat.id IS NULL
                ");
                $orphan_consultations = $stmt->fetch()['count'];
                if ($orphan_consultations > 0) {
                    $warnings[] = "⚠️ $orphan_consultations consultation(s) sans patiente";
                } else {
                    $tests_passed[] = "✓ Aucune consultation orpheline";
                }
                
                // Vérifier paiements orphelins
                $stmt = $pdo->query("
                    SELECT COUNT(*) as count 
                    FROM paiements p
                    LEFT JOIN consultations_prenatales cp ON p.consultation_id = cp.id
                    WHERE cp.id IS NULL
                ");
                $orphan_paiements = $stmt->fetch()['count'];
                if ($orphan_paiements > 0) {
                    $warnings[] = "⚠️ $orphan_paiements paiement(s) sans consultation";
                } else {
                    $tests_passed[] = "✓ Aucun paiement orphelin";
                }
                echo '</div>';
                
                // TEST 7: Cohérence des montants
                echo '<div class="mb-6"><h2 class="text-xl font-bold mb-3">💰 Test 7: Cohérence Montants</h2>';
                $stmt = $pdo->query("
                    SELECT COUNT(*) as count
                    FROM paiements
                    WHERE montant_paye > montant_total
                ");
                $incoherent_montants = $stmt->fetch()['count'];
                if ($incoherent_montants > 0) {
                    $tests_failed[] = "❌ $incoherent_montants paiement(s) avec montant payé > montant total";
                } else {
                    $tests_passed[] = "✓ Cohérence des montants OK";
                }
                
                // Vérifier que montant_restant = montant_total - montant_paye
                $stmt = $pdo->query("
                    SELECT COUNT(*) as count
                    FROM paiements
                    WHERE ABS(montant_restant - (montant_total - montant_paye)) > 0.01
                ");
                $wrong_restant = $stmt->fetch()['count'];
                if ($wrong_restant > 0) {
                    $tests_failed[] = "❌ $wrong_restant paiement(s) avec montant_restant incohérent";
                } else {
                    $tests_passed[] = "✓ Calcul montant_restant correct";
                }
                echo '</div>';
                
            } catch (PDOException $e) {
                $tests_failed[] = "❌ Erreur critique: " . $e->getMessage();
            }
            
            // Calculer le score
            $total_tests = count($tests_passed) + count($tests_failed);
            $score = $total_tests > 0 ? (count($tests_passed) / $total_tests) * 100 : 0;
            ?>

            <!-- Score global -->
            <div class="mb-6 p-6 rounded-lg <?php echo $score >= 80 ? 'bg-green-50 border-2 border-green-300' : 'bg-red-50 border-2 border-red-300'; ?>">
                <div class="text-center">
                    <p class="text-sm text-gray-600 mb-2">Score de Santé du Système</p>
                    <p class="text-6xl font-bold <?php echo $score >= 80 ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo round($score); ?>%
                    </p>
                    <p class="text-sm text-gray-600 mt-2">
                        <?php echo count($tests_passed); ?> / <?php echo $total_tests; ?> tests réussis
                    </p>
                </div>
            </div>

            <!-- Tests réussis -->
            <?php if (!empty($tests_passed)): ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                    <h3 class="text-green-800 font-semibold mb-2 flex items-center">
                        <i class="fas fa-check-circle mr-2"></i> Tests Réussis (<?php echo count($tests_passed); ?>)
                    </h3>
                    <ul class="text-green-700 text-sm space-y-1 max-h-96 overflow-y-auto">
                        <?php foreach ($tests_passed as $test): ?>
                            <li><?php echo $test; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Warnings -->
            <?php if (!empty($warnings)): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                    <h3 class="text-yellow-800 font-semibold mb-2 flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2"></i> Avertissements (<?php echo count($warnings); ?>)
                    </h3>
                    <ul class="text-yellow-700 text-sm space-y-1">
                        <?php foreach ($warnings as $warning): ?>
                            <li><?php echo $warning; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Tests échoués -->
            <?php if (!empty($tests_failed)): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <h3 class="text-red-800 font-semibold mb-2 flex items-center">
                        <i class="fas fa-times-circle mr-2"></i> Tests Échoués (<?php echo count($tests_failed); ?>)
                    </h3>
                    <ul class="text-red-700 text-sm space-y-1">
                        <?php foreach ($tests_failed as $test): ?>
                            <li><?php echo $test; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Actions -->
            <div class="grid md:grid-cols-3 gap-4">
                <?php if ($score < 100): ?>
                    <a href="setup_caisse_system.php" class="bg-green-500 text-white py-3 rounded-lg hover:bg-green-600 text-center font-semibold">
                        <i class="fas fa-wrench mr-2"></i>Installer Tables Manquantes
                    </a>
                    <a href="fix_consultations_table.php" class="bg-blue-500 text-white py-3 rounded-lg hover:bg-blue-600 text-center font-semibold">
                        <i class="fas fa-tools mr-2"></i>Corriger Consultations
                    </a>
                <?php endif; ?>
                <button onclick="location.reload()" class="bg-gray-500 text-white py-3 rounded-lg hover:bg-gray-600 font-semibold">
                    <i class="fas fa-sync mr-2"></i>Re-tester
                </button>
            </div>

            <!-- Recommandations -->
            <?php if ($score >= 80): ?>
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-6">
                    <h3 class="text-blue-900 font-semibold mb-3">🎯 Système Opérationnel</h3>
                    <p class="text-blue-800 text-sm mb-3">Votre système fonctionne correctement. Vous pouvez :</p>
                    <div class="grid md:grid-cols-2 gap-3 text-sm">
                        <a href="login.php" class="bg-blue-500 text-white py-2 px-4 rounded text-center hover:bg-blue-600">
                            <i class="fas fa-sign-in-alt mr-2"></i>Se connecter
                        </a>
                        <a href="caissiere_dashboard.php" class="bg-green-500 text-white py-2 px-4 rounded text-center hover:bg-green-600">
                            <i class="fas fa-cash-register mr-2"></i>Dashboard Caisse
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

