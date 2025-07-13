<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Clinique\Auth\Auth;
use Clinique\Config\Database;

$auth = new Auth();
$auth->requireAnyRole(['admin', 'medecin']);

$db = Database::getInstance();

// Recherche et filtres
$search = $_GET['search'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$lieu_deces = $_GET['lieu_deces'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Construction de la requête
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(p.nom LIKE ? OR p.prenom LIKE ? OR d.cause_deces LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($date_debut)) {
    $where[] = "DATE(d.date_deces) >= ?";
    $params[] = $date_debut;
}

if (!empty($date_fin)) {
    $where[] = "DATE(d.date_deces) <= ?";
    $params[] = $date_fin;
}

if (!empty($lieu_deces)) {
    $where[] = "d.lieu_deces LIKE ?";
    $params[] = "%$lieu_deces%";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Récupération des décès
$deces = $db->fetchAll("
    SELECT d.*, 
           p.nom, p.prenom, p.date_naissance,
           med.nom as medecin_nom, med.prenom as medecin_prenom
    FROM deces d
    JOIN patientes p ON d.patiente_id = p.id
    LEFT JOIN users med ON d.medecin_id = med.id
    $whereClause
    ORDER BY d.date_deces DESC
    LIMIT $limit OFFSET $offset
", $params);

// Comptage total pour pagination
$total = $db->fetch("
    SELECT COUNT(*) as count
    FROM deces d
    JOIN patientes p ON d.patiente_id = p.id
    LEFT JOIN users med ON d.medecin_id = med.id
    $whereClause
", $params)['count'];

$totalPages = ceil($total / $limit);

// Statistiques
$stats = [
    'total_deces' => $db->fetch("SELECT COUNT(*) as count FROM deces")['count'],
    'deces_ce_mois' => $db->fetch("SELECT COUNT(*) as count FROM deces WHERE MONTH(date_deces) = MONTH(CURRENT_DATE()) AND YEAR(date_deces) = YEAR(CURRENT_DATE())")['count'],
    'deces_ce_an' => $db->fetch("SELECT COUNT(*) as count FROM deces WHERE YEAR(date_deces) = YEAR(CURRENT_DATE())")['count'],
    'moyenne_age' => $db->fetch("SELECT AVG(age_deces) as moyenne FROM deces WHERE age_deces IS NOT NULL")['moyenne']
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Décès - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#DC2626',
                        secondary: '#EC4899',
                        accent: '#7C3AED'
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
                    <div class="w-10 h-10 bg-gradient-to-r from-red-500 to-pink-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-cross text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold bg-gradient-to-r from-red-600 to-pink-600 bg-clip-text text-transparent">
                            Clinique Obstétrique
                        </h1>
                        <p class="text-xs text-gray-500">Gestion des décès</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($auth->getCurrentUserName()); ?></p>
                        <p class="text-xs text-gray-500 capitalize"><?php echo str_replace('_', ' ', $auth->getCurrentUserRole()); ?></p>
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
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Gestion des décès</h2>
                    <p class="text-gray-600"><?php echo $total; ?> décès enregistré(s)</p>
                </div>
                <a href="/deces/ajouter.php" class="bg-gradient-to-r from-red-500 to-pink-500 text-white px-6 py-3 rounded-lg hover:shadow-lg transition-all duration-300 transform hover:scale-105 flex items-center">
                    <i class="fas fa-plus mr-2"></i>
                    Nouveau décès
                </a>
            </div>

            <!-- Statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-r from-red-500 to-pink-500 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-red-100">Total décès</p>
                            <p class="text-3xl font-bold"><?php echo $stats['total_deces']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-cross text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-orange-500 to-red-500 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-orange-100">Ce mois</p>
                            <p class="text-3xl font-bold"><?php echo $stats['deces_ce_mois']; ?></p>
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
                            <p class="text-3xl font-bold"><?php echo $stats['deces_ce_an']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar-alt text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-500 to-gray-700 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-100">Âge moyen</p>
                            <p class="text-3xl font-bold"><?php echo $stats['moyenne_age'] ? round($stats['moyenne_age']) : 'N/A'; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user-clock text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-filter mr-2"></i>Filtres
                </h3>
                <form method="GET" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-search mr-2"></i>Recherche
                            </label>
                            <input 
                                type="text" 
                                name="search" 
                                value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="Nom, prénom, cause..."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all duration-200"
                            >
                        </div>
                        
                        <div>
                            <label for="date_debut" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-calendar mr-2"></i>Date début
                            </label>
                            <input 
                                type="date" 
                                name="date_debut" 
                                value="<?php echo htmlspecialchars($date_debut); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all duration-200"
                            >
                        </div>
                        
                        <div>
                            <label for="date_fin" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-calendar mr-2"></i>Date fin
                            </label>
                            <input 
                                type="date" 
                                name="date_fin" 
                                value="<?php echo htmlspecialchars($date_fin); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all duration-200"
                            >
                        </div>
                        
                        <div>
                            <label for="lieu_deces" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-map-marker-alt mr-2"></i>Lieu
                            </label>
                            <input 
                                type="text" 
                                name="lieu_deces" 
                                value="<?php echo htmlspecialchars($lieu_deces); ?>"
                                placeholder="Lieu du décès..."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent transition-all duration-200"
                            >
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <button type="submit" class="bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition-colors">
                            <i class="fas fa-search mr-2"></i>
                            Filtrer
                        </button>
                        <?php if (!empty($search) || !empty($date_debut) || !empty($date_fin) || !empty($lieu_deces)): ?>
                            <a href="/deces.php" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition-colors">
                                <i class="fas fa-times mr-2"></i>
                                Effacer les filtres
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Liste des décès -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Patiente
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date décès
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Âge
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Lieu
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Médecin
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($deces)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fas fa-cross text-4xl mb-4 text-gray-300"></i>
                                        <p class="text-lg font-medium">Aucun décès trouvé</p>
                                        <p class="text-sm"><?php echo !empty($search) || !empty($date_debut) || !empty($date_fin) || !empty($lieu_deces) ? 'Essayez de modifier vos critères de recherche.' : 'Commencez par ajouter un nouveau décès.'; ?></p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($deces as $deces_item): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-gradient-to-r from-red-500 to-pink-500 rounded-full flex items-center justify-center text-white font-semibold">
                                                    <?php echo strtoupper(substr($deces_item['prenom'], 0, 1) . substr($deces_item['nom'], 0, 1)); ?>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($deces_item['prenom'] . ' ' . $deces_item['nom']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?php echo date('d/m/Y', strtotime($deces_item['date_naissance'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php echo date('d/m/Y H:i', strtotime($deces_item['date_deces'])); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php if ($deces_item['age_deces']): ?>
                                                <?php echo $deces_item['age_deces']; ?> ans
                                            <?php else: ?>
                                                <span class="text-gray-500 italic">Non renseigné</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php if ($deces_item['lieu_deces']): ?>
                                                <?php echo htmlspecialchars($deces_item['lieu_deces']); ?>
                                            <?php else: ?>
                                                <span class="text-gray-500 italic">Non renseigné</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <?php if ($deces_item['medecin_nom']): ?>
                                                    Dr. <?php echo htmlspecialchars($deces_item['medecin_prenom'] . ' ' . $deces_item['medecin_nom']); ?>
                                                <?php else: ?>
                                                    <span class="text-gray-500 italic">Non assigné</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="/deces/voir.php?id=<?php echo $deces_item['id']; ?>" class="text-red-600 hover:text-red-900" title="Voir les détails">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="/deces/modifier.php?id=<?php echo $deces_item['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="/deces/supprimer.php?id=<?php echo $deces_item['id']; ?>" class="text-red-600 hover:text-red-900" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce décès ?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
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
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&date_debut=<?php echo urlencode($date_debut); ?>&date_fin=<?php echo urlencode($date_fin); ?>&lieu_deces=<?php echo urlencode($lieu_deces); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Précédent
                                </a>
                            <?php endif; ?>
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&date_debut=<?php echo urlencode($date_debut); ?>&date_fin=<?php echo urlencode($date_fin); ?>&lieu_deces=<?php echo urlencode($lieu_deces); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
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
                                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&date_debut=<?php echo urlencode($date_debut); ?>&date_fin=<?php echo urlencode($date_fin); ?>&lieu_deces=<?php echo urlencode($lieu_deces); ?>" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $page ? 'z-10 bg-red-50 border-red-500 text-red-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html> 