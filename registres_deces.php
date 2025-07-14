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

$where = [];
$params = [];
if (!empty($search)) {
    $where[] = "(p.nom LIKE ? OR p.prenom LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}
if (!empty($date_debut)) {
    $where[] = "d.date_deces >= ?";
    $params[] = $date_debut . ' 00:00:00';
}
if (!empty($date_fin)) {
    $where[] = "d.date_deces <= ?";
    $params[] = $date_fin . ' 23:59:59';
}
if (!empty($medecin_id)) {
    $where[] = "d.medecin_id = ?";
    $params[] = $medecin_id;
}
$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$deces = $db->fetchAll("
    SELECT d.*, p.nom, p.prenom, p.telephone, p.date_naissance, p.nationalite,
           medecin.nom as medecin_nom, medecin.prenom as medecin_prenom
    FROM deces d
    JOIN patientes p ON d.patiente_id = p.id
    LEFT JOIN users medecin ON d.medecin_id = medecin.id
    $whereClause
    ORDER BY d.date_deces DESC
", $params);

// Récupération des médecins pour les filtres
$medecins = $db->fetchAll("
    SELECT id, nom, prenom FROM users WHERE role = 'medecin' AND is_active = 1 ORDER BY nom, prenom
");

// Statistiques
$total_deces = count($deces);
$deces_ce_mois = $db->fetch("
    SELECT COUNT(*) as count FROM deces WHERE MONTH(date_deces) = MONTH(CURDATE()) AND YEAR(date_deces) = YEAR(CURDATE())
")['count'];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registre des Décès - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
    function exportExcel() {
        const params = new URLSearchParams(window.location.search);
        window.open('export_excel_deces.php?' + params.toString(), '_blank');
    }
    function exportPDF() {
        const params = new URLSearchParams(window.location.search);
        window.open('export_pdf_deces.php?' + params.toString(), '_blank');
    }
    </script>
</head>
<body class="bg-gradient-to-br from-gray-50 via-red-50 to-pink-50 min-h-screen">
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
                                <i class="fas fa-cross text-2xl text-red-600 mr-3"></i>
                                <span class="text-xl font-bold text-gray-900">Registre des Décès</span>
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
                    <div class="bg-gradient-to-r from-red-500 to-pink-500 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-red-100">Total Décès</p>
                                <p class="text-3xl font-bold"><?php echo $total_deces; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-cross text-xl"></i>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gradient-to-r from-gray-500 to-gray-600 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-100">Ce Mois</p>
                                <p class="text-3xl font-bold"><?php echo $deces_ce_mois; ?></p>
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
                        <i class="fas fa-filter mr-2 text-red-600"></i>Filtres de recherche
                    </h3>
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                   placeholder="Nom, prénom..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date début</label>
                            <input type="date" name="date_debut" value="<?php echo htmlspecialchars($date_debut); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date fin</label>
                            <input type="date" name="date_fin" value="<?php echo htmlspecialchars($date_fin); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Médecin</label>
                            <select name="medecin_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                                <option value="">Tous les médecins</option>
                                <?php foreach ($medecins as $medecin): ?>
                                    <option value="<?php echo $medecin['id']; ?>" <?php echo $medecin_id == $medecin['id'] ? 'selected' : ''; ?>>
                                        Dr. <?php echo htmlspecialchars($medecin['nom'] . ' ' . $medecin['prenom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="md:col-span-2 lg:col-span-4 flex justify-end space-x-3">
                            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-md hover:shadow-lg transition-all duration-300">
                                <i class="fas fa-search mr-2"></i>Filtrer
                            </button>
                            <a href="registres_deces.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                                <i class="fas fa-times mr-2"></i>Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Actions d'export -->
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Liste des Décès</h2>
                    <div class="flex space-x-3">
                        <button onclick="exportPDF()" class="px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-md hover:from-red-600 hover:to-red-700 transition-all duration-300 transform hover:scale-105 shadow-lg">
                            <i class="fas fa-file-pdf mr-2"></i>Export PDF
                        </button>
                        <button onclick="exportExcel()" class="px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-md hover:from-green-600 hover:to-green-700 transition-all duration-300 transform hover:scale-105 shadow-lg">
                            <i class="fas fa-file-excel mr-2"></i>Export Excel
                        </button>
                    </div>
                </div>

                <!-- Tableau des décès -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-red-500 to-pink-500 text-white">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Patiente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Date Décès</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Âge Décès</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Cause</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Lieu</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Médecin</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($deces)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-search text-4xl mb-4 text-gray-300"></i>
                                            <p class="text-lg">Aucun décès trouvé</p>
                                            <p class="text-sm">Essayez de modifier vos filtres de recherche</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($deces as $deces_item): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($deces_item['prenom'] . ' ' . $deces_item['nom']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        Tél: <?php echo htmlspecialchars($deces_item['telephone']); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <?php echo date('d/m/Y', strtotime($deces_item['date_deces'])); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo date('H:i', strtotime($deces_item['date_deces'])); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <?php echo $deces_item['age_deces']; ?> heures
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900">
                                                    <?php 
                                                    $cause = $deces_item['cause_deces'];
                                                    if (strlen($cause) > 50) {
                                                        echo htmlspecialchars(substr($cause, 0, 50)) . '...';
                                                    } else {
                                                        echo htmlspecialchars($cause);
                                                    }
                                                    ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($deces_item['lieu_deces'] ?? 'Non spécifié'); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    Dr. <?php echo htmlspecialchars($deces_item['medecin_prenom'] . ' ' . $deces_item['medecin_nom']); ?>
                                                </div>
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