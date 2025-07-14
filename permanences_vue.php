<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';

// Vérifier que l'utilisateur est admin ou caissière
if (!in_array($_SESSION['user_role'], ['admin', 'caissiere'])) {
    header('Location: /dashboard.php');
    exit;
}

try {
    $db = new Database();
    
    // Gestion des filtres de date et statut
    $date_du = $_GET['date_du'] ?? date('Y-m-d');  // Jour actuel par défaut
    $date_au = $_GET['date_au'] ?? date('Y-m-d');  // Jour actuel par défaut
    $statut_filter = $_GET['statut'] ?? '';

    // Construire la requête avec filtres
    $where_conditions = ["DATE(p.created_at) >= ?", "DATE(p.created_at) <= ?"];
    $params = [$date_du, $date_au];

    if (!empty($statut_filter)) {
        $where_conditions[] = "p.statut_final = ?";
        $params[] = $statut_filter;
    }

    $where_clause = implode(" AND ", $where_conditions);

    // Récupérer toutes les permanences filtrées
    $permanences = $db->fetchAll("
        SELECT p.*, a.nom_acte, u.nom as secretaire_nom, u.prenom as secretaire_prenom
        FROM permanences p
        JOIN actes_poses a ON p.acte_id = a.id
        JOIN users u ON p.secretaire_id = u.id
        WHERE $where_clause
        ORDER BY p.created_at DESC
    ", $params);
    
} catch (Exception $e) {
    // En cas d'erreur, initialiser des tableaux vides
    $permanences = [];
    $stats = [
        'total_permanences' => 0,
        'total_montant' => 0,
        'validees' => 0,
        'annulees' => 0,
        'en_attente' => 0,
        'moyenne_montant' => 0,
        'en_attente_aujourd_hui' => 0
    ];
    $total_par_acte = [];
    $periode_affichee = "Erreur de connexion";
    
    // Log de l'erreur pour debug
    error_log("Erreur DB dans permanences_vue.php: " . $e->getMessage());
}

    // Statistiques pour le dashboard
    $stats = $db->fetch("
        SELECT 
            COUNT(*) as total_permanences,
            SUM(montant_paye) as total_montant,
            COUNT(CASE WHEN statut_final = 'ok' THEN 1 END) as validees,
            COUNT(CASE WHEN statut_final = 'annule' THEN 1 END) as annulees,
            COUNT(CASE WHEN statut_final = 'en_attente' THEN 1 END) as en_attente,
            AVG(montant_paye) as moyenne_montant,
            COUNT(CASE WHEN statut_final = 'en_attente' AND DATE(created_at) = CURDATE() THEN 1 END) as en_attente_aujourd_hui
        FROM permanences 
        WHERE DATE(created_at) >= ? AND DATE(created_at) <= ?
    ", [$date_du, $date_au]);

    // Total par acte posé (uniquement les validées)
    $total_par_acte = $db->fetchAll("
        SELECT a.nom_acte, COUNT(*) as nb_fois, SUM(p.montant_paye) as total_montant
        FROM permanences p
        JOIN actes_poses a ON p.acte_id = a.id
        WHERE DATE(p.created_at) >= ? AND DATE(p.created_at) <= ? 
        AND p.statut_final = 'ok'
        GROUP BY a.id, a.nom_acte
        ORDER BY nb_fois DESC
    ", [$date_du, $date_au]);

// Déterminer la période affichée
$periode_affichee = '';
if ($date_du === $date_au) {
    if ($date_du === date('Y-m-d')) {
        $periode_affichee = "Aujourd'hui (" . date('d/m/Y') . ")";
    } else {
        $periode_affichee = "Le " . date('d/m/Y', strtotime($date_du));
    }
} else {
    $periode_affichee = "Du " . date('d/m/Y', strtotime($date_du)) . " au " . date('d/m/Y', strtotime($date_au));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vue des Permanences - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-cyan-50 min-h-screen">
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
                        <p class="text-xs text-gray-500">Vue des permanences</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
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
        <?php include 'includes/sidebar.php'; ?>
        <main class="flex-1 py-8 px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                    <i class="fas fa-list text-purple-600 mr-3"></i>
                    Vue des permanences
                </h2>
                <div class="bg-gradient-to-r from-purple-100 to-pink-100 px-4 py-2 rounded-lg">
                    <p class="text-sm text-gray-700">
                        <i class="fas fa-calendar-alt mr-2"></i>
                        <span id="periodeAffichee"><?= $periode_affichee ?></span>
                    </p>
                </div>
            </div>

            <!-- Dashboard des statistiques -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900" data-stat="stats_title">Statistiques - <?= $periode_affichee ?></h3>
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i>
                        Données mises à jour selon les filtres
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Total permanences -->
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-500 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100">Total permanences</p>
                                <p class="text-3xl font-bold" data-stat="total_permanences"><?= $stats['total_permanences'] ?: 0 ?></p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-calendar-check text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Total recettes -->
                    <div class="bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100">Total recettes</p>
                                <p class="text-3xl font-bold" data-stat="total_montant"><?= number_format($stats['total_montant'] ?: 0, 0, ',', ' ') ?> FCFA</p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-money-bill-wave text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Moyenne par acte -->
                    <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-100">Moyenne/acte</p>
                                <p class="text-3xl font-bold" data-stat="moyenne_montant"><?= number_format($stats['moyenne_montant'] ?: 0, 0, ',', ' ') ?> FCFA</p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-chart-line text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- En attente aujourd'hui -->
                    <div class="bg-gradient-to-r from-orange-500 to-red-500 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-orange-100">En attente</p>
                                <p class="text-3xl font-bold" data-stat="en_attente"><?= $stats['en_attente'] ?: 0 ?></p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-clock text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Détail des statuts -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                        <div>
                            <p class="text-sm text-gray-600">Validées</p>
                            <p class="text-2xl font-bold text-green-600" data-stat="validees"><?= $stats['validees'] ?: 0 ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Annulées</p>
                            <p class="text-2xl font-bold text-red-600" data-stat="annulees"><?= $stats['annulees'] ?: 0 ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">En attente</p>
                            <p class="text-2xl font-bold text-orange-600" data-stat="en_attente_detail"><?= $stats['en_attente'] ?: 0 ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Période affichée -->
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <p class="text-sm text-gray-600">Période affichée: <span class="font-semibold text-gray-900"><?= $periode_affichee ?></span></p>
            </div>


            <!-- Total par acte posé -->
            <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900" data-stat="total_acte_title">Total par Acte Posé - <?= $periode_affichee ?></h3>
                        <p class="text-sm text-gray-600 mt-1">Actes validés uniquement pour cette période</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="bg-indigo-100 p-3 rounded-lg">
                            <i class="fas fa-stethoscope text-indigo-600 text-xl"></i>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600">Types d'actes</p>
                            <p class="text-lg font-bold text-indigo-600"><?= count($total_par_acte) ?></p>
                        </div>
                    </div>
                </div>
                
                <div id="totalParActeContainer">
                <?php if (!empty($total_par_acte)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gradient-to-r from-indigo-50 to-purple-50">
                                <tr>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Acte</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Nombre</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Total Montant</th>
                                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">% du Total</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php 
                                $total_montant_global = array_sum(array_column($total_par_acte, 'total_montant'));
                                foreach ($total_par_acte as $acte): 
                                    $pourcentage = $total_montant_global > 0 ? ($acte['total_montant'] / $total_montant_global) * 100 : 0;
                                ?>
                                    <tr class="hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 transition-colors duration-200">
                                        <td class="px-6 py-4 text-sm text-gray-900 font-semibold">
                                            <div class="flex items-center">
                                                <div class="w-3 h-3 bg-indigo-500 rounded-full mr-3"></div>
                                                <?= htmlspecialchars($acte['nom_acte']) ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                <?= $acte['nb_fois'] ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900 font-bold">
                                            <?= number_format($acte['total_montant'], 0, ',', ' ') ?> FCFA
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            <div class="flex items-center">
                                                <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                    <div class="bg-indigo-500 h-2 rounded-full" style="width: <?= $pourcentage ?>%"></div>
                                                </div>
                                                <span class="text-xs text-gray-500"><?= number_format($pourcentage, 1) ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Résumé en bas -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="text-center">
                                <p class="text-sm text-gray-600">Total des actes</p>
                                <p class="text-2xl font-bold text-indigo-600"><?= array_sum(array_column($total_par_acte, 'nb_fois')) ?></p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-600">Total des recettes</p>
                                <p class="text-2xl font-bold text-green-600"><?= number_format($total_montant_global, 0, ',', ' ') ?> FCFA</p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-600">Moyenne par acte</p>
                                <p class="text-2xl font-bold text-purple-600">
                                    <?= $total_par_acte ? number_format($total_montant_global / array_sum(array_column($total_par_acte, 'nb_fois')), 0, ',', ' ') : 0 ?> FCFA
                                </p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-clipboard-list text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">Aucun acte validé enregistré pour cette période</p>
                    </div>
                <?php endif; ?>
                </div>
            </div>
            <!-- Formulaire de filtrage -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-bold text-gray-900">Filtres</h3>
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-filter mr-1"></i>
                        Les statistiques s'adaptent automatiquement
                    </div>
                </div>
                <form method="get" class="flex flex-col md:flex-row md:items-center gap-4">
                    <div>
                        <label for="date_du" class="block text-xs font-medium text-gray-600 mb-1">Du</label>
                        <input type="date" id="date_du" name="date_du" value="<?= htmlspecialchars($date_du) ?>" class="px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="date_au" class="block text-xs font-medium text-gray-600 mb-1">Au</label>
                        <input type="date" id="date_au" name="date_au" value="<?= htmlspecialchars($date_au) ?>" class="px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label for="statut" class="block text-xs font-medium text-gray-600 mb-1">Statut</label>
                        <select id="statut" name="statut" class="px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="">Tous les statuts</option>
                            <option value="en_attente" <?= $statut_filter === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="ok" <?= $statut_filter === 'ok' ? 'selected' : '' ?>>Validé</option>
                            <option value="annule" <?= $statut_filter === 'annule' ? 'selected' : '' ?>>Annulé</option>
                        </select>
                    </div>
                    <div class="self-end">
                        <button type="submit" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-2 rounded-lg font-semibold shadow hover:shadow-lg transition-all duration-300">
                            <i class="fas fa-search mr-2"></i>
                            Filtrer
                        </button>
                    </div>
                </form>
                <div class="mt-3 text-xs text-gray-500">
                    <i class="fas fa-info-circle mr-1"></i>
                    Par défaut : données d'aujourd'hui. Les statistiques et le total par acte se mettent à jour selon les filtres appliqués.
                </div>
            </div>
            <input type="text" id="searchInput" placeholder="Rechercher par nom, prénom (ex: sarah toure), acte, contact..." class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent w-full md:w-80 mb-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" id="permanenceTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nom</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Prénom</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Âge</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nationalité</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acte</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Heure</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Saisi par</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                        </tr>
                    </thead>
                    <tbody id="permanenceTableBody">
                        <?php foreach ($permanences as $permanence): ?>
                            <tr>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 font-semibold"><?= htmlspecialchars($permanence['nom_patient']) ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 font-semibold"><?= htmlspecialchars($permanence['prenom_patient']) ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($permanence['age']) ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($permanence['nationalite']) ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($permanence['nom_acte']) ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500"><?= number_format($permanence['montant_paye'], 0, ',', ' ') ?> FCFA</td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($permanence['contact']) ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500"><?= date('H:i', strtotime($permanence['created_at'])) ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($permanence['secretaire_nom']) ?> <?= htmlspecialchars($permanence['secretaire_prenom']) ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-xs">
                                    <?php if ($permanence['statut_final'] === 'ok'): ?>
                                        <span class="inline-block px-3 py-1 rounded-full font-semibold bg-green-100 text-green-800">OK</span>
                                    <?php elseif ($permanence['statut_final'] === 'annule'): ?>
                                        <span class="inline-block px-3 py-1 rounded-full font-semibold bg-red-100 text-red-800">Annulé</span>
                                    <?php else: ?>
                                        <span class="inline-block px-3 py-1 rounded-full font-semibold bg-yellow-100 text-yellow-800">En attente</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="flex justify-end mt-4">
                <nav id="paginationNav" class="inline-flex"></nav>
            </div>
        </main>
    </div>
    <script>
        // Variables globales
        let allPermanences = [];
        let currentStats = {};
        let currentTotalParActe = [];
        let currentPeriode = '';
        const rowsPerPage = 20;
        let currentPage = 1;
        let isLoading = false;

        // Fonction de logging pour debug
        function logDebug(message, data = null) {
            const timestamp = new Date().toLocaleTimeString();
            console.log(`[${timestamp}] DEBUG: ${message}`, data);
            
            // Afficher aussi dans une div de debug si elle existe
            const debugDiv = document.getElementById('debugLogs');
            if (debugDiv) {
                const logEntry = document.createElement('div');
                logEntry.className = 'text-xs text-gray-600 mb-1';
                logEntry.innerHTML = `<strong>[${timestamp}]</strong> ${message}`;
                if (data) {
                    logEntry.innerHTML += `<br><pre class="text-xs">${JSON.stringify(data, null, 2)}</pre>`;
                }
                debugDiv.appendChild(logEntry);
                
                // Garder seulement les 10 derniers logs
                while (debugDiv.children.length > 10) {
                    debugDiv.removeChild(debugDiv.firstChild);
                }
            }
        }

        // Fonction pour charger les données via AJAX
        async function loadPermanences() {
            if (isLoading) {
                logDebug('Chargement déjà en cours, ignoré');
                return;
            }
            
            isLoading = true;
            logDebug('Début du chargement des permanences');
            
            const form = document.querySelector('form');
            const formData = new FormData(form);
            const params = new URLSearchParams(formData);
            
            logDebug('Paramètres de filtrage:', Object.fromEntries(params));
            
            // Afficher un indicateur de chargement
            const tbody = document.getElementById('permanenceTableBody');
            tbody.innerHTML = '<tr><td colspan="10" class="text-center py-8"><div class="flex items-center justify-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-500"></div><span class="ml-2">Chargement...</span></div></td></tr>';
            
            try {
                logDebug('Envoi de la requête AJAX vers permanences_ajax.php');
                const response = await fetch(`permanences_ajax.php?${params.toString()}`);
                logDebug('Réponse reçue:', {
                    status: response.status,
                    statusText: response.statusText,
                    headers: Object.fromEntries(response.headers.entries())
                });
                
                const data = await response.json();
                logDebug('Données JSON reçues:', data);
                
                if (response.ok) {
                    logDebug('Requête réussie, mise à jour des données');
                    allPermanences = data.permanences || [];
                    currentStats = data.stats || {};
                    currentTotalParActe = data.total_par_acte || [];
                    currentPeriode = data.periode_affichee || '';
                    currentPage = 1;
                    
                    logDebug('Données mises à jour:', {
                        permanencesCount: allPermanences.length,
                        stats: currentStats,
                        totalParActeCount: currentTotalParActe.length,
                        periode: currentPeriode
                    });
                    
                    renderTable();
                    updateStats();
                    updateTotalParActe();
                    updatePeriode();
                } else {
                    logDebug('Erreur de réponse:', data.error);
                    console.error('Erreur:', data.error);
                    tbody.innerHTML = '<tr><td colspan="10" class="text-center py-8 text-red-500">Erreur de chargement: ' + (data.error || 'Erreur inconnue') + '</td></tr>';
                }
            } catch (error) {
                logDebug('Erreur AJAX:', error);
                console.error('Erreur AJAX:', error);
                tbody.innerHTML = '<tr><td colspan="10" class="text-center py-8 text-red-500">Erreur de connexion: ' + error.message + '</td></tr>';
            } finally {
                isLoading = false;
                logDebug('Chargement terminé');
            }
        }

        // Fonction pour rendre le tableau
        function renderTable() {
            logDebug('Rendu du tableau');
            const search = document.getElementById('searchInput').value.toLowerCase();
            logDebug('Terme de recherche:', search);
            
            let filteredPermanences = allPermanences.filter(permanence => {
                const permanenceText = Object.values(permanence).join(' ').toLowerCase();
                const searchTerms = search.split(' ').filter(term => term.length > 0);
                
                if (searchTerms.length > 1) {
                    return searchTerms.every(term => permanenceText.includes(term));
                } else {
                    return permanenceText.includes(search);
                }
            });
            
            logDebug('Permanences filtrées:', {
                total: allPermanences.length,
                filtered: filteredPermanences.length,
                search: search
            });
            
            const tbody = document.getElementById('permanenceTableBody');
            const startIndex = (currentPage - 1) * rowsPerPage;
            const endIndex = startIndex + rowsPerPage;
            const pagePermanences = filteredPermanences.slice(startIndex, endIndex);
            
            logDebug('Pagination:', {
                currentPage: currentPage,
                startIndex: startIndex,
                endIndex: endIndex,
                pagePermanences: pagePermanences.length
            });
            
            if (pagePermanences.length === 0) {
                tbody.innerHTML = '<tr><td colspan="10" class="text-center py-8 text-gray-500">Aucune permanence trouvée</td></tr>';
                logDebug('Aucune permanence à afficher');
            } else {
                tbody.innerHTML = pagePermanences.map(permanence => `
                    <tr>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 font-semibold">${permanence.nom || permanence.nom_patient || ''}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 font-semibold">${permanence.prenom || permanence.prenom_patient || ''}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">${permanence.age || ''}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">${permanence.nationalite || ''}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">${permanence.acte || permanence.nom_acte || ''}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">${permanence.montant || permanence.montant_paye || ''}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">${permanence.contact || ''}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">${permanence.heure || ''}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">${permanence.secretaire || ''}</td>
                        <td class="px-4 py-2 whitespace-nowrap text-xs">${permanence.statut || ''}</td>
                    </tr>
                `).join('');
                logDebug('Tableau rendu avec', pagePermanences.length, 'lignes');
            }
            
            renderPagination(filteredPermanences.length);
        }

        // Fonction pour mettre à jour les statistiques
        function updateStats() {
            logDebug('Mise à jour des statistiques:', currentStats);
            const stats = currentStats;
            
            // Mettre à jour les cartes de statistiques
            const elements = {
                total_permanences: document.querySelector('[data-stat="total_permanences"]'),
                total_montant: document.querySelector('[data-stat="total_montant"]'),
                moyenne_montant: document.querySelector('[data-stat="moyenne_montant"]'),
                en_attente: document.querySelector('[data-stat="en_attente"]'),
                validees: document.querySelector('[data-stat="validees"]'),
                annulees: document.querySelector('[data-stat="annulees"]'),
                en_attente_detail: document.querySelector('[data-stat="en_attente_detail"]')
            };
            
            // Vérifier que tous les éléments existent
            Object.entries(elements).forEach(([key, element]) => {
                if (!element) {
                    logDebug(`Élément manquant: ${key}`);
                }
            });
            
            if (elements.total_permanences) elements.total_permanences.textContent = stats.total_permanences || 0;
            if (elements.total_montant) elements.total_montant.textContent = (stats.total_montant ? (parseInt(stats.total_montant).toLocaleString('fr-FR') + ' FCFA') : '0 FCFA');
            if (elements.moyenne_montant) elements.moyenne_montant.textContent = (stats.moyenne_montant ? (parseInt(stats.moyenne_montant).toLocaleString('fr-FR') + ' FCFA') : '0 FCFA');
            if (elements.en_attente) elements.en_attente.textContent = stats.en_attente || 0;
            if (elements.validees) elements.validees.textContent = stats.validees || 0;
            if (elements.annulees) elements.annulees.textContent = stats.annulees || 0;
            if (elements.en_attente_detail) elements.en_attente_detail.textContent = stats.en_attente || 0;
            
            logDebug('Statistiques mises à jour');
        }

        // Fonction pour mettre à jour le total par acte
        function updateTotalParActe() {
            logDebug('Mise à jour du total par acte:', currentTotalParActe);
            const totalParActe = currentTotalParActe;
            const container = document.getElementById('totalParActeContainer');
            
            if (!container) {
                logDebug('Container totalParActeContainer non trouvé');
                return;
            }
            
            if (totalParActe.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-clipboard-list text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">Aucun acte validé enregistré pour cette période</p>
                    </div>
                `;
                logDebug('Aucun acte à afficher');
                return;
            }
            
            let totalMontantGlobal = 0;
            totalParActe.forEach(acte => {
                totalMontantGlobal += parseFloat(acte.total_montant);
            });
            
            logDebug('Total montant global:', totalMontantGlobal);
            
            let tableHTML = `
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gradient-to-r from-indigo-50 to-purple-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Acte</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Total Montant</th>
                                <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">% du Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
            `;
            
            totalParActe.forEach(acte => {
                const pourcentage = totalMontantGlobal > 0 ? (parseFloat(acte.total_montant) / totalMontantGlobal) * 100 : 0;
                tableHTML += `
                    <tr class="hover:bg-gradient-to-r hover:from-indigo-50 hover:to-purple-50 transition-colors duration-200">
                        <td class="px-6 py-4 text-sm text-gray-900 font-semibold">
                            <div class="flex items-center">
                                <div class="w-3 h-3 bg-indigo-500 rounded-full mr-3"></div>
                                ${acte.nom_acte}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                ${acte.nb_fois}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900 font-bold">
                            ${parseInt(acte.total_montant).toLocaleString('fr-FR')} FCFA
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <div class="flex items-center">
                                <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                    <div class="bg-indigo-500 h-2 rounded-full" style="width: ${pourcentage}%"></div>
                                </div>
                                <span class="text-xs text-gray-500">${pourcentage.toFixed(1)}%</span>
                            </div>
                        </td>
                    </tr>
                `;
            });
            
            tableHTML += `
                        </tbody>
                    </table>
                </div>
                
                <!-- Résumé en bas -->
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center">
                            <p class="text-sm text-gray-600">Total des actes</p>
                            <p class="text-2xl font-bold text-indigo-600">${totalParActe.reduce((sum, acte) => sum + parseInt(acte.nb_fois), 0)}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-gray-600">Total des recettes</p>
                            <p class="text-2xl font-bold text-green-600">${totalMontantGlobal.toLocaleString('fr-FR')} FCFA</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-gray-600">Moyenne par acte</p>
                            <p class="text-2xl font-bold text-purple-600">
                                ${totalParActe.length > 0 ? (totalMontantGlobal / totalParActe.reduce((sum, acte) => sum + parseInt(acte.nb_fois), 0)).toLocaleString('fr-FR') : 0} FCFA
                            </p>
                        </div>
                    </div>
                </div>
            `;
            
            container.innerHTML = tableHTML;
            logDebug('Total par acte mis à jour');
        }

        // Fonction pour mettre à jour la période affichée
        function updatePeriode() {
            logDebug('Mise à jour de la période:', currentPeriode);
            const periodeElement = document.getElementById('periodeAffichee');
            if (periodeElement) {
                periodeElement.textContent = currentPeriode;
            } else {
                logDebug('Élément periodeAffichee non trouvé');
            }
            
            // Mettre à jour aussi dans le titre des statistiques
            const statsTitle = document.querySelector('[data-stat="stats_title"]');
            if (statsTitle) {
                statsTitle.textContent = `Statistiques - ${currentPeriode}`;
            } else {
                logDebug('Élément stats_title non trouvé');
            }
            
            // Mettre à jour le titre du total par acte
            const totalActeTitle = document.querySelector('[data-stat="total_acte_title"]');
            if (totalActeTitle) {
                totalActeTitle.textContent = `Total par Acte Posé - ${currentPeriode}`;
            } else {
                logDebug('Élément total_acte_title non trouvé');
            }
        }

        // Fonction pour rendre la pagination
        function renderPagination(totalRows) {
            logDebug('Rendu de la pagination:', { totalRows, currentPage, rowsPerPage });
            const nav = document.getElementById('paginationNav');
            if (!nav) {
                logDebug('Élément paginationNav non trouvé');
                return;
            }
            
            nav.innerHTML = '';
            const totalPages = Math.ceil(totalRows / rowsPerPage);
            
            logDebug('Pages totales:', totalPages);
            
            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.textContent = i;
                btn.className = 'mx-1 px-3 py-1 rounded border ' + (i === currentPage ? 'bg-purple-500 text-white' : 'bg-white text-purple-600 border-purple-300');
                btn.onclick = () => { 
                    logDebug('Changement de page:', i);
                    currentPage = i; 
                    renderTable(); 
                };
                nav.appendChild(btn);
            }
        }

        // Événements
        document.addEventListener('DOMContentLoaded', function() {
            logDebug('DOM chargé, initialisation...');
            
            // Créer une div de debug si elle n'existe pas
            if (!document.getElementById('debugLogs')) {
                const debugDiv = document.createElement('div');
                debugDiv.id = 'debugLogs';
                debugDiv.className = 'fixed bottom-4 right-4 w-80 h-64 bg-black bg-opacity-75 text-white p-4 rounded-lg overflow-y-auto text-xs z-50';
                debugDiv.innerHTML = '<div class="font-bold mb-2">Logs de Debug</div>';
                document.body.appendChild(debugDiv);
            }
            
            // Charger les données initiales
            loadPermanences();
            
            // Gestionnaire pour le formulaire de filtrage
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    logDebug('Soumission du formulaire de filtrage');
                    loadPermanences();
                });
            } else {
                logDebug('Formulaire de filtrage non trouvé');
            }
            
            // Gestionnaire pour la recherche
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    logDebug('Recherche modifiée:', this.value);
                    currentPage = 1;
                    renderTable();
                });
            } else {
                logDebug('Champ de recherche non trouvé');
            }
            
            logDebug('Initialisation terminée');
        });
    </script>
</body>
</html> 