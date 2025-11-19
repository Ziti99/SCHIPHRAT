<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'caissiere'])) {
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

// Période sélectionnée
$periode = $_GET['periode'] ?? 'mois_courant';
$date_debut = $_GET['date_debut'] ?? date('Y-m-01');
$date_fin = $_GET['date_fin'] ?? date('Y-m-t');
$acte_filtre = $_GET['acte_id'] ?? 'tous'; // Filtre par acte

// Récupérer la liste des actes pour le filtre
$actes_liste = $db->fetchAll("
    SELECT id, nom_acte, montant 
    FROM actes_poses 
    WHERE is_active = 1 
    ORDER BY nom_acte
");

// Définir les dates selon la période
switch ($periode) {
    case 'aujourd_hui':
        $date_debut = date('Y-m-d');
        $date_fin = date('Y-m-d');
        break;
    case 'semaine':
        $date_debut = date('Y-m-d', strtotime('monday this week'));
        $date_fin = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'mois_courant':
        $date_debut = date('Y-m-01');
        $date_fin = date('Y-m-t');
        break;
    case 'mois_dernier':
        $date_debut = date('Y-m-01', strtotime('first day of last month'));
        $date_fin = date('Y-m-t', strtotime('last day of last month'));
        break;
    case 'annee':
        $date_debut = date('Y-01-01');
        $date_fin = date('Y-12-31');
        break;
}

// Construire la condition pour le filtre acte
$acte_where = "";
$acte_params = [$date_debut, $date_fin];
$acte_join = "";

if ($acte_filtre !== 'tous' && is_numeric($acte_filtre)) {
    $acte_join = "INNER JOIN consultations_prenatales cp ON p.consultation_id = cp.id
                  INNER JOIN consultation_actes ca ON cp.id = ca.consultation_id";
    $acte_where = "AND ca.acte_id = ?";
    $acte_params[] = $acte_filtre;
}

// Statistiques globales de la période
$stats_globales = $db->fetch("
    SELECT 
        COUNT(DISTINCT p.id) as nb_paiements,
        COUNT(DISTINCT p.patiente_id) as nb_patientes,
        COALESCE(SUM(p.montant_total), 0) as total_facture,
        COALESCE(SUM(p.montant_paye), 0) as total_collecte,
        COALESCE(SUM(p.montant_restant), 0) as total_restant,
        SUM(CASE WHEN p.statut = 'paye_total' THEN 1 ELSE 0 END) as nb_complets,
        SUM(CASE WHEN p.statut = 'paye_partiel' THEN 1 ELSE 0 END) as nb_partiels,
        SUM(CASE WHEN p.statut = 'en_attente' THEN 1 ELSE 0 END) as nb_attente
    FROM paiements p
    $acte_join
    WHERE DATE(COALESCE(p.date_paiement, p.created_at)) BETWEEN ? AND ?
    $acte_where
", $acte_params);

// Statistiques par acte si un acte est sélectionné
$stats_acte = null;
$acte_nom = 'Tous les actes';
if ($acte_filtre !== 'tous' && is_numeric($acte_filtre)) {
    $stats_acte = $db->fetch("
        SELECT 
            ap.id,
            ap.nom_acte,
            COUNT(DISTINCT ca.consultation_id) as nb_consultations,
            COUNT(ca.id) as nb_fois,
            SUM(ca.montant * ca.quantite) as total_montant_acte,
            SUM(CASE WHEN p.statut = 'paye_total' THEN ca.montant * ca.quantite ELSE 0 END) as total_collecte_acte
        FROM consultation_actes ca
        INNER JOIN actes_poses ap ON ca.acte_id = ap.id
        INNER JOIN consultations_prenatales cp ON ca.consultation_id = cp.id
        INNER JOIN paiements p ON cp.id = p.consultation_id
        WHERE ca.acte_id = ?
        AND DATE(COALESCE(p.date_paiement, p.created_at)) BETWEEN ? AND ?
        GROUP BY ap.id, ap.nom_acte
    ", [$acte_filtre, $date_debut, $date_fin]);
    
    if ($stats_acte) {
        $acte_nom = $stats_acte['nom_acte'];
    }
}

// Répartition par mode de paiement
$modes_paiement_params = [$date_debut, $date_fin];
$modes_paiement_join = "";
$modes_paiement_where = "WHERE p.mode_paiement IS NOT NULL AND DATE(p.date_paiement) BETWEEN ? AND ?";

if ($acte_filtre !== 'tous' && is_numeric($acte_filtre)) {
    $modes_paiement_join = "INNER JOIN consultations_prenatales cp ON p.consultation_id = cp.id
                            INNER JOIN consultation_actes ca ON cp.id = ca.consultation_id";
    $modes_paiement_where .= " AND ca.acte_id = ?";
    $modes_paiement_params[] = $acte_filtre;
}

$modes_paiement = $db->fetchAll("
    SELECT 
        p.mode_paiement,
        COUNT(DISTINCT p.id) as nombre,
        SUM(p.montant_paye) as total
    FROM paiements p
    $modes_paiement_join
    $modes_paiement_where
    GROUP BY p.mode_paiement
    ORDER BY total DESC
", $modes_paiement_params);

// Évolution quotidienne
$evolution_params = [$date_debut, $date_fin];
$evolution_join = "";
$evolution_where = "WHERE DATE(COALESCE(p.date_paiement, p.created_at)) BETWEEN ? AND ?";

if ($acte_filtre !== 'tous' && is_numeric($acte_filtre)) {
    $evolution_join = "INNER JOIN consultations_prenatales cp ON p.consultation_id = cp.id
                       INNER JOIN consultation_actes ca ON cp.id = ca.consultation_id";
    $evolution_where .= " AND ca.acte_id = ?";
    $evolution_params[] = $acte_filtre;
}

$evolution_quotidienne = $db->fetchAll("
    SELECT 
        DATE(COALESCE(p.date_paiement, p.created_at)) as date,
        COUNT(DISTINCT p.id) as nb_paiements,
        SUM(p.montant_paye) as total_jour
    FROM paiements p
    $evolution_join
    $evolution_where
    GROUP BY DATE(COALESCE(p.date_paiement, p.created_at))
    ORDER BY date
", $evolution_params);

// Top 10 actes les plus facturés (uniquement si pas de filtre acte spécifique)
$top_actes = [];
if ($acte_filtre === 'tous') {
    $top_actes = $db->fetchAll("
        SELECT 
            ap.nom_acte,
            COUNT(*) as nb_fois,
            SUM(ca.montant * ca.quantite) as total_montant
        FROM consultation_actes ca
        INNER JOIN actes_poses ap ON ca.acte_id = ap.id
        INNER JOIN consultations_prenatales cp ON ca.consultation_id = cp.id
        INNER JOIN paiements p ON cp.id = p.consultation_id
        WHERE DATE(COALESCE(p.date_paiement, p.created_at)) BETWEEN ? AND ?
        GROUP BY ap.id, ap.nom_acte
        ORDER BY total_montant DESC
        LIMIT 10
    ", [$date_debut, $date_fin]);
}

// Performance par caissière
$performance_params = [$date_debut, $date_fin];
$performance_join = "";
$performance_where = "WHERE DATE(p.date_paiement) BETWEEN ? AND ?";

if ($acte_filtre !== 'tous' && is_numeric($acte_filtre)) {
    $performance_join = "INNER JOIN consultations_prenatales cp ON p.consultation_id = cp.id
                         INNER JOIN consultation_actes ca ON cp.id = ca.consultation_id";
    $performance_where .= " AND ca.acte_id = ?";
    $performance_params[] = $acte_filtre;
}

$performance_caissieres = $db->fetchAll("
    SELECT 
        u.nom,
        u.prenom,
        COUNT(DISTINCT p.id) as nb_paiements,
        SUM(p.montant_paye) as total_collecte
    FROM paiements p
    INNER JOIN users u ON p.caissiere_id = u.id
    $performance_join
    $performance_where
    GROUP BY u.id, u.nom, u.prenom
    ORDER BY total_collecte DESC
", $performance_params);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques de Recettes - Caissière</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50">
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        <div class="flex-1">
            <?php include 'includes/navbar.php'; ?>
            
            <div class="p-8">
                <!-- En-tête -->
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-chart-bar text-green-600 mr-3"></i>
                        Statistiques de Recettes
                    </h1>
                    <p class="text-gray-600">Analyse détaillée des paiements et recettes</p>
                </div>

                <!-- Filtres de période -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <form method="GET" class="grid md:grid-cols-6 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Période rapide</label>
                            <select name="periode" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="aujourd_hui" <?php echo $periode === 'aujourd_hui' ? 'selected' : ''; ?>>Aujourd'hui</option>
                                <option value="semaine" <?php echo $periode === 'semaine' ? 'selected' : ''; ?>>Cette semaine</option>
                                <option value="mois_courant" <?php echo $periode === 'mois_courant' ? 'selected' : ''; ?>>Ce mois</option>
                                <option value="mois_dernier" <?php echo $periode === 'mois_dernier' ? 'selected' : ''; ?>>Mois dernier</option>
                                <option value="annee" <?php echo $periode === 'annee' ? 'selected' : ''; ?>>Cette année</option>
                                <option value="personnalise" <?php echo $periode === 'personnalise' ? 'selected' : ''; ?>>Personnalisé</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Acte posé</label>
                            <select name="acte_id" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="tous" <?php echo $acte_filtre === 'tous' ? 'selected' : ''; ?>>Tous les actes</option>
                                <?php foreach ($actes_liste as $acte): ?>
                                    <option value="<?php echo $acte['id']; ?>" <?php echo $acte_filtre == $acte['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($acte['nom_acte']); ?> (<?php echo number_format($acte['montant'], 0, ',', ' '); ?> FCFA)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date début</label>
                            <input type="date" name="date_debut" value="<?php echo $date_debut; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date fin</label>
                            <input type="date" name="date_fin" value="<?php echo $date_fin; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
                                <i class="fas fa-search mr-2"></i>Filtrer
                            </button>
                        </div>
                        <div class="flex items-end">
                            <a href="caissiere_statistiques.php" class="w-full text-center bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                                <i class="fas fa-redo mr-2"></i>Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Période affichée -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-center justify-between">
                        <p class="text-blue-800">
                            <i class="fas fa-calendar mr-2"></i>
                            <strong>Période :</strong> <?php echo date('d/m/Y', strtotime($date_debut)); ?> au <?php echo date('d/m/Y', strtotime($date_fin)); ?>
                        </p>
                        <p class="text-blue-800">
                            <i class="fas fa-stethoscope mr-2"></i>
                            <strong>Acte :</strong> <?php echo htmlspecialchars($acte_nom); ?>
                        </p>
                    </div>
                </div>

                <!-- Statistiques principales -->
                <div class="grid md:grid-cols-4 gap-6 mb-6">
                    <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-green-500">
                        <p class="text-sm text-gray-600 mb-1">Total Collecté</p>
                        <p class="text-3xl font-bold text-green-600">
                            <?php echo number_format($stats_globales['total_collecte'], 0, ',', ' '); ?> <span class="text-lg">FCFA</span>
                        </p>
                        <p class="text-xs text-gray-500 mt-2"><?php echo $stats_globales['nb_paiements']; ?> paiement(s)</p>
                    </div>

                    <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-blue-500">
                        <p class="text-sm text-gray-600 mb-1">Total Facturé</p>
                        <p class="text-3xl font-bold text-blue-600">
                            <?php echo number_format($stats_globales['total_facture'], 0, ',', ' '); ?> <span class="text-lg">FCFA</span>
                        </p>
                        <p class="text-xs text-gray-500 mt-2"><?php echo $stats_globales['nb_patientes']; ?> patiente(s)</p>
                    </div>

                    <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-orange-500">
                        <p class="text-sm text-gray-600 mb-1">Reste à Collecter</p>
                        <p class="text-3xl font-bold text-orange-600">
                            <?php echo number_format($stats_globales['total_restant'], 0, ',', ' '); ?> <span class="text-lg">FCFA</span>
                        </p>
                        <p class="text-xs text-gray-500 mt-2"><?php echo $stats_globales['nb_attente']; ?> en attente</p>
                    </div>

                    <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-purple-500">
                        <p class="text-sm text-gray-600 mb-1">Taux de Collecte</p>
                        <p class="text-3xl font-bold text-purple-600">
                            <?php 
                            $taux = $stats_globales['total_facture'] > 0 
                                ? ($stats_globales['total_collecte'] / $stats_globales['total_facture']) * 100 
                                : 0;
                            echo number_format($taux, 1); 
                            ?> <span class="text-lg">%</span>
                        </p>
                        <p class="text-xs text-gray-500 mt-2"><?php echo $stats_globales['nb_complets']; ?> payé(s) complet(s)</p>
                    </div>
                </div>

                <!-- Statistiques par acte (si un acte est sélectionné) -->
                <?php if ($stats_acte): ?>
                <div class="bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-200 rounded-lg p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">
                        <i class="fas fa-stethoscope text-indigo-600 mr-2"></i>
                        Statistiques pour : <?php echo htmlspecialchars($stats_acte['nom_acte']); ?>
                    </h2>
                    <div class="grid md:grid-cols-4 gap-4">
                        <div class="bg-white rounded-lg p-4 shadow">
                            <p class="text-sm text-gray-600 mb-1">Total par Acte</p>
                            <p class="text-2xl font-bold text-indigo-600">
                                <?php echo number_format($stats_acte['total_montant_acte'], 0, ',', ' '); ?> <span class="text-sm">FCFA</span>
                            </p>
                            <p class="text-xs text-gray-500 mt-1"><?php echo $stats_acte['nb_fois']; ?> fois</p>
                        </div>
                        <div class="bg-white rounded-lg p-4 shadow">
                            <p class="text-sm text-gray-600 mb-1">Collecté</p>
                            <p class="text-2xl font-bold text-green-600">
                                <?php echo number_format($stats_acte['total_collecte_acte'], 0, ',', ' '); ?> <span class="text-sm">FCFA</span>
                            </p>
                            <p class="text-xs text-gray-500 mt-1"><?php echo $stats_acte['nb_consultations']; ?> consultation(s)</p>
                        </div>
                        <div class="bg-white rounded-lg p-4 shadow">
                            <p class="text-sm text-gray-600 mb-1">Reste à Collecter</p>
                            <p class="text-2xl font-bold text-orange-600">
                                <?php 
                                $reste_acte = $stats_acte['total_montant_acte'] - $stats_acte['total_collecte_acte'];
                                echo number_format($reste_acte, 0, ',', ' '); 
                                ?> <span class="text-sm">FCFA</span>
                            </p>
                        </div>
                        <div class="bg-white rounded-lg p-4 shadow">
                            <p class="text-sm text-gray-600 mb-1">Taux de Collecte</p>
                            <p class="text-2xl font-bold text-purple-600">
                                <?php 
                                $taux_acte = $stats_acte['total_montant_acte'] > 0 
                                    ? ($stats_acte['total_collecte_acte'] / $stats_acte['total_montant_acte']) * 100 
                                    : 0;
                                echo number_format($taux_acte, 1); 
                                ?> <span class="text-sm">%</span>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="grid lg:grid-cols-2 gap-6 mb-6">
                    <!-- Évolution quotidienne -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Évolution Quotidienne</h2>
                        <canvas id="evolutionChart"></canvas>
                    </div>

                    <!-- Répartition par mode de paiement -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Modes de Paiement</h2>
                        <?php if (!empty($modes_paiement)): ?>
                            <canvas id="modesChart"></canvas>
                            <div class="mt-4 space-y-2">
                                <?php foreach ($modes_paiement as $mode): ?>
                                    <div class="flex justify-between items-center p-2 bg-gray-50 rounded">
                                        <span class="capitalize"><?php echo str_replace('_', ' ', $mode['mode_paiement']); ?></span>
                                        <span class="font-bold text-green-600">
                                            <?php echo number_format($mode['total'], 0, ',', ' '); ?> FCFA
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-gray-500 py-8">Aucune donnée pour cette période</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="grid lg:grid-cols-2 gap-6">
                    <!-- Top actes -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Top 10 Actes Facturés</h2>
                        <?php if (!empty($top_actes)): ?>
                            <div class="space-y-3">
                                <?php foreach ($top_actes as $index => $acte): ?>
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center">
                                            <span class="w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center font-bold text-sm mr-3">
                                                <?php echo $index + 1; ?>
                                            </span>
                                            <div>
                                                <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($acte['nom_acte']); ?></p>
                                                <p class="text-xs text-gray-500"><?php echo $acte['nb_fois']; ?> fois</p>
                                            </div>
                                        </div>
                                        <p class="text-lg font-bold text-green-600">
                                            <?php echo number_format($acte['total_montant'], 0, ',', ' '); ?> FCFA
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-gray-500 py-8">Aucun acte pour cette période</p>
                        <?php endif; ?>
                    </div>

                    <!-- Performance caissières -->
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-4">Performance par Caissière</h2>
                        <?php if (!empty($performance_caissieres)): ?>
                            <div class="space-y-3">
                                <?php foreach ($performance_caissieres as $index => $caissiere): ?>
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                        <div class="flex items-center">
                                            <span class="w-8 h-8 bg-purple-500 text-white rounded-full flex items-center justify-center font-bold text-sm mr-3">
                                                <?php echo $index + 1; ?>
                                            </span>
                                            <div>
                                                <p class="font-semibold text-gray-900">
                                                    <?php echo htmlspecialchars($caissiere['prenom'] . ' ' . $caissiere['nom']); ?>
                                                </p>
                                                <p class="text-xs text-gray-500"><?php echo $caissiere['nb_paiements']; ?> paiement(s)</p>
                                            </div>
                                        </div>
                                        <p class="text-lg font-bold text-purple-600">
                                            <?php echo number_format($caissiere['total_collecte'], 0, ',', ' '); ?> FCFA
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-gray-500 py-8">Aucune donnée pour cette période</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Graphique évolution quotidienne
        <?php if (!empty($evolution_quotidienne)): ?>
        const ctx1 = document.getElementById('evolutionChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: [<?php echo implode(',', array_map(function($e) { return "'" . date('d/m', strtotime($e['date'])) . "'"; }, $evolution_quotidienne)); ?>],
                datasets: [{
                    label: 'Montant collecté (FCFA)',
                    data: [<?php echo implode(',', array_map(function($e) { return $e['total_jour']; }, $evolution_quotidienne)); ?>],
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
        <?php endif; ?>

        // Graphique modes de paiement
        <?php if (!empty($modes_paiement)): ?>
        const ctx2 = document.getElementById('modesChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: [<?php echo implode(',', array_map(function($m) { return "'" . ucfirst(str_replace('_', ' ', $m['mode_paiement'])) . "'"; }, $modes_paiement)); ?>],
                datasets: [{
                    data: [<?php echo implode(',', array_map(function($m) { return $m['total']; }, $modes_paiement)); ?>],
                    backgroundColor: ['#10B981', '#3B82F6', '#8B5CF6', '#F59E0B', '#EF4444', '#EC4899']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>

