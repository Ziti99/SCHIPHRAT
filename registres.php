<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$db = new Database();

// Récupération des statistiques des registres
$stats = [
    'total_admissions' => $db->fetch("SELECT COUNT(*) as count FROM patientes")['count'],
    'total_accouchements' => $db->fetch("SELECT COUNT(*) as count FROM accouchements")['count'],
    'total_deces' => $db->fetch("SELECT COUNT(*) as count FROM deces")['count'],
    'ce_mois' => $db->fetch("SELECT COUNT(*) as count FROM accouchements WHERE MONTH(date_accouchement) = MONTH(CURDATE()) AND YEAR(date_accouchement) = YEAR(CURDATE())")['count']
];

// Récupération des derniers accouchements pour le registre
$recent_accouchements = $db->fetchAll("
    SELECT a.*, 
           p.nom, p.prenom, p.telephone,
           medecin.nom as medecin_nom, medecin.prenom as medecin_prenom,
           sage_femme.nom as sage_femme_nom, sage_femme.prenom as sage_femme_prenom
    FROM accouchements a
    JOIN patientes p ON a.patiente_id = p.id
    JOIN users medecin ON a.medecin_id = medecin.id
    LEFT JOIN users sage_femme ON a.sage_femme_id = sage_femme.id
    ORDER BY a.date_accouchement DESC
    LIMIT 10
");

// Récupération des décès néonataux (si la table existe)
$deces_neonataux = [];
try {
    $deces_neonataux = $db->fetchAll("
        SELECT dn.*, 
               p.nom, p.prenom,
               a.date_accouchement, a.nom_bebe
        FROM deces_neonataux dn
        JOIN accouchements a ON dn.accouchement_id = a.id
        JOIN patientes p ON a.patiente_id = p.id
        ORDER BY dn.date_deces DESC
        LIMIT 10
    ");
} catch (Exception $e) {
    // La table deces_neonataux n'existe pas, on utilise un tableau vide
    $deces_neonataux = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registres Numériques - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#8B5CF6',
                        secondary: '#EC4899',
                        accent: '#06B6D4'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-heartbeat text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                            Clinique Obstétrique
                        </h1>
                        <p class="text-xs text-gray-500">Registres numériques</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                        <p class="text-xs text-gray-500 capitalize"><?php echo str_replace('_', ' ', $_SESSION['role']); ?></p>
                    </div>
                    <a href="/logout.php" class="text-gray-600 hover:text-red-600 transition-colors">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <!-- Contenu principal -->
        <main class="flex-1 p-8">
            <!-- En-tête -->
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Registres numériques</h2>
                <p class="text-gray-600">Remplacement des anciens registres papier par des registres numériques sécurisés</p>
            </div>

            <!-- Statistiques des registres -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-r from-blue-500 to-cyan-500 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100">Registre des admissions</p>
                            <p class="text-3xl font-bold"><?php echo $stats['total_admissions']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user-plus text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100">Registre des accouchements</p>
                            <p class="text-3xl font-bold"><?php echo $stats['total_accouchements']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-baby text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-red-500 to-pink-500 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-red-100">Registre des décès</p>
                            <p class="text-3xl font-bold"><?php echo $stats['total_deces']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-cross text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100">Accouchements ce mois</p>
                            <p class="text-3xl font-bold"><?php echo $stats['ce_mois']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accès aux registres -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-list text-purple-500 mr-2"></i>
                        Registres Numériques
                    </h3>
                    <div class="space-y-3">
                        <a href="admissions.php" class="flex items-center justify-between p-4 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                            <div>
                                <p class="font-medium text-blue-800">Registre des Admissions</p>
                                <p class="text-sm text-blue-600">Consultations prénatales avec filtres</p>
                            </div>
                            <i class="fas fa-arrow-right text-blue-500"></i>
                        </a>
                        
                        <a href="accouchements.php" class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-colors">
                            <div>
                                <p class="font-medium text-green-800">Registre des Accouchements</p>
                                <p class="text-sm text-green-600">Accouchements avec filtres avancés</p>
                            </div>
                            <i class="fas fa-arrow-right text-green-500"></i>
                        </a>
                        
                        <a href="deces.php" class="flex items-center justify-between p-4 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors">
                            <div>
                                <p class="font-medium text-red-800">Registre des Décès</p>
                                <p class="text-sm text-red-600">Décès avec filtres avancés</p>
                            </div>
                            <i class="fas fa-arrow-right text-red-500"></i>
                        </a>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-file-excel text-green-500 mr-2"></i>
                        Export Excel
                    </h3>
                    <div class="space-y-3">
                        <a href="export_excel_accouchements.php" class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg hover:bg-green-100 transition-colors">
                            <div>
                                <p class="font-medium text-green-800">Registre des accouchements</p>
                                <p class="text-sm text-green-600">Export Excel complet</p>
                            </div>
                            <i class="fas fa-download text-green-500"></i>
                        </a>
                        
                        <a href="export_excel_admissions.php" class="flex items-center justify-between p-4 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors">
                            <div>
                                <p class="font-medium text-blue-800">Registre des admissions</p>
                                <p class="text-sm text-blue-600">Export Excel complet</p>
                            </div>
                            <i class="fas fa-download text-blue-500"></i>
                        </a>
                        
                        <a href="export_excel_deces.php" class="flex items-center justify-between p-4 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors">
                            <div>
                                <p class="font-medium text-gray-800">Registre des décès</p>
                                <p class="text-sm text-gray-600">Export Excel complet</p>
                            </div>
                            <i class="fas fa-download text-gray-500"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Registre des accouchements -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-baby text-green-500 mr-2"></i>
                        Registre des accouchements
                    </h3>
                    <a href="accouchements.php" class="text-purple-600 hover:text-purple-700 font-medium">
                        Voir tout <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mère</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mode</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bébé</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Médecin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($recent_accouchements as $accouchement): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <?php echo date('d/m/Y H:i', strtotime($accouchement['date_accouchement'])); ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($accouchement['prenom'] . ' ' . $accouchement['nom']); ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                            <?php echo $accouchement['mode_accouchement'] == 'cesarienne' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $accouchement['mode_accouchement'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <?php if ($accouchement['nom_bebe']): ?>
                                            <?php echo htmlspecialchars($accouchement['nom_bebe']); ?>
                                        <?php endif; ?>
                                        <?php if ($accouchement['poids_bebe']): ?>
                                            (<?php echo $accouchement['poids_bebe']; ?> kg)
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        Dr. <?php echo htmlspecialchars($accouchement['medecin_nom'] . ' ' . $accouchement['medecin_prenom']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Registre des décès néonataux -->
            <?php if (!empty($deces_neonataux)): ?>
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-cross text-red-500 mr-2"></i>
                        Registre des décès néonataux
                    </h3>
                    <a href="deces.php" class="text-purple-600 hover:text-purple-700 font-medium">
                        Voir tout <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date décès</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mère</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Âge décès</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cause</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <?php foreach ($deces_neonataux as $deces): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <?php echo date('d/m/Y H:i', strtotime($deces['date_deces'])); ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($deces['prenom'] . ' ' . $deces['nom']); ?>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <?php echo $deces['age_deces']; ?> heures
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <?php 
                                        $cause = $deces['cause_deces'];
                                        if (strlen($cause) > 50) {
                                            echo htmlspecialchars(substr($cause, 0, 50)) . '...';
                                        } else {
                                            echo htmlspecialchars($cause);
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html> 