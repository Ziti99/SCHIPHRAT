<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$db = new Database();

// Créer la table deces si elle n'existe pas
$db->query("
    CREATE TABLE IF NOT EXISTS deces (
        id INT PRIMARY KEY AUTO_INCREMENT,
        patiente_id INT NOT NULL,
        date_deces DATETIME NOT NULL,
        cause_deces TEXT NOT NULL,
        age_deces INT, -- en heures
        lieu_deces VARCHAR(255),
        medecin_id INT NOT NULL,
        observations TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (patiente_id) REFERENCES patientes(id) ON DELETE CASCADE,
        FOREIGN KEY (medecin_id) REFERENCES users(id)
    )
");

// Filtres
$search = $_GET['search'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$lieu = $_GET['lieu'] ?? '';
$cause = $_GET['cause'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;

// Construction de la requête
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(p.nom LIKE ? OR p.prenom LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($date_debut)) {
    $where[] = "DATE(d.date_deces) >= ?";
    $params[] = $date_debut;
}

if (!empty($date_fin)) {
    $where[] = "DATE(d.date_deces) <= ?";
    $params[] = $date_fin;
}

if (!empty($lieu)) {
    $where[] = "d.lieu_deces LIKE ?";
    $params[] = "%$lieu%";
}

if (!empty($cause)) {
    $where[] = "d.cause_deces LIKE ?";
    $params[] = "%$cause%";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Récupération des décès
$deces = $db->fetchAll("
    SELECT d.*, 
           p.nom, p.prenom, p.date_naissance, p.nationalite,
           medecin.nom as medecin_nom, medecin.prenom as medecin_prenom
    FROM deces d
    JOIN patientes p ON d.patiente_id = p.id
    JOIN users medecin ON d.medecin_id = medecin.id
    $whereClause
    ORDER BY d.date_deces DESC
    LIMIT $limit OFFSET $offset
", $params);

// Comptage total
$total = $db->fetch("
    SELECT COUNT(*) as count
    FROM deces d
    JOIN patientes p ON d.patiente_id = p.id
    JOIN users medecin ON d.medecin_id = medecin.id
    $whereClause
", $params)['count'];

$totalPages = ceil($total / $limit);

// Statistiques
$stats = [
    'total' => $db->fetch("SELECT COUNT(*) as count FROM deces")['count'],
    'ce_mois' => $db->fetch("SELECT COUNT(*) as count FROM deces WHERE MONTH(date_deces) = MONTH(CURDATE()) AND YEAR(date_deces) = YEAR(CURDATE())")['count'],
    'ce_an' => $db->fetch("SELECT COUNT(*) as count FROM deces WHERE YEAR(date_deces) = YEAR(CURDATE())")['count'],
    'domicile' => $db->fetch("SELECT COUNT(*) as count FROM deces WHERE lieu_deces LIKE '%domicile%' OR lieu_deces LIKE '%maison%'")['count']
];

// Export PDF
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    require_once __DIR__ . '/vendor/autoload.php';
    
    $html = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            .header { text-align: center; margin-bottom: 20px; }
            .title { font-size: 18px; font-weight: bold; margin-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; font-weight: bold; }
            .stats { margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="title">REGISTRE DES DÉCÈS</div>
            <div>Clinique Obstétrique</div>
            <div>Période: ' . ($date_debut ?: 'Toutes') . ' - ' . ($date_fin ?: 'Toutes') . '</div>
        </div>
        
        <div class="stats">
            <strong>Statistiques:</strong><br>
            Total: ' . $stats['total'] . ' | Ce mois: ' . $stats['ce_mois'] . ' | Cette année: ' . $stats['ce_an'] . '
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Date</th>
                    <th>Patiente</th>
                    <th>Dossier</th>
                    <th>Cause</th>
                    <th>Lieu</th>
                    <th>Médecin</th>
                </tr>
            </thead>
            <tbody>';
    
    foreach ($deces as $index => $deces_item) {
        $html .= '
                <tr>
                    <td>' . ($index + 1) . '</td>
                    <td>' . date('d/m/Y H:i', strtotime($deces_item['date_deces'])) . '</td>
                    <td>' . htmlspecialchars($deces_item['prenom'] . ' ' . $deces_item['nom']) . '</td>
                    
                    <td>' . htmlspecialchars($deces_item['cause_deces']) . '</td>
                    <td>' . htmlspecialchars($deces_item['lieu_deces'] ?: 'Non spécifié') . '</td>
                    <td>Dr. ' . htmlspecialchars($deces_item['medecin_prenom'] . ' ' . $deces_item['medecin_nom']) . '</td>
                </tr>';
    }
    
    $html .= '
            </tbody>
        </table>
    </body>
    </html>';
    
    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("registre_deces_" . date('Y-m-d') . ".pdf");
    exit;
}
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
</script>
</head>
<body class="bg-gradient-to-br from-gray-50 via-red-50 to-pink-50 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>
        
        <!-- Contenu principal -->
        <div class="flex-1">
            <!-- Navigation -->
            <nav class="bg-white shadow-lg border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <a href="../registres.php" class="text-purple-600 hover:text-purple-800 mr-4">
                                <i class="fas fa-arrow-left mr-2"></i>Retour
                            </a>
                            <div class="flex-shrink-0 flex items-center">
                                <i class="fas fa-book text-2xl text-red-600 mr-3"></i>
                                <span class="text-xl font-bold text-gray-900">Registre des Décès</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-gray-700">
                                <i class="fas fa-user mr-2"></i>
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </span>
                            <a href="../logout.php" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                            </a>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- En-tête -->
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Registre des Décès</h2>
                        <p class="text-gray-600">Registre officiel des décès enregistrés</p>
                    </div>
                    <div class="flex space-x-4">
                        <a href="?export=pdf&search=<?php echo urlencode($search); ?>&date_debut=<?php echo urlencode($date_debut); ?>&date_fin=<?php echo urlencode($date_fin); ?>&lieu=<?php echo urlencode($lieu); ?>&cause=<?php echo urlencode($cause); ?>" 
                           class="bg-gradient-to-r from-red-500 to-pink-500 text-white px-6 py-3 rounded-lg hover:shadow-lg transition-all duration-300 flex items-center">
                            <i class="fas fa-file-pdf mr-2"></i>Exporter PDF
                        </a>
                        <button onclick="exportExcel()" class="bg-gradient-to-r from-green-500 to-emerald-500 text-white px-6 py-3 rounded-lg hover:shadow-lg transition-all duration-300 flex items-center">
                            <i class="fas fa-file-excel mr-2"></i>Export Excel
                        </button>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-gradient-to-r from-red-500 to-pink-500 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-red-100">Total décès</p>
                                <p class="text-3xl font-bold"><?php echo $stats['total']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-cross text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-gray-500 to-gray-600 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-100">Ce mois</p>
                                <p class="text-3xl font-bold"><?php echo $stats['ce_mois']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-calendar text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-100">Cette année</p>
                                <p class="text-3xl font-bold"><?php echo $stats['ce_an']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-chart-line text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-orange-500 to-red-500 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-orange-100">Domicile</p>
                                <p class="text-3xl font-bold"><?php echo $stats['domicile']; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-home text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtres avancés -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-filter mr-2 text-red-600"></i>Filtres de recherche
                    </h3>
                    
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                                   placeholder="Nom, prénom, dossier..."
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
                            <label class="block text-sm font-medium text-gray-700 mb-2">Lieu</label>
                            <input type="text" name="lieu" value="<?php echo htmlspecialchars($lieu); ?>"
                                   placeholder="Domicile, Hôpital..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        
                        <div class="flex items-end">
                            <button type="submit" class="w-full px-6 py-2 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-md hover:shadow-lg transition-all duration-300">
                                <i class="fas fa-search mr-2"></i>Filtrer
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Liste des décès -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-red-500 to-pink-500 text-white">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">N°</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Patiente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Dossier</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Cause</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Lieu</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Médecin</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($deces)): ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-heart text-4xl mb-4 text-gray-300"></i>
                                            <p class="text-lg">Aucun décès trouvé</p>
                                            <p class="text-sm">Aucun décès ne correspond aux critères de recherche.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($deces as $index => $deces_item): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo $offset + $index + 1; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <?php echo date('d/m/Y', strtotime($deces_item['date_deces'])); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo date('H:i', strtotime($deces_item['date_deces'])); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($deces_item['prenom'] . ' ' . $deces_item['nom']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($deces_item['nationalite'] ?: 'Non défini'); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
    
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-900 max-w-xs">
                                                    <?php echo htmlspecialchars($deces_item['cause_deces']); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($deces_item['lieu_deces'] ?: 'Non spécifié'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                Dr. <?php echo htmlspecialchars($deces_item['medecin_prenom'] . ' ' . $deces_item['medecin_nom']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&date_debut=<?php echo urlencode($date_debut); ?>&date_fin=<?php echo urlencode($date_fin); ?>&lieu=<?php echo urlencode($lieu); ?>&cause=<?php echo urlencode($cause); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Précédent
                                    </a>
                                <?php endif; ?>
                                <?php if ($page < $totalPages): ?>
                                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&date_debut=<?php echo urlencode($date_debut); ?>&date_fin=<?php echo urlencode($date_fin); ?>&lieu=<?php echo urlencode($lieu); ?>&cause=<?php echo urlencode($cause); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Suivant
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Affichage de <span class="font-medium"><?php echo ($offset + 1); ?></span> à <span class="font-medium"><?php echo min($offset + $limit, $total); ?></span> sur <span class="font-medium"><?php echo $total; ?></span> résultats
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&date_debut=<?php echo urlencode($date_debut); ?>&date_fin=<?php echo urlencode($date_fin); ?>&lieu=<?php echo urlencode($lieu); ?>&cause=<?php echo urlencode($cause); ?>" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $page ? 'z-10 bg-red-50 border-red-500 text-red-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 