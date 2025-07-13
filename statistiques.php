<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance();

// Statistiques générales
$stats = [
    'total_patientes' => $db->fetch("SELECT COUNT(*) as count FROM patientes")['count'],
    'total_accouchements' => $db->fetch("SELECT COUNT(*) as count FROM accouchements")['count'],
    'total_consultations' => $db->fetch("SELECT COUNT(*) as count FROM consultations_prenatales")['count'],
    'cesariennes' => $db->fetch("SELECT COUNT(*) as count FROM accouchements WHERE mode_accouchement = 'cesarienne'")['count'],
    'voie_basse' => $db->fetch("SELECT COUNT(*) as count FROM accouchements WHERE mode_accouchement = 'voie_basse'")['count'],
    'deces_neonataux' => $db->fetch("SELECT COUNT(*) as count FROM deces_neonataux")['count'],
    'visites_postnatal' => $db->fetch("SELECT COUNT(*) as count FROM suivi_postnatal")['count']
];

// Calcul des taux
$taux_cesariennes = $stats['total_accouchements'] > 0 ? round(($stats['cesariennes'] / $stats['total_accouchements']) * 100, 1) : 0;
$taux_mortalite = $stats['total_accouchements'] > 0 ? round(($stats['deces_neonataux'] / $stats['total_accouchements']) * 100, 2) : 0;

