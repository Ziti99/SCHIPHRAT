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

// Filtres
$statut_filtre = $_GET['statut'] ?? 'tous';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$search = $_GET['search'] ?? '';

// Construction de la requête
$where_clauses = [];
$params = [];

if ($statut_filtre !== 'tous') {
    $where_clauses[] = "p.statut = ?";
    $params[] = $statut_filtre;
}

if ($date_debut) {
    $where_clauses[] = "DATE(cp.date_consultation) >= ?";
    $params[] = $date_debut;
}

if ($date_fin) {
    $where_clauses[] = "DATE(cp.date_consultation) <= ?";
    $params[] = $date_fin;
}

if ($search) {
    $where_clauses[] = "(pat.nom LIKE ? OR pat.prenom LIKE ? OR pat.telephone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

// Récupérer les consultations
$consultations = $db->fetchAll("
    SELECT 
        p.id as paiement_id,
        p.montant_total,
        p.montant_paye,
        p.montant_restant,
        p.statut,
        p.mode_paiement,
        p.date_paiement,
        cp.id as consultation_id,
        cp.date_consultation,
        pat.id as patiente_id,
        pat.nom,
        pat.prenom,
        pat.telephone,
        COUNT(ca.id) as nb_actes,
        GROUP_CONCAT(ap.nom_acte SEPARATOR ', ') as actes_liste
    FROM paiements p
    INNER JOIN patientes pat ON p.patiente_id = pat.id
    INNER JOIN consultations_prenatales cp ON p.consultation_id = cp.id
    LEFT JOIN consultation_actes ca ON cp.id = ca.consultation_id
    LEFT JOIN actes_poses ap ON ca.acte_id = ap.id
    $where_sql
    GROUP BY p.id
    ORDER BY cp.date_consultation DESC
", $params);

// Statistiques
$stats = $db->fetch("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'en_attente' THEN 1 ELSE 0 END) as en_attente,
        SUM(CASE WHEN statut = 'paye_partiel' THEN 1 ELSE 0 END) as partiel,
        SUM(CASE WHEN statut = 'paye_total' THEN 1 ELSE 0 END) as complet,
        SUM(montant_restant) as montant_restant_total
    FROM paiements p
    INNER JOIN consultations_prenatales cp ON p.consultation_id = cp.id
    $where_sql
", $params);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Consultations - Caissière</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="flex">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Contenu principal -->
        <div class="flex-1">
            <!-- Navigation -->
            <?php include 'includes/navbar.php'; ?>

            <div class="p-4 sm:p-6 lg:p-8">
                <!-- En-tête -->
                <div class="mb-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">
                                <i class="fas fa-list-alt text-green-600 mr-3"></i>
                                Consultations et Paiements
                            </h1>
                            <p class="text-gray-600 text-sm sm:text-base">Gérer les paiements des consultations et actes médicaux</p>
                        </div>
                        <!-- Indicateur de connexion en temps réel -->
                        <div id="realtime-indicator" class="flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-lg self-start sm:self-auto">
                            <span class="w-2 h-2 bg-gray-400 rounded-full animate-pulse"></span>
                            <span class="text-sm text-gray-600">Connexion...</span>
                        </div>
                    </div>
                </div>

                <!-- Statistiques rapides -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 mb-6" id="stats-container">
                    <div class="bg-gradient-to-br from-gray-50 to-white rounded-xl shadow-md hover:shadow-lg transition-shadow p-4 border border-gray-100">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs sm:text-sm text-gray-600 font-medium">Total</p>
                            <i class="fas fa-list text-gray-400 text-sm"></i>
                        </div>
                        <p class="text-xl sm:text-2xl font-bold text-gray-900" id="stat-total"><?php echo $stats['total']; ?></p>
                    </div>
                    <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-xl shadow-md hover:shadow-lg transition-shadow p-4 border border-red-200">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs sm:text-sm text-red-700 font-medium">En Attente</p>
                            <i class="fas fa-clock text-red-400 text-sm"></i>
                        </div>
                        <p class="text-xl sm:text-2xl font-bold text-red-800" id="stat-en-attente"><?php echo $stats['en_attente']; ?></p>
                    </div>
                    <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl shadow-md hover:shadow-lg transition-shadow p-4 border border-yellow-200">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs sm:text-sm text-yellow-700 font-medium">Partiel</p>
                            <i class="fas fa-percent text-yellow-400 text-sm"></i>
                        </div>
                        <p class="text-xl sm:text-2xl font-bold text-yellow-800" id="stat-partiel"><?php echo $stats['partiel']; ?></p>
                    </div>
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl shadow-md hover:shadow-lg transition-shadow p-4 border border-green-200">
                        <div class="flex items-center justify-between mb-2">
                            <p class="text-xs sm:text-sm text-green-700 font-medium">Payé</p>
                            <i class="fas fa-check-circle text-green-400 text-sm"></i>
                        </div>
                        <p class="text-xl sm:text-2xl font-bold text-green-800" id="stat-complet"><?php echo $stats['complet']; ?></p>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6 mb-6 border border-gray-100">
                    <div class="flex items-center gap-2 mb-4">
                        <i class="fas fa-filter text-purple-600"></i>
                        <h2 class="text-lg font-semibold text-gray-900">Filtres de recherche</h2>
                    </div>
                    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5">
                                <i class="fas fa-tag mr-1 text-gray-400"></i>Statut
                            </label>
                            <select name="statut" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors text-sm">
                                <option value="tous" <?php echo $statut_filtre === 'tous' ? 'selected' : ''; ?>>Tous</option>
                                <option value="en_attente" <?php echo $statut_filtre === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                <option value="paye_partiel" <?php echo $statut_filtre === 'paye_partiel' ? 'selected' : ''; ?>>Partiel</option>
                                <option value="paye_total" <?php echo $statut_filtre === 'paye_total' ? 'selected' : ''; ?>>Payé</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5">
                                <i class="fas fa-calendar-alt mr-1 text-gray-400"></i>Date Début
                            </label>
                            <input type="date" name="date_debut" value="<?php echo htmlspecialchars($date_debut); ?>" 
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors text-sm">
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5">
                                <i class="fas fa-calendar-check mr-1 text-gray-400"></i>Date Fin
                            </label>
                            <input type="date" name="date_fin" value="<?php echo htmlspecialchars($date_fin); ?>" 
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors text-sm">
                        </div>
                        <div>
                            <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5">
                                <i class="fas fa-search mr-1 text-gray-400"></i>Rechercher
                            </label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Nom, téléphone..." 
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors text-sm">
                        </div>
                        <div class="flex items-end gap-2 sm:flex-col lg:flex-row">
                            <button type="submit" class="flex-1 bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-2.5 rounded-lg hover:from-green-600 hover:to-green-700 transition-all shadow-md hover:shadow-lg font-medium text-sm">
                                <i class="fas fa-search mr-2"></i><span class="hidden sm:inline">Filtrer</span><span class="sm:hidden">Rechercher</span>
                            </button>
                            <a href="caissiere_consultations.php" class="bg-gray-500 text-white px-4 py-2.5 rounded-lg hover:bg-gray-600 transition-colors shadow-md hover:shadow-lg" title="Réinitialiser">
                                <i class="fas fa-redo"></i>
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Liste des consultations -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
                                <tr>
                                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        <i class="fas fa-user mr-1 text-gray-400"></i>Patiente
                                    </th>
                                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider hidden md:table-cell">
                                        <i class="fas fa-calendar mr-1 text-gray-400"></i>Date
                                    </th>
                                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider hidden lg:table-cell">
                                        <i class="fas fa-stethoscope mr-1 text-gray-400"></i>Actes
                                    </th>
                                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        <i class="fas fa-money-bill-wave mr-1 text-gray-400"></i>Total
                                    </th>
                                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider hidden sm:table-cell">
                                        <i class="fas fa-check-circle mr-1 text-gray-400"></i>Payé
                                    </th>
                                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        <i class="fas fa-exclamation-circle mr-1 text-gray-400"></i>Reste
                                    </th>
                                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        <i class="fas fa-info-circle mr-1 text-gray-400"></i>Statut
                                    </th>
                                    <th class="px-4 sm:px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                        <i class="fas fa-cog mr-1 text-gray-400"></i>Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="consultations-tbody" class="bg-white divide-y divide-gray-200">
                                <?php if (empty($consultations)): ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-12 text-center">
                                            <div class="flex flex-col items-center justify-center">
                                                <div class="bg-gray-100 rounded-full p-6 mb-4">
                                                    <i class="fas fa-inbox text-5xl text-gray-400"></i>
                                                </div>
                                                <p class="text-lg font-semibold text-gray-700 mb-1">Aucune consultation trouvée</p>
                                                <p class="text-sm text-gray-500">Essayez de modifier vos filtres de recherche</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($consultations as $c): ?>
                                        <tr class="hover:bg-gray-50 transition-colors <?php echo $c['statut'] === 'en_attente' ? 'bg-yellow-50/60' : ''; ?> <?php echo $c['statut'] === 'en_attente' ? 'border-l-4 border-yellow-400' : ''; ?>">
                                            <td class="px-4 sm:px-6 py-4">
                                                <div>
                                                    <p class="font-semibold text-gray-900 text-sm sm:text-base">
                                                        <?php echo htmlspecialchars($c['prenom'] . ' ' . $c['nom']); ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        <i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($c['telephone'] ?? '-'); ?>
                                                    </p>
                                                    <p class="text-xs text-gray-400 mt-1 md:hidden">
                                                        <i class="fas fa-calendar mr-1"></i><?php echo date('d/m/Y', strtotime($c['date_consultation'])); ?>
                                                    </p>
                                                </div>
                                            </td>
                                            <td class="px-4 sm:px-6 py-4 text-sm text-gray-600 hidden md:table-cell">
                                                <div>
                                                    <p><?php echo date('d/m/Y', strtotime($c['date_consultation'])); ?></p>
                                                    <p class="text-xs text-gray-400"><?php echo date('H:i', strtotime($c['date_consultation'])); ?></p>
                                                </div>
                                            </td>
                                            <td class="px-4 sm:px-6 py-4 hidden lg:table-cell">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700" title="<?php echo htmlspecialchars($c['actes_liste'] ?? 'Aucun'); ?>">
                                                    <i class="fas fa-stethoscope mr-1.5"></i><?php echo $c['nb_actes']; ?> acte(s)
                                                </span>
                                            </td>
                                            <td class="px-4 sm:px-6 py-4">
                                                <p class="text-sm sm:text-base font-bold text-gray-900">
                                                    <?php echo number_format($c['montant_total'], 0, ',', ' '); ?> <span class="text-xs font-normal text-gray-500">FCFA</span>
                                                </p>
                                            </td>
                                            <td class="px-4 sm:px-6 py-4 text-sm font-semibold text-green-600 hidden sm:table-cell">
                                                <?php echo number_format($c['montant_paye'], 0, ',', ' '); ?> <span class="text-xs font-normal">FCFA</span>
                                            </td>
                                            <td class="px-4 sm:px-6 py-4">
                                                <p class="text-sm sm:text-base font-bold text-red-600">
                                                    <?php echo number_format($c['montant_restant'], 0, ',', ' '); ?> <span class="text-xs font-normal text-gray-500">FCFA</span>
                                                </p>
                                            </td>
                                            <td class="px-4 sm:px-6 py-4">
                                                <?php
                                                $badge_colors = [
                                                    'en_attente' => 'bg-red-100 text-red-800 border-red-200',
                                                    'paye_partiel' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                                    'paye_total' => 'bg-green-100 text-green-800 border-green-200'
                                                ];
                                                $badge_labels = [
                                                    'en_attente' => 'Non payé',
                                                    'paye_partiel' => 'Partiel',
                                                    'paye_total' => 'Payé'
                                                ];
                                                ?>
                                                <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full border <?php echo $badge_colors[$c['statut']] ?? 'bg-gray-100 text-gray-800 border-gray-200'; ?>">
                                                    <?php echo $badge_labels[$c['statut']] ?? $c['statut']; ?>
                                                </span>
                                            </td>
                                            <td class="px-4 sm:px-6 py-4">
                                                <div class="flex flex-wrap gap-1.5 sm:gap-2">
                                                    <a href="caissiere_patiente_detail.php?id=<?php echo $c['patiente_id']; ?>" 
                                                       class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors text-xs sm:text-sm font-medium shadow-sm hover:shadow"
                                                       title="Voir détails patiente" aria-label="Voir détails patiente">
                                                        <i class="fas fa-eye"></i><span class="hidden sm:inline">Détails</span>
                                                    </a>
                                                    <?php if ($c['statut'] !== 'paye_total'): ?>
                                                        <a href="caissiere_valider_paiement.php?id=<?php echo $c['paiement_id']; ?>" 
                                                           class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-lg bg-gradient-to-r from-green-600 to-green-700 text-white hover:from-green-700 hover:to-green-800 transition-all text-xs sm:text-sm font-semibold shadow-md hover:shadow-lg"
                                                           title="Valider paiement"
                                                           aria-label="Valider paiement"
                                                           onclick="return confirm('Encaisser <?php echo number_format($c['montant_restant'], 0, ',', ' '); ?> FCFA pour <?php echo htmlspecialchars($c['prenom'] . ' ' . $c['nom']); ?> ?');">
                                                            <i class="fas fa-check-circle"></i><span class="hidden sm:inline">Encaisser</span>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="caissiere_recu.php?id=<?php echo $c['paiement_id']; ?>" 
                                                           class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-1.5 sm:py-2 rounded-lg bg-gradient-to-r from-purple-600 to-purple-700 text-white hover:from-purple-700 hover:to-purple-800 transition-all text-xs sm:text-sm font-semibold shadow-md hover:shadow-lg"
                                                           title="Imprimer reçu" aria-label="Imprimer reçu">
                                                            <i class="fas fa-file-pdf"></i><span class="hidden sm:inline">Reçu</span>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Résumé montant restant -->
                <div id="montant-restant-container" class="mt-6 <?php echo $stats['montant_restant_total'] > 0 ? '' : 'hidden'; ?> bg-gradient-to-r from-orange-50 to-orange-100 border-2 border-orange-300 rounded-xl p-4 sm:p-6 shadow-lg">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div>
                            <p class="text-sm sm:text-base text-orange-800 font-semibold mb-1">
                                <i class="fas fa-exclamation-triangle mr-2"></i>Montant Total Restant à Encaisser
                            </p>
                            <p class="text-2xl sm:text-3xl font-bold text-orange-900" id="montant-restant-total">
                                <?php echo number_format($stats['montant_restant_total'], 0, ',', ' '); ?> <span class="text-lg sm:text-xl font-normal">FCFA</span>
                            </p>
                        </div>
                        <div class="bg-orange-200 rounded-full p-4 sm:p-6">
                            <i class="fas fa-exclamation-triangle text-3xl sm:text-5xl text-orange-600"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
<script>
// ============================================
// MISE À JOUR EN TEMPS RÉEL DES CONSULTATIONS
// ============================================

(function() {
    'use strict';
    
    // Configuration
    const UPDATE_INTERVAL = 5000; // Vérifier toutes les 5 secondes (polling)
    const DEBUG_MODE = window.location.search.includes('debug=1') || localStorage.getItem('realtime_debug') === 'true';
    let lastCheckTime = Math.floor(Date.now() / 1000);
    let lastConsultationId = <?php 
        if (!empty($consultations) && is_array($consultations)) {
            $ids = array_column($consultations, 'consultation_id');
            echo !empty($ids) ? max($ids) : 0;
        } else {
            echo 0;
        }
    ?>;
    let updateTimer = null;
    let isUpdating = false;
    let updateCount = 0;
    let errorCount = 0;
    let unreadNotifications = [];
    let allNotifications = [];
    
    // Fonction de log de debug
    function debugLog(message, data = null) {
        if (!DEBUG_MODE) return;
        const timestamp = new Date().toLocaleTimeString('fr-FR');
        const logMessage = `[${timestamp}] [REALTIME] ${message}`;
        console.log(logMessage, data || '');
    }
    
    // Log initial
    debugLog('Initialisation du système de mise à jour en temps réel', {
        UPDATE_INTERVAL,
        lastConsultationId,
        DEBUG_MODE,
        userAgent: navigator.userAgent
    });
    
    // Éléments DOM
    const tbody = document.getElementById('consultations-tbody');
    const realtimeIndicator = document.getElementById('realtime-indicator');
    const statsContainer = document.getElementById('stats-container');
    const notificationBell = document.getElementById('notification-bell');
    const notificationBadge = document.getElementById('notification-badge');
    const notificationCount = document.getElementById('notification-count');
    const notificationDropdown = document.getElementById('notification-dropdown');
    const notificationList = document.getElementById('notification-list');
    const markAllReadBtn = document.getElementById('mark-all-read');
    
    // Charger les notifications depuis le localStorage
    function loadNotifications() {
        try {
            const saved = localStorage.getItem('caissiere_notifications');
            if (saved) {
                allNotifications = JSON.parse(saved);
                unreadNotifications = allNotifications.filter(n => !n.read);
                updateNotificationUI();
            }
        } catch (e) {
            console.error('Erreur lors du chargement des notifications:', e);
        }
    }
    
    // Sauvegarder les notifications dans le localStorage
    function saveNotifications() {
        try {
            localStorage.setItem('caissiere_notifications', JSON.stringify(allNotifications));
        } catch (e) {
            console.error('Erreur lors de la sauvegarde des notifications:', e);
        }
    }
    
    // Ajouter une notification
    function addNotification(consultation) {
        const notification = {
            id: Date.now() + Math.random(),
            type: 'new_consultation',
            title: 'Nouvelle consultation',
            message: `${consultation.prenom} ${consultation.nom} - ${formatMontant(consultation.montant_restant)} FCFA à encaisser`,
            consultation_id: consultation.consultation_id,
            paiement_id: consultation.paiement_id,
            patiente_id: consultation.patiente_id,
            timestamp: new Date().toISOString(),
            read: false
        };
        
        // Vérifier si cette consultation n'a pas déjà été notifiée
        const alreadyNotified = allNotifications.some(n => 
            n.consultation_id === consultation.consultation_id && n.type === 'new_consultation'
        );
        
        if (!alreadyNotified) {
            allNotifications.unshift(notification);
            unreadNotifications.unshift(notification);
            
            // Garder seulement les 50 dernières notifications
            if (allNotifications.length > 50) {
                allNotifications = allNotifications.slice(0, 50);
            }
            
            saveNotifications();
            updateNotificationUI();
            
            // Jouer un son de notification (optionnel)
            playNotificationSound();
            
            // Animation de la cloche
            if (notificationBell) {
                notificationBell.classList.add('animate-pulse');
                setTimeout(() => {
                    notificationBell.classList.remove('animate-pulse');
                }, 1000);
            }
            
            debugLog('Notification ajoutée', notification);
        }
    }
    
    // Jouer un son de notification
    function playNotificationSound() {
        try {
            // Créer un son court avec l'API Web Audio
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + 0.2);
        } catch (e) {
            // Ignorer les erreurs audio (navigateur ne supporte pas)
            debugLog('Impossible de jouer le son de notification', e);
        }
    }
    
    // Mettre à jour l'interface des notifications
    function updateNotificationUI() {
        // Mettre à jour le badge
        if (notificationBadge && notificationCount) {
            const unreadCount = unreadNotifications.length;
            if (unreadCount > 0) {
                notificationBadge.classList.remove('hidden');
                notificationCount.textContent = unreadCount > 99 ? '99+' : unreadCount;
            } else {
                notificationBadge.classList.add('hidden');
            }
        }
        
        // Mettre à jour la liste des notifications
        if (notificationList) {
            if (allNotifications.length === 0) {
                notificationList.innerHTML = `
                    <div class="p-4 text-center text-gray-500 text-sm">
                        <i class="fas fa-bell-slash text-2xl mb-2 text-gray-300"></i>
                        <p>Aucune notification</p>
                    </div>
                `;
            } else {
                notificationList.innerHTML = allNotifications.map(notif => {
                    const date = new Date(notif.timestamp);
                    const timeStr = date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
                    const dateStr = date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
                    const unreadClass = notif.read ? '' : 'bg-purple-50 border-l-4 border-purple-500';
                    
                    return `
                        <div class="p-3 sm:p-4 hover:bg-gray-50 cursor-pointer ${unreadClass}" data-notification-id="${notif.id}">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <i class="fas fa-${notif.type === 'new_consultation' ? 'stethoscope' : 'bell'} text-purple-600 text-sm sm:text-base"></i>
                                        <h4 class="font-semibold text-gray-900 text-xs sm:text-sm truncate">${notif.title}</h4>
                                        ${!notif.read ? '<span class="w-2 h-2 bg-purple-500 rounded-full flex-shrink-0"></span>' : ''}
                                    </div>
                                    <p class="text-xs sm:text-sm text-gray-600 mb-1 sm:mb-2 break-words">${notif.message}</p>
                                    <p class="text-[10px] sm:text-xs text-gray-400">${dateStr} à ${timeStr}</p>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
                
                // Ajouter les event listeners pour les notifications
                notificationList.querySelectorAll('[data-notification-id]').forEach(el => {
                    el.addEventListener('click', () => {
                        const notifId = el.getAttribute('data-notification-id');
                        markAsRead(notifId);
                        
                        // Rediriger vers la consultation si c'est une nouvelle consultation
                        const notif = allNotifications.find(n => n.id == notifId);
                        if (notif && notif.type === 'new_consultation' && notif.paiement_id) {
                            window.location.href = `caissiere_valider_paiement.php?id=${notif.paiement_id}`;
                        }
                    });
                });
            }
        }
    }
    
    // Marquer une notification comme lue
    function markAsRead(notifId) {
        const notif = allNotifications.find(n => n.id == notifId);
        if (notif && !notif.read) {
            notif.read = true;
            unreadNotifications = unreadNotifications.filter(n => n.id != notifId);
            saveNotifications();
            updateNotificationUI();
        }
    }
    
    // Marquer toutes les notifications comme lues
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            allNotifications.forEach(n => n.read = true);
            unreadNotifications = [];
            saveNotifications();
            updateNotificationUI();
        });
    }
    
    // Toggle du dropdown
    if (notificationBell) {
        notificationBell.addEventListener('click', (e) => {
            e.stopPropagation();
            if (notificationDropdown) {
                const isHidden = notificationDropdown.classList.contains('hidden');
                notificationDropdown.classList.toggle('hidden');
                
                // Sur mobile, ajuster la position si nécessaire
                if (!isHidden && window.innerWidth < 640) {
                    const rect = notificationBell.getBoundingClientRect();
                    notificationDropdown.style.right = '0.5rem';
                    notificationDropdown.style.top = (rect.bottom + 8) + 'px';
                }
            }
        });
    }
    
    // Fermer le dropdown en cliquant ailleurs
    document.addEventListener('click', (e) => {
        if (notificationDropdown && !notificationDropdown.contains(e.target) && !notificationBell.contains(e.target)) {
            notificationDropdown.classList.add('hidden');
        }
    });
    
    // Fermer le dropdown au scroll sur mobile
    if (window.innerWidth < 640) {
        window.addEventListener('scroll', () => {
            if (notificationDropdown && !notificationDropdown.classList.contains('hidden')) {
                notificationDropdown.classList.add('hidden');
            }
        }, { passive: true });
    }
    
    // Fonction pour formater les montants
    function formatMontant(montant) {
        return new Intl.NumberFormat('fr-FR').format(montant);
    }
    
    // Charger les notifications au démarrage
    loadNotifications();
    
    // Fonction pour formater les dates
    function formatDate(dateString) {
        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        const hours = String(date.getHours()).padStart(2, '0');
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${day}/${month}/${year} ${hours}:${minutes}`;
    }
    
    // Fonction pour obtenir le badge de statut
    function getStatutBadge(statut) {
        const badgeColors = {
            'en_attente': 'bg-red-100 text-red-800',
            'paye_partiel': 'bg-yellow-100 text-yellow-800',
            'paye_total': 'bg-green-100 text-green-800'
        };
        const badgeLabels = {
            'en_attente': 'Non payé',
            'paye_partiel': 'Partiel',
            'paye_total': 'Payé'
        };
        const color = badgeColors[statut] || 'bg-gray-100 text-gray-800';
        const label = badgeLabels[statut] || statut;
        return `<span class="px-2 py-1 text-xs rounded-full ${color}">${label}</span>`;
    }
    
    // Fonction pour créer une ligne de consultation
    function createConsultationRow(c) {
        const rowClass = c.statut === 'en_attente' 
            ? 'hover:bg-gray-50 bg-yellow-50/60 border-l-4 border-yellow-400' 
            : 'hover:bg-gray-50';
        
        const actionsHtml = c.statut !== 'paye_total'
            ? `<a href="caissiere_valider_paiement.php?id=${c.paiement_id}" 
                   class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-green-600 text-white hover:bg-green-700 text-sm font-semibold"
                   title="Valider paiement"
                   onclick="return confirm('Encaisser ${formatMontant(c.montant_restant)} FCFA pour ${c.prenom} ${c.nom} ?');">
                    <i class="fas fa-check-circle"></i><span class="hidden sm:inline">Encaisser</span>
                </a>`
            : `<a href="caissiere_recu.php?id=${c.paiement_id}" 
                   class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-purple-600 text-white hover:bg-purple-700 text-sm font-semibold"
                   title="Imprimer reçu">
                    <i class="fas fa-file-pdf"></i><span class="hidden sm:inline">Reçu</span>
                </a>`;
        
        return `
            <tr class="${rowClass}" data-consultation-id="${c.consultation_id}">
                <td class="px-6 py-4">
                    <div>
                        <p class="font-semibold text-gray-900">${c.prenom} ${c.nom}</p>
                        <p class="text-xs text-gray-500">
                            <i class="fas fa-phone mr-1"></i>${c.telephone || '-'}
                        </p>
                    </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">${formatDate(c.date_consultation)}</td>
                <td class="px-6 py-4">
                    <span class="text-sm text-gray-600" title="${c.actes_liste || 'Aucun'}">
                        <i class="fas fa-stethoscope mr-1"></i>${c.nb_actes} acte(s)
                    </span>
                </td>
                <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                    ${formatMontant(c.montant_total)} FCFA
                </td>
                <td class="px-6 py-4 text-sm font-semibold text-green-600">
                    ${formatMontant(c.montant_paye)} FCFA
                </td>
                <td class="px-6 py-4 text-sm font-semibold text-red-600">
                    ${formatMontant(c.montant_restant)} FCFA
                </td>
                <td class="px-6 py-4">${getStatutBadge(c.statut)}</td>
                <td class="px-6 py-4">
                    <div class="flex flex-wrap gap-2">
                        <a href="caissiere_patiente_detail.php?id=${c.patiente_id}" 
                           class="inline-flex items-center gap-2 px-3 py-2 rounded-md bg-blue-50 text-blue-700 hover:bg-blue-100 text-sm font-medium"
                           title="Voir détails patiente">
                            <i class="fas fa-eye"></i><span class="hidden sm:inline">Détails</span>
                        </a>
                        ${actionsHtml}
                    </div>
                </td>
            </tr>
        `;
    }
    
    // Fonction pour mettre à jour l'indicateur de connexion
    function updateConnectionIndicator(connected) {
        if (!realtimeIndicator) return;
        
        if (connected) {
            realtimeIndicator.innerHTML = `
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                <span class="text-sm text-green-700 font-medium">En temps réel</span>
            `;
            realtimeIndicator.className = 'flex items-center gap-2 px-4 py-2 bg-green-50 border border-green-200 rounded-lg self-start sm:self-auto';
        } else {
            realtimeIndicator.innerHTML = `
                <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                <span class="text-sm text-red-700 font-medium">Déconnecté</span>
            `;
            realtimeIndicator.className = 'flex items-center gap-2 px-4 py-2 bg-red-50 border border-red-200 rounded-lg self-start sm:self-auto';
        }
    }
    
    // Fonction pour mettre à jour les statistiques
    function updateStats(stats) {
        const statTotal = document.getElementById('stat-total');
        const statEnAttente = document.getElementById('stat-en-attente');
        const statPartiel = document.getElementById('stat-partiel');
        const statComplet = document.getElementById('stat-complet');
        const montantRestantTotal = document.getElementById('montant-restant-total');
        const montantRestantContainer = document.getElementById('montant-restant-container');
        
        if (statTotal) statTotal.textContent = stats.total || 0;
        if (statEnAttente) statEnAttente.textContent = stats.en_attente || 0;
        if (statPartiel) statPartiel.textContent = stats.partiel || 0;
        if (statComplet) statComplet.textContent = stats.complet || 0;
        
        if (montantRestantTotal && stats.montant_restant_total) {
            montantRestantTotal.textContent = formatMontant(stats.montant_restant_total) + ' FCFA';
        }
        
        if (montantRestantContainer) {
            if (stats.montant_restant_total > 0) {
                montantRestantContainer.classList.remove('hidden');
            } else {
                montantRestantContainer.classList.add('hidden');
            }
        }
    }
    
    // Fonction pour récupérer les paramètres de filtre de l'URL
    function getFilterParams() {
        const params = new URLSearchParams(window.location.search);
        return {
            statut: params.get('statut') || 'tous',
            date_debut: params.get('date_debut') || '',
            date_fin: params.get('date_fin') || '',
            search: params.get('search') || ''
        };
    }
    
    // Fonction pour actualiser la liste des consultations
    async function refreshConsultations() {
        if (isUpdating) {
            debugLog('Mise à jour déjà en cours, ignorée');
            return;
        }
        isUpdating = true;
        updateCount++;
        const requestStartTime = performance.now();
        
        try {
            const filters = getFilterParams();
            const params = new URLSearchParams(filters);
            params.append('last_check', lastCheckTime);
            params.append('last_consultation_id', lastConsultationId);
            if (DEBUG_MODE) {
                params.append('debug', '1');
            }
            
            const url = `api/get_consultations.php?${params.toString()}`;
            debugLog(`Requête #${updateCount} envoyée`, {
                url,
                lastCheckTime,
                lastConsultationId,
                filters
            });
            
            const response = await fetch(url);
            const requestTime = Math.round(performance.now() - requestStartTime);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            debugLog(`Réponse reçue (#${updateCount})`, {
                success: data.success,
                consultations_count: data.consultations?.length || 0,
                new_consultations_count: data.new_consultations_count || 0,
                request_time_ms: requestTime,
                debug: data.debug || null
            });
            
            if (data.success) {
                // Mettre à jour les statistiques
                updateStats(data.stats);
                debugLog('Statistiques mises à jour', data.stats);
                
                // Vérifier s'il y a de nouvelles consultations
                if (data.consultations && data.consultations.length > 0) {
                    const newConsultations = data.consultations.filter(c => 
                        c.consultation_id > lastConsultationId
                    );
                    
                    // Mettre à jour le dernier ID
                    const maxId = Math.max(...data.consultations.map(c => c.consultation_id));
                    const hasNewConsultations = newConsultations.length > 0 || maxId > lastConsultationId;
                    
                    debugLog('Analyse des consultations', {
                        total_consultations: data.consultations.length,
                        new_consultations: newConsultations.length,
                        last_consultation_id_before: lastConsultationId,
                        max_id: maxId,
                        has_new: hasNewConsultations
                    });
                    
                    if (hasNewConsultations) {
                        const oldLastId = lastConsultationId;
                        lastConsultationId = maxId;
                        
                        debugLog('Nouvelles consultations détectées, mise à jour du tableau', {
                            old_last_id: oldLastId,
                            new_last_id: lastConsultationId,
                            new_consultations_ids: newConsultations.map(c => c.consultation_id)
                        });
                        
                        // Reconstruire le tableau avec toutes les consultations
                        if (tbody) {
                            const updateStartTime = performance.now();
                            tbody.innerHTML = data.consultations.map(c => createConsultationRow(c)).join('');
                            const updateTime = Math.round(performance.now() - updateStartTime);
                            debugLog('Tableau mis à jour', {
                                rows_count: data.consultations.length,
                                update_time_ms: updateTime
                            });
                        }
                        
                        // Afficher une notification discrète si de nouvelles consultations
                        if (newConsultations.length > 0) {
                            // Ajouter chaque nouvelle consultation comme notification
                            newConsultations.forEach(consultation => {
                                addNotification(consultation);
                            });
                            
                            showNotification(`${newConsultations.length} nouvelle(s) consultation(s) détectée(s)`, 'success');
                            debugLog('Notification affichée', {
                                count: newConsultations.length
                            });
                        }
                        
                        updateConnectionIndicator(true);
                    } else {
                        debugLog('Aucune nouvelle consultation');
                    }
                } else {
                    // Aucune consultation - mettre à jour l'affichage
                    debugLog('Aucune consultation trouvée');
                    if (tbody) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="bg-gray-100 rounded-full p-6 mb-4">
                                            <i class="fas fa-inbox text-5xl text-gray-400"></i>
                                        </div>
                                        <p class="text-lg font-semibold text-gray-700 mb-1">Aucune consultation trouvée</p>
                                        <p class="text-sm text-gray-500">Essayez de modifier vos filtres de recherche</p>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }
                }
                
                lastCheckTime = Math.floor(Date.now() / 1000);
                updateConnectionIndicator(true);
                errorCount = 0; // Réinitialiser le compteur d'erreurs en cas de succès
            } else {
                throw new Error(data.error || 'Réponse invalide du serveur');
            }
        } catch (error) {
            errorCount++;
            const errorMessage = error.message || 'Erreur inconnue';
            console.error('[REALTIME] Erreur lors de la mise à jour:', error);
            debugLog('ERREUR lors de la mise à jour', {
                error: errorMessage,
                error_count: errorCount,
                stack: error.stack
            });
            updateConnectionIndicator(false);
            
            // Afficher une notification d'erreur après plusieurs échecs
            if (errorCount >= 3) {
                showNotification('Problème de connexion. Vérifiez votre connexion internet.', 'error');
            }
        } finally {
            isUpdating = false;
            const totalTime = Math.round(performance.now() - requestStartTime);
            debugLog(`Mise à jour #${updateCount} terminée`, {
                total_time_ms: totalTime,
                success: errorCount === 0
            });
        }
    }
    
    // Fonction pour afficher une notification
    function showNotification(message, type = 'info') {
        // Créer une notification toast simple
        const notification = document.createElement('div');
        let bgColor = 'bg-blue-500';
        let icon = 'fa-info-circle';
        
        if (type === 'success') {
            bgColor = 'bg-green-500';
            icon = 'fa-check-circle';
        } else if (type === 'error') {
            bgColor = 'bg-red-500';
            icon = 'fa-exclamation-circle';
        }
        
        notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg ${bgColor} text-white`;
        notification.innerHTML = `
            <div class="flex items-center gap-2">
                <i class="fas ${icon}"></i>
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(notification);
        
        // Supprimer après 3 secondes (5 secondes pour les erreurs)
        const duration = type === 'error' ? 5000 : 3000;
        setTimeout(() => {
            notification.style.transition = 'opacity 0.3s';
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, duration);
    }
    
    // Démarrer la vérification périodique
    function startAutoRefresh() {
        debugLog('Démarrage de la vérification automatique', {
            first_check_delay: 2000,
            interval: UPDATE_INTERVAL
        });
        
        // Première vérification après 2 secondes
        setTimeout(() => {
            debugLog('Première vérification déclenchée');
            refreshConsultations();
        }, 2000);
        
        // Ensuite toutes les X secondes
        updateTimer = setInterval(() => {
            debugLog(`Vérification périodique (intervalle: ${UPDATE_INTERVAL}ms)`);
            refreshConsultations();
        }, UPDATE_INTERVAL);
    }
    
    // Arrêter la vérification
    function stopAutoRefresh() {
        if (updateTimer) {
            debugLog('Arrêt de la vérification automatique');
            clearInterval(updateTimer);
            updateTimer = null;
        }
    }
    
    // Démarrer quand la page est prête
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startAutoRefresh);
    } else {
        startAutoRefresh();
    }
    
    // Arrêter quand la page est en arrière-plan (optionnel)
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            debugLog('Page mise en arrière-plan, arrêt des vérifications');
            stopAutoRefresh();
            updateConnectionIndicator(false);
        } else {
            debugLog('Page revenue au premier plan, reprise des vérifications');
            startAutoRefresh();
        }
    });
    
    // Exposer les fonctions pour le debug
    window.realtimeDebug = {
        refresh: refreshConsultations,
        getStats: () => ({
            updateCount,
            errorCount,
            lastCheckTime,
            lastConsultationId,
            isUpdating,
            DEBUG_MODE
        }),
        enableDebug: () => {
            localStorage.setItem('realtime_debug', 'true');
            location.reload();
        },
        disableDebug: () => {
            localStorage.removeItem('realtime_debug');
            location.reload();
        }
    };
    
    if (DEBUG_MODE) {
        console.log('%c[REALTIME DEBUG MODE ACTIVÉ]', 'color: green; font-weight: bold; font-size: 14px;');
        console.log('Utilisez window.realtimeDebug pour accéder aux fonctions de debug');
        console.log('window.realtimeDebug.getStats() - Voir les statistiques');
        console.log('window.realtimeDebug.refresh() - Forcer une mise à jour');
    }
})();

// ============================================
// RACCOURCIS CLAVIER
// ============================================
(function() {
    try {
        const form = document.querySelector('form[method="GET"]');
        const searchInput = document.querySelector('input[name="search"]');
        const statutSelect = document.querySelector('select[name="statut"]');

        document.addEventListener('keydown', function(e) {
            if (e.target && (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable)) {
                return;
            }
            // '/' pour rechercher
            if (e.key === '/') {
                e.preventDefault();
                if (searchInput) {
                    searchInput.focus();
                }
            }
            // 'f' => filtrer "en_attente"
            if (e.key.toLowerCase() === 'f' && form && statutSelect) {
                e.preventDefault();
                statutSelect.value = 'en_attente';
                form.submit();
            }
            // 'r' => recharger
            if (e.key.toLowerCase() === 'r') {
                e.preventDefault();
                window.location.reload();
            }
        });
    } catch (err) {
        console.warn('Raccourcis caissière non initialisés', err);
    }
})();
</script>
</html>

