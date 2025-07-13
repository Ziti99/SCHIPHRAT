<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$user = $auth->getCurrentUser();
$db = new Database();

// Récupérer les statistiques
try {
    // Total des patientes
    $total_patientes = $db->fetchOne("SELECT COUNT(*) as total FROM patientes")['total'];
    
    // Total des consultations prénatales ce mois
    $consultations_mois = $db->fetchOne("
        SELECT COUNT(*) as total 
        FROM consultations_prenatales 
        WHERE MONTH(date_consultation) = MONTH(CURRENT_DATE()) 
        AND YEAR(date_consultation) = YEAR(CURRENT_DATE())
    ")['total'];
    
    // Total des accouchements ce mois
    $accouchements_mois = $db->fetchOne("
        SELECT COUNT(*) as total 
        FROM accouchements 
        WHERE MONTH(date_accouchement) = MONTH(CURRENT_DATE()) 
        AND YEAR(date_accouchement) = YEAR(CURRENT_DATE())
    ")['total'];
    
    // Total des suivis postnataux ce mois
    $suivis_mois = $db->fetchOne("
        SELECT COUNT(*) as total 
        FROM suivi_postnatal 
        WHERE MONTH(date_visite) = MONTH(CURRENT_DATE()) 
        AND YEAR(date_visite) = YEAR(CURRENT_DATE())
    ")['total'];
    
    // Consultations par mois (6 derniers mois)
    $consultations_par_mois = $db->fetchAll("
        SELECT 
            DATE_FORMAT(date_consultation, '%Y-%m') as mois,
            COUNT(*) as total
        FROM consultations_prenatales 
        WHERE date_consultation >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(date_consultation, '%Y-%m')
        ORDER BY mois
    ");
    
    // Accouchements par type
    $accouchements_par_type = $db->fetchAll("
        SELECT mode_accouchement, COUNT(*) as total
        FROM accouchements
        GROUP BY mode_accouchement
    ");
    
    // Dernières consultations
    $dernieres_consultations = $db->fetchAll("
        SELECT cp.*, p.nom, p.prenom
        FROM consultations_prenatales cp
        JOIN grossesses g ON cp.grossesse_id = g.id
        JOIN patientes p ON g.patiente_id = p.id
        ORDER BY cp.date_consultation DESC
        LIMIT 5
    ");
    
} catch (Exception $e) {
    $total_patientes = 0;
    $consultations_mois = 0;
    $accouchements_mois = 0;
    $suivis_mois = 0;
    $consultations_par_mois = [];
    $accouchements_par_type = [];
    $dernieres_consultations = [];
}
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
</head>
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-cyan-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="../../dashboard.php" class="text-purple-600 hover:text-purple-800 mr-4">
                        <i class="fas fa-arrow-left mr-2"></i>Retour
                    </a>
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-chart-bar text-2xl text-indigo-600 mr-3"></i>
                        <span class="text-xl font-bold text-gray-900">Statistiques</span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">
                        <i class="fas fa-user mr-2"></i>
                        <?php echo htmlspecialchars($user['nom'] . ' ' . $user['prenom']); ?>
                    </span>
                    <a href="../../logout.php" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- En-tête -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Statistiques</h1>
            <p class="text-gray-600">Tableau de bord et analyses</p>
        </div>

        <!-- Métriques principales -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Patientes</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $total_patientes; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-stethoscope text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Consultations (Mois)</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $consultations_mois; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-cyan-100 text-cyan-600">
                        <i class="fas fa-baby text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Accouchements (Mois)</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $accouchements_mois; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-pink-100 text-pink-600">
                        <i class="fas fa-heartbeat text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Suivis (Mois)</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $suivis_mois; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Graphiques -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Graphique des consultations par mois -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Consultations par mois</h3>
                <canvas id="consultationsChart" width="400" height="200"></canvas>
            </div>

            <!-- Graphique des types d'accouchements -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Types d'accouchements</h3>
                <canvas id="accouchementsChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Dernières consultations -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Dernières consultations</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patiente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Observations</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($dernieres_consultations)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                    Aucune consultation récente
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($dernieres_consultations as $consultation): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('d/m/Y H:i', strtotime($consultation['date_consultation'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($consultation['nom'] . ' ' . $consultation['prenom']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        <?php echo htmlspecialchars(substr($consultation['observations'], 0, 50)) . (strlen($consultation['observations']) > 50 ? '...' : ''); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="../consultations/view.php?id=<?php echo $consultation['id']; ?>" class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Graphique des consultations par mois
        const consultationsCtx = document.getElementById('consultationsChart').getContext('2d');
        new Chart(consultationsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($consultations_par_mois, 'mois')); ?>,
                datasets: [{
                    label: 'Consultations',
                    data: <?php echo json_encode(array_column($consultations_par_mois, 'total')); ?>,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Graphique des types d'accouchements
        const accouchementsCtx = document.getElementById('accouchementsChart').getContext('2d');
        new Chart(accouchementsCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($accouchements_par_type, 'mode_accouchement')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($accouchements_par_type, 'total')); ?>,
                    backgroundColor: [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)'
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