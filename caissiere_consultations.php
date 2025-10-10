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
} catch (Exception $e) {
    // Tables non créées, rediriger vers l'installation
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

            <div class="p-8">
                <!-- En-tête -->
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        <i class="fas fa-list-alt text-green-600 mr-3"></i>
                        Consultations et Paiements
                    </h1>
                    <p class="text-gray-600">Gérer les paiements des consultations et actes médicaux</p>
                </div>

                <!-- Statistiques rapides -->
                <div class="grid md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-white rounded-lg shadow p-4">
                        <p class="text-sm text-gray-600">Total</p>
                        <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total']; ?></p>
                    </div>
                    <div class="bg-red-50 rounded-lg shadow p-4">
                        <p class="text-sm text-red-600">En Attente</p>
                        <p class="text-2xl font-bold text-red-700"><?php echo $stats['en_attente']; ?></p>
                    </div>
                    <div class="bg-yellow-50 rounded-lg shadow p-4">
                        <p class="text-sm text-yellow-600">Paiement Partiel</p>
                        <p class="text-2xl font-bold text-yellow-700"><?php echo $stats['partiel']; ?></p>
                    </div>
                    <div class="bg-green-50 rounded-lg shadow p-4">
                        <p class="text-sm text-green-600">Payé Complet</p>
                        <p class="text-2xl font-bold text-green-700"><?php echo $stats['complet']; ?></p>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <form method="GET" class="grid md:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                            <select name="statut" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="tous" <?php echo $statut_filtre === 'tous' ? 'selected' : ''; ?>>Tous</option>
                                <option value="en_attente" <?php echo $statut_filtre === 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                <option value="paye_partiel" <?php echo $statut_filtre === 'paye_partiel' ? 'selected' : ''; ?>>Partiel</option>
                                <option value="paye_total" <?php echo $statut_filtre === 'paye_total' ? 'selected' : ''; ?>>Payé</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date Début</label>
                            <input type="date" name="date_debut" value="<?php echo htmlspecialchars($date_debut); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date Fin</label>
                            <input type="date" name="date_fin" value="<?php echo htmlspecialchars($date_fin); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Rechercher</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Nom, téléphone..." 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div class="flex items-end gap-2">
                            <button type="submit" class="flex-1 bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600">
                                <i class="fas fa-search mr-2"></i>Filtrer
                            </button>
                            <a href="caissiere_consultations.php" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">
                                <i class="fas fa-redo"></i>
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Liste des consultations -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Patiente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date Consultation</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actes</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant Total</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payé</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reste</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($consultations)): ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                            <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                            <p>Aucune consultation trouvée</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($consultations as $c): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4">
                                                <div>
                                                    <p class="font-semibold text-gray-900">
                                                        <?php echo htmlspecialchars($c['prenom'] . ' ' . $c['nom']); ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500">
                                                        <i class="fas fa-phone mr-1"></i><?php echo htmlspecialchars($c['telephone'] ?? '-'); ?>
                                                    </p>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600">
                                                <?php echo date('d/m/Y H:i', strtotime($c['date_consultation'])); ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="text-sm text-gray-600" title="<?php echo htmlspecialchars($c['actes_liste'] ?? 'Aucun'); ?>">
                                                    <i class="fas fa-stethoscope mr-1"></i><?php echo $c['nb_actes']; ?> acte(s)
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                                <?php echo number_format($c['montant_total'], 0, ',', ' '); ?> FCFA
                                            </td>
                                            <td class="px-6 py-4 text-sm font-semibold text-green-600">
                                                <?php echo number_format($c['montant_paye'], 0, ',', ' '); ?> FCFA
                                            </td>
                                            <td class="px-6 py-4 text-sm font-semibold text-red-600">
                                                <?php echo number_format($c['montant_restant'], 0, ',', ' '); ?> FCFA
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php
                                                $badge_colors = [
                                                    'en_attente' => 'bg-red-100 text-red-800',
                                                    'paye_partiel' => 'bg-yellow-100 text-yellow-800',
                                                    'paye_total' => 'bg-green-100 text-green-800'
                                                ];
                                                $badge_labels = [
                                                    'en_attente' => 'Non payé',
                                                    'paye_partiel' => 'Partiel',
                                                    'paye_total' => 'Payé'
                                                ];
                                                ?>
                                                <span class="px-2 py-1 text-xs rounded-full <?php echo $badge_colors[$c['statut']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                                    <?php echo $badge_labels[$c['statut']] ?? $c['statut']; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm space-x-2">
                                                <a href="caissiere_patiente_detail.php?id=<?php echo $c['patiente_id']; ?>" 
                                                   class="text-blue-600 hover:text-blue-800" title="Voir détails patiente">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($c['statut'] !== 'paye_total'): ?>
                                                    <a href="caissiere_valider_paiement.php?id=<?php echo $c['paiement_id']; ?>" 
                                                       class="text-green-600 hover:text-green-800" title="Valider paiement">
                                                        <i class="fas fa-check-circle"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="caissiere_recu.php?id=<?php echo $c['paiement_id']; ?>" 
                                                       class="text-purple-600 hover:text-purple-800" title="Imprimer reçu">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Résumé montant restant -->
                <?php if ($stats['montant_restant_total'] > 0): ?>
                <div class="mt-6 bg-orange-50 border border-orange-200 rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-orange-700 font-medium">Montant Total Restant à Encaisser</p>
                            <p class="text-3xl font-bold text-orange-900 mt-1">
                                <?php echo number_format($stats['montant_restant_total'], 0, ',', ' '); ?> FCFA
                            </p>
                        </div>
                        <i class="fas fa-exclamation-triangle text-5xl text-orange-300"></i>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

