<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$db = new Database();

// Filtres
$search = $_GET['search'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$medecin_id = $_GET['medecin_id'] ?? '';
$sage_femme_id = $_GET['sage_femme_id'] ?? '';

$where = [];
$params = [];
if (!empty($search)) {
    $where[] = "(p.nom LIKE ? OR p.prenom LIKE ? OR a.nom_bebe LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}
if (!empty($date_debut)) {
    $where[] = "a.date_accouchement >= ?";
    $params[] = $date_debut . ' 00:00:00';
}
if (!empty($date_fin)) {
    $where[] = "a.date_accouchement <= ?";
    $params[] = $date_fin . ' 23:59:59';
}
if (!empty($medecin_id)) {
    $where[] = "a.medecin_id = ?";
    $params[] = $medecin_id;
}
if (!empty($sage_femme_id)) {
    $where[] = "a.sage_femme_id = ?";
    $params[] = $sage_femme_id;
}
$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$accouchements = $db->fetchAll("
    SELECT a.*, p.nom, p.prenom, p.telephone, p.date_naissance, p.nationalite,
           medecin.nom as medecin_nom, medecin.prenom as medecin_prenom,
           sage_femme.nom as sage_femme_nom, sage_femme.prenom as sage_femme_prenom
    FROM accouchements a
    JOIN patientes p ON a.patiente_id = p.id
    LEFT JOIN users medecin ON a.medecin_id = medecin.id
    LEFT JOIN users sage_femme ON a.sage_femme_id = sage_femme.id
    $whereClause
    ORDER BY a.date_accouchement DESC
", $params);

// Récupération des médecins et sages-femmes pour les filtres
$medecins = $db->fetchAll("
    SELECT id, nom, prenom FROM users WHERE role = 'medecin' AND is_active = 1 ORDER BY nom, prenom
");
$sages_femmes = $db->fetchAll("
    SELECT id, nom, prenom FROM users WHERE role = 'sage_femme' AND is_active = 1 ORDER BY nom, prenom
");

// Statistiques
$total_accouchements = count($accouchements);
$accouchements_ce_mois = $db->fetch("
    SELECT COUNT(*) as count FROM accouchements WHERE MONTH(date_accouchement) = MONTH(CURDATE()) AND YEAR(date_accouchement) = YEAR(CURDATE())
")['count'];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registre des Accouchements - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
    function exportExcel() {
        const params = new URLSearchParams(window.location.search);
        window.open('export_excel_accouchements.php?' + params.toString(), '_blank');
    }
    function exportPDF() {
        const params = new URLSearchParams(window.location.search);
        window.open('export_pdf_accouchements.php?' + params.toString(), '_blank');
    }
    </script>
</head>
<body class="bg-gradient-to-br from-green-50 via-cyan-50 to-blue-50 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        <!-- Contenu principal -->
        <div class="flex-1">
            <!-- Navigation -->
            <nav class="bg-white shadow-lg border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <a href="registres.php" class="text-purple-600 hover:text-purple-800 mr-4">
                                <i class="fas fa-arrow-left mr-2"></i>Retour
                            </a>
                            <div class="flex-shrink-0 flex items-center">
                                <i class="fas fa-baby text-2xl text-green-600 mr-3"></i>
                                <span class="text-xl font-bold text-gray-900">Registre des Accouchements</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-gray-700">
                                <i class="fas fa-user mr-2"></i>
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </span>
                            <a href="logout.php" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                            </a>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Statistiques -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100">Total Accouchements</p>
                                <p class="text-3xl font-bold"><?php echo $total_accouchements; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-baby text-xl"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-r from-blue-500 to-cyan-500 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100">Ce Mois</p>
                                <p class="text-3xl font-bold"><?php echo $accouchements_ce_mois; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-calendar text-xl"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-100">Actions</p>
                                <p class="text-lg font-bold">Export & Filtres</p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-download text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-filter mr-2 text-green-600"></i>Filtres de recherche
                    </h3>
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                   placeholder="Nom, prénom ou bébé..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date début</label>
                            <input type="date" name="date_debut" value="<?php echo htmlspecialchars($date_debut); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date fin</label>
                            <input type="date" name="date_fin" value="<?php echo htmlspecialchars($date_fin); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Médecin</label>
                            <select name="medecin_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Tous les médecins</option>
                                <?php foreach ($medecins as $medecin): ?>
                                    <option value="<?php echo $medecin['id']; ?>" <?php echo $medecin_id == $medecin['id'] ? 'selected' : ''; ?>>
                                        Dr. <?php echo htmlspecialchars($medecin['nom'] . ' ' . $medecin['prenom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sage-femme</label>
                            <select name="sage_femme_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Toutes</option>
                                <?php foreach ($sages_femmes as $sf): ?>
                                    <option value="<?php echo $sf['id']; ?>" <?php echo $sage_femme_id == $sf['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sf['nom'] . ' ' . $sf['prenom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="md:col-span-2 lg:col-span-4 flex justify-end space-x-3">
                            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-md hover:shadow-lg transition-all duration-300">
                                <i class="fas fa-search mr-2"></i>Filtrer
                            </button>
                            <a href="registres_accouchements.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                                <i class="fas fa-times mr-2"></i>Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Actions d'export -->
                <div class="flex justify-end mb-6 space-x-3">
                    <button onclick="exportPDF()" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors">
                        <i class="fas fa-file-pdf mr-2"></i>Export PDF
                    </button>
                    <button onclick="exportExcel()" class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors">
                        <i class="fas fa-file-excel mr-2"></i>Export Excel
                    </button>
                </div>

                <!-- Tableau des accouchements -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-green-500 to-emerald-500 text-white">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Patiente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Mode</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Bébé</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Médecin</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Sage-femme</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($accouchements)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-baby text-4xl mb-4 text-gray-300"></i>
                                            <p class="text-lg">Aucun accouchement trouvé</p>
                                            <p class="text-sm">Essayez de modifier vos filtres de recherche</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($accouchements as $acc): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo date('d/m/Y H:i', strtotime($acc['date_accouchement'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php echo htmlspecialchars($acc['prenom'] . ' ' . $acc['nom']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    <?php 
                                                    switch($acc['mode_accouchement']) {
                                                        case 'cesarienne': echo 'bg-red-100 text-red-800'; break;
                                                        case 'voie_basse': echo 'bg-green-100 text-green-800'; break;
                                                        case 'forceps': echo 'bg-yellow-100 text-yellow-800'; break;
                                                        case 'ventouse': echo 'bg-blue-100 text-blue-800'; break;
                                                        default: echo 'bg-gray-100 text-gray-800';
                                                    }
                                                    ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $acc['mode_accouchement'])); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($acc['nom_bebe']): ?>
                                                    <?php echo htmlspecialchars($acc['nom_bebe']); ?>
                                                <?php else: ?>
                                                    <span class="text-gray-400">Non défini</span>
                                                <?php endif; ?>
                                                <div class="text-xs text-gray-500">
                                                    <?php echo $acc['sexe_bebe'] === 'M' ? 'Masculin' : ($acc['sexe_bebe'] === 'F' ? 'Féminin' : ''); ?>
                                                    <?php if ($acc['poids_bebe']): ?>
                                                        - <?php echo $acc['poids_bebe']; ?> kg
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                Dr. <?php echo htmlspecialchars($acc['medecin_prenom'] . ' ' . $acc['medecin_nom']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($acc['sage_femme_nom']): ?>
                                                    SF. <?php echo htmlspecialchars($acc['sage_femme_prenom'] . ' ' . $acc['sage_femme_nom']); ?>
                                                <?php else: ?>
                                                    <span class="text-gray-400">Non défini</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 