// Statistiques mensuelles (6 derniers mois)
$mois_stats = $db->fetchAll("
    SELECT 
        DATE_FORMAT(date_accouchement, '%Y-%m') as mois,
        COUNT(*) as accouchements,
        SUM(CASE WHEN mode_accouchement = 'cesarienne' THEN 1 ELSE 0 END) as cesariennes,
        SUM(CASE WHEN mode_accouchement = 'voie_basse' THEN 1 ELSE 0 END) as voie_basse
    FROM accouchements 
    WHERE date_accouchement >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(date_accouchement, '%Y-%m')
    ORDER BY mois DESC
");

// Consultations par mois
$consultations_mois = $db->fetchAll("
    SELECT 
        DATE_FORMAT(date_consultation, '%Y-%m') as mois,
        COUNT(*) as consultations
    FROM consultations_prenatales 
    WHERE date_consultation >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(date_consultation, '%Y-%m')
    ORDER BY mois DESC
");

// Répartition par âge des patientes
$repartition_age = $db->fetchAll("
    SELECT 
        CASE 
            WHEN age < 20 THEN 'Moins de 20 ans'
            WHEN age BETWEEN 20 AND 25 THEN '20-25 ans'
            WHEN age BETWEEN 26 AND 30 THEN '26-30 ans'
            WHEN age BETWEEN 31 AND 35 THEN '31-35 ans'
            WHEN age BETWEEN 36 AND 40 THEN '36-40 ans'
            ELSE 'Plus de 40 ans'
        END as tranche_age,
        COUNT(*) as nombre
    FROM patientes 
    GROUP BY 
        CASE 
            WHEN age < 20 THEN 'Moins de 20 ans'
            WHEN age BETWEEN 20 AND 25 THEN '20-25 ans'
            WHEN age BETWEEN 26 AND 30 THEN '26-30 ans'
            WHEN age BETWEEN 31 AND 35 THEN '31-35 ans'
            WHEN age BETWEEN 36 AND 40 THEN '36-40 ans'
            ELSE 'Plus de 40 ans'
        END
    ORDER BY 
        CASE tranche_age
            WHEN 'Moins de 20 ans' THEN 1
            WHEN '20-25 ans' THEN 2
            WHEN '26-30 ans' THEN 3
            WHEN '31-35 ans' THEN 4
            WHEN '36-40 ans' THEN 5
            ELSE 6
        END
");

// Top 5 des médecins par nombre d'accouchements
$top_medecins = $db->fetchAll("
    SELECT 
        u.nom, u.prenom, u.specialite,
        COUNT(a.id) as nombre_accouchements
    FROM users u
    LEFT JOIN accouchements a ON u.id = a.medecin_id
    WHERE u.role IN ('medecin', 'sage_femme') AND u.is_active = 1
    GROUP BY u.id, u.nom, u.prenom, u.specialite
    ORDER BY nombre_accouchements DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        <p class="text-xs text-gray-500">Statistiques</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                        <p class="text-xs text-gray-500 capitalize"><?php echo str_replace('_', ' ', $_SESSION['user_role']); ?></p>
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
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Statistiques & Rapports</h2>
                    <p class="text-gray-600">Analyse complète des données de la clinique</p>
                </div>
                <div class="flex space-x-4">
                    <a href="/statistiques/export-pdf.php" class="bg-red-500 text-white px-6 py-3 rounded-lg hover:bg-red-600 transition-colors">
                        <i class="fas fa-file-pdf mr-2"></i>
                        Export PDF
                    </a>
                    <a href="/statistiques/export-excel.php" class="bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 transition-colors">
                        <i class="fas fa-file-excel mr-2"></i>
                        Export Excel
                    </a>
                </div>
            </div>

            <!-- Statistiques principales -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100">Total patientes</p>
                            <p class="text-3xl font-bold"><?php echo $stats['total_patientes']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user-injured text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100">Total accouchements</p>
                            <p class="text-3xl font-bold"><?php echo $stats['total_accouchements']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-baby text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-orange-500 to-red-500 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-orange-100">Consultations</p>
                            <p class="text-3xl font-bold"><?php echo $stats['total_consultations']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-check text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Taux et indicateurs -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-chart-pie text-purple-500 mr-2"></i>
                        Répartition des accouchements
                    </h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Voie basse</span>
                            <div class="flex items-center space-x-2">
                                <div class="w-32 bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo $stats['total_accouchements'] > 0 ? ($stats['voie_basse'] / $stats['total_accouchements']) * 100 : 0; ?>%"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-900"><?php echo $stats['voie_basse']; ?></span>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Césariennes</span>
                            <div class="flex items-center space-x-2">
                                <div class="w-32 bg-gray-200 rounded-full h-2">
                                    <div class="bg-red-500 h-2 rounded-full" style="width: <?php echo $taux_cesariennes; ?>%"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-900"><?php echo $stats['cesariennes']; ?> (<?php echo $taux_cesariennes; ?>%)</span>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-medium text-gray-700">Décès néonataux</span>
                            <div class="flex items-center space-x-2">
                                <div class="w-32 bg-gray-200 rounded-full h-2">
                                    <div class="bg-gray-500 h-2 rounded-full" style="width: <?php echo $taux_mortalite; ?>%"></div>
                                </div>
                                <span class="text-sm font-medium text-gray-900"><?php echo $stats['deces_neonataux']; ?> (<?php echo $taux_mortalite; ?>%)</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-user-md text-blue-500 mr-2"></i>
                        Top 5 des médecins
                    </h3>
                    <div class="space-y-3">
                        <?php foreach ($top_medecins as $medecin): ?>
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900">
                                        Dr. <?php echo htmlspecialchars($medecin['nom'] . ' ' . $medecin['prenom']); ?>
                                    </p>
                                    <?php if ($medecin['specialite']): ?>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($medecin['specialite']); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold text-purple-600"><?php echo $medecin['nombre_accouchements']; ?></p>
                                    <p class="text-xs text-gray-500">accouchements</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Graphiques -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-chart-line text-green-500 mr-2"></i>
                        Accouchements par mois
                    </h3>
                    <canvas id="accouchementsChart" width="400" height="200"></canvas>
                </div>

                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-chart-bar text-blue-500 mr-2"></i>
                        Répartition par âge
                    </h3>
                    <canvas id="ageChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Tableau des statistiques mensuelles -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-table text-purple-500 mr-2"></i>
                    Statistiques mensuelles (6 derniers mois)
                </h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mois</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Accouchements</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Césariennes</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Voie basse</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Taux césariennes</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($mois_stats as $stat): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo date('F Y', strtotime($stat['mois'] . '-01')); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo $stat['accouchements']; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo $stat['cesariennes']; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo $stat['voie_basse']; ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo $stat['accouchements'] > 0 ? round(($stat['cesariennes'] / $stat['accouchements']) * 100, 1) : 0; ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Graphique des accouchements par mois
        const accouchementsCtx = document.getElementById('accouchementsChart').getContext('2d');
        new Chart(accouchementsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($stat) { return date('M Y', strtotime($stat['mois'] . '-01')); }, array_reverse($mois_stats))); ?>,
                datasets: [{
                    label: 'Accouchements',
                    data: <?php echo json_encode(array_map(function($stat) { return $stat['accouchements']; }, array_reverse($mois_stats))); ?>,
                    borderColor: '#8B5CF6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Graphique de répartition par âge
        const ageCtx = document.getElementById('ageChart').getContext('2d');
        new Chart(ageCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_map(function($stat) { return $stat['tranche_age']; }, $repartition_age)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_map(function($stat) { return $stat['nombre']; }, $repartition_age)); ?>,
                    backgroundColor: [
                        '#8B5CF6',
                        '#EC4899',
                        '#06B6D4',
                        '#10B981',
                        '#F59E0B',
                        '#EF4444'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html> 