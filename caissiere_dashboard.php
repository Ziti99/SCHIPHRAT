<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'caissiere') {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';
$db = new Database();

// Vérifier si les tables de paiements existent
try {
    $db->query("SELECT 1 FROM paiements LIMIT 1");
} catch (PDOException $e) {
    header('Location: /setup_caisse_system.php');
    exit;
}

// Récupérer les statistiques du jour
$stats_jour = $db->fetch("
    SELECT 
        COUNT(*) as nb_paiements,
        COALESCE(SUM(montant_paye), 0) as total_collecte,
        COALESCE(SUM(CASE WHEN statut = 'en_attente' THEN montant_restant ELSE 0 END), 0) as en_attente
    FROM paiements
    WHERE DATE(COALESCE(date_paiement, created_at)) = CURDATE()
");

// Statistiques du mois
$stats_mois = $db->fetch("
    SELECT 
        COUNT(*) as nb_paiements,
        COALESCE(SUM(montant_paye), 0) as total_collecte,
        COALESCE(SUM(CASE WHEN statut = 'paye_total' THEN 1 ELSE 0 END), 0) as nb_complets
    FROM paiements
    WHERE MONTH(COALESCE(date_paiement, created_at)) = MONTH(CURDATE())
    AND YEAR(COALESCE(date_paiement, created_at)) = YEAR(CURDATE())
");

// Paiements en attente
$paiements_attente = $db->fetchAll("
    SELECT 
        p.id,
        p.montant_total,
        p.montant_paye,
        p.montant_restant,
        p.statut,
        p.created_at,
        cp.date_consultation,
        pat.nom,
        pat.prenom,
        pat.telephone,
        COUNT(ca.id) as nb_actes
    FROM paiements p
    INNER JOIN patientes pat ON p.patiente_id = pat.id
    INNER JOIN consultations_prenatales cp ON p.consultation_id = cp.id
    LEFT JOIN consultation_actes ca ON cp.id = ca.consultation_id
    WHERE p.statut IN ('en_attente', 'paye_partiel')
    GROUP BY p.id
    ORDER BY cp.date_consultation DESC
    LIMIT 10
");

// Derniers paiements validés
$derniers_paiements = $db->fetchAll("
    SELECT 
        p.id,
        p.montant_paye,
        p.mode_paiement,
        p.date_paiement,
        pat.nom,
        pat.prenom,
        u.nom as caissiere_nom,
        u.prenom as caissiere_prenom
    FROM paiements p
    INNER JOIN patientes pat ON p.patiente_id = pat.id
    LEFT JOIN users u ON p.caissiere_id = u.id
    WHERE p.statut = 'paye_total' AND p.date_paiement IS NOT NULL
    ORDER BY p.date_paiement DESC
    LIMIT 5
");

// Répartition par mode de paiement (mois en cours)
$modes_paiement = $db->fetchAll("
    SELECT 
        mode_paiement,
        COUNT(*) as nombre,
        SUM(montant_paye) as total
    FROM paiements
    WHERE mode_paiement IS NOT NULL
    AND MONTH(date_paiement) = MONTH(CURDATE())
    AND YEAR(date_paiement) = YEAR(CURDATE())
    GROUP BY mode_paiement
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Caissière - Clinique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gradient-to-br from-green-50 via-emerald-50 to-teal-50">
    <div class="flex">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Contenu principal -->
        <div class="flex-1">
            <!-- Navigation -->
            <?php include 'includes/navbar.php'; ?>

            <div class="p-8">
                <!-- En-tête -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-cash-register text-green-600 mr-3"></i>
                        Dashboard Caisse
                    </h1>
                    <p class="text-gray-600">Bienvenue <?php echo htmlspecialchars($_SESSION['user_prenom'] ?? ''); ?> - Gestion des paiements et recettes</p>
                </div>

                <!-- Statistiques du jour -->
                <div class="grid md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 font-medium mb-1">Collecté Aujourd'hui</p>
                                <p class="text-3xl font-bold text-green-600">
                                    <?php echo number_format($stats_jour['total_collecte'] ?? 0, 0, ',', ' '); ?> <span class="text-lg">FCFA</span>
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    <?php echo $stats_jour['nb_paiements'] ?? 0; ?> paiement(s)
                                </p>
                            </div>
                            <div class="bg-green-100 rounded-full p-4">
                                <i class="fas fa-money-bill-wave text-3xl text-green-600"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-orange-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 font-medium mb-1">En Attente Aujourd'hui</p>
                                <p class="text-3xl font-bold text-orange-600">
                                    <?php echo number_format($stats_jour['en_attente'] ?? 0, 0, ',', ' '); ?> <span class="text-lg">FCFA</span>
                                </p>
                                <p class="text-xs text-gray-500 mt-1">À encaisser</p>
                            </div>
                            <div class="bg-orange-100 rounded-full p-4">
                                <i class="fas fa-clock text-3xl text-orange-600"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600 font-medium mb-1">Total Mois</p>
                                <p class="text-3xl font-bold text-blue-600">
                                    <?php echo number_format($stats_mois['total_collecte'] ?? 0, 0, ',', ' '); ?> <span class="text-lg">FCFA</span>
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    <?php echo $stats_mois['nb_complets'] ?? 0; ?> paiement(s) complet(s)
                                </p>
                            </div>
                            <div class="bg-blue-100 rounded-full p-4">
                                <i class="fas fa-chart-line text-3xl text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions rapides -->
                <div class="grid md:grid-cols-4 gap-4 mb-8">
                    <a href="caissiere_consultations.php" class="bg-gradient-to-r from-green-500 to-emerald-500 text-white p-4 rounded-lg shadow-lg hover:shadow-xl transition-all text-center">
                        <i class="fas fa-list text-2xl mb-2"></i>
                        <p class="font-semibold">Toutes les Consultations</p>
                    </a>
                    <a href="caissiere_consultations.php?statut=en_attente" class="bg-gradient-to-r from-orange-500 to-red-500 text-white p-4 rounded-lg shadow-lg hover:shadow-xl transition-all text-center">
                        <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                        <p class="font-semibold">Paiements en Attente</p>
                    </a>
                    <a href="caissiere_statistiques.php" class="bg-gradient-to-r from-blue-500 to-cyan-500 text-white p-4 rounded-lg shadow-lg hover:shadow-xl transition-all text-center">
                        <i class="fas fa-chart-bar text-2xl mb-2"></i>
                        <p class="font-semibold">Statistiques Détaillées</p>
                    </a>
                    <a href="caissiere_recherche.php" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white p-4 rounded-lg shadow-lg hover:shadow-xl transition-all text-center">
                        <i class="fas fa-search text-2xl mb-2"></i>
                        <p class="font-semibold">Rechercher Patiente</p>
                    </a>
                </div>

                <div class="grid lg:grid-cols-2 gap-6">
                    <!-- Paiements en attente -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-xl font-bold text-gray-900">
                                <i class="fas fa-hourglass-half text-orange-600 mr-2"></i>
                                Paiements en Attente
                            </h2>
                            <a href="caissiere_consultations.php?statut=en_attente" class="text-sm text-blue-600 hover:text-blue-800">
                                Voir tout →
                            </a>
                        </div>
                        
                        <?php if (empty($paiements_attente)): ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-check-circle text-4xl text-green-400 mb-3"></i>
                                <p>Aucun paiement en attente</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3 max-h-96 overflow-y-auto">
                                <?php foreach ($paiements_attente as $p): ?>
                                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                        <div class="flex justify-between items-start mb-2">
                                            <div>
                                                <h3 class="font-semibold text-gray-900">
                                                    <?php echo htmlspecialchars($p['prenom'] . ' ' . $p['nom']); ?>
                                                </h3>
                                                <p class="text-xs text-gray-500">
                                                    <i class="fas fa-calendar mr-1"></i>
                                                    <?php echo date('d/m/Y à H:i', strtotime($p['date_consultation'])); ?>
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    <i class="fas fa-stethoscope mr-1"></i>
                                                    <?php echo $p['nb_actes']; ?> acte(s)
                                                </p>
                                            </div>
                                            <span class="px-2 py-1 text-xs rounded-full <?php echo $p['statut'] === 'paye_partiel' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo $p['statut'] === 'paye_partiel' ? 'Partiel' : 'Non payé'; ?>
                                            </span>
                                        </div>
                                        <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                                            <div>
                                                <p class="text-sm text-gray-600">
                                                    Payé: <strong class="text-green-600"><?php echo number_format($p['montant_paye'], 0, ',', ' '); ?> FCFA</strong>
                                                </p>
                                                <p class="text-sm text-gray-600">
                                                    Reste: <strong class="text-red-600"><?php echo number_format($p['montant_restant'], 0, ',', ' '); ?> FCFA</strong>
                                                </p>
                                            </div>
                                            <a href="caissiere_valider_paiement.php?id=<?php echo $p['id']; ?>" 
                                               class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition-colors text-sm">
                                                <i class="fas fa-check mr-1"></i> Valider
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Derniers paiements -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">
                            <i class="fas fa-history text-blue-600 mr-2"></i>
                            Derniers Paiements Validés
                        </h2>
                        
                        <?php if (empty($derniers_paiements)): ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-receipt text-4xl text-gray-300 mb-3"></i>
                                <p>Aucun paiement validé</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-3">
                                <?php foreach ($derniers_paiements as $dp): ?>
                                    <div class="border-l-4 border-green-500 bg-green-50 p-3 rounded">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <p class="font-semibold text-gray-900">
                                                    <?php echo htmlspecialchars($dp['prenom'] . ' ' . $dp['nom']); ?>
                                                </p>
                                                <p class="text-xs text-gray-600">
                                                    Par <?php echo htmlspecialchars($dp['caissiere_prenom'] ?? 'Système'); ?>
                                                    • <?php echo date('d/m/Y H:i', strtotime($dp['date_paiement'])); ?>
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-lg font-bold text-green-600">
                                                    <?php echo number_format($dp['montant_paye'], 0, ',', ' '); ?> FCFA
                                                </p>
                                                <p class="text-xs text-gray-600 capitalize">
                                                    <?php echo str_replace('_', ' ', $dp['mode_paiement'] ?? ''); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Répartition modes de paiement -->
                <?php if (!empty($modes_paiement)): ?>
                <div class="bg-white rounded-xl shadow-lg p-6 mt-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">
                        <i class="fas fa-pie-chart text-purple-600 mr-2"></i>
                        Répartition des Modes de Paiement (Ce mois)
                    </h2>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <canvas id="modesChart"></canvas>
                        </div>
                        <div class="space-y-3">
                            <?php foreach ($modes_paiement as $mode): ?>
                                <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="font-semibold text-gray-900 capitalize">
                                            <?php echo str_replace('_', ' ', $mode['mode_paiement']); ?>
                                        </p>
                                        <p class="text-xs text-gray-600"><?php echo $mode['nombre']; ?> paiement(s)</p>
                                    </div>
                                    <p class="text-lg font-bold text-green-600">
                                        <?php echo number_format($mode['total'], 0, ',', ' '); ?> FCFA
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Graphique des modes de paiement
        <?php if (!empty($modes_paiement)): ?>
        const ctx = document.getElementById('modesChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: [<?php echo implode(',', array_map(function($m) { return "'" . ucfirst(str_replace('_', ' ', $m['mode_paiement'])) . "'"; }, $modes_paiement)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_map(function($m) { return $m['total']; }, $modes_paiement)); ?>],
                    backgroundColor: [
                        '#10B981', // green
                        '#3B82F6', // blue
                        '#8B5CF6', // purple
                        '#F59E0B', // amber
                        '#EF4444', // red
                        '#EC4899'  // pink
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
        <?php endif; ?>
    </script>
</body>
</html>

