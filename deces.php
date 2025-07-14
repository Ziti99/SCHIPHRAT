<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$db = new Database();

// Traitement de la suppression
if (isset($_POST['delete_deces'])) {
    $deces_id = $_POST['deces_id'] ?? 0;
    if ($deces_id > 0) {
        try {
            $db->query("DELETE FROM deces WHERE id = ?", [$deces_id]);
            $message = 'Décès supprimé avec succès !';
        } catch (Exception $e) {
            $error = 'Erreur lors de la suppression : ' . $e->getMessage();
        }
    }
}

// Récupération des paramètres de filtrage
$search = $_GET['search'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$lieu = $_GET['lieu'] ?? '';
$cause = $_GET['cause'] ?? '';

// Construction des conditions WHERE
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.nom LIKE ? OR p.prenom LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($date_debut)) {
    $where_conditions[] = "DATE(d.date_deces) >= ?";
    $params[] = $date_debut;
}

if (!empty($date_fin)) {
    $where_conditions[] = "DATE(d.date_deces) <= ?";
    $params[] = $date_fin;
}

if (!empty($lieu)) {
    $where_conditions[] = "d.lieu_deces LIKE ?";
    $params[] = "%$lieu%";
}

if (!empty($cause)) {
    $where_conditions[] = "d.cause_deces LIKE ?";
    $params[] = "%$cause%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Récupération du total pour la pagination
$total_query = "SELECT COUNT(*) as total FROM deces d JOIN patientes p ON d.patiente_id = p.id $where_clause";
$total_result = $db->fetch($total_query, $params);
$total = $total_result['total'];
$total_pages = ceil($total / $limit);

// Récupération des données avec pagination
$deces_list = $db->fetchAll("
    SELECT d.*, 
           p.nom, p.prenom, p.date_naissance, p.nationalite,
           medecin.nom as medecin_nom, medecin.prenom as medecin_prenom
    FROM deces d
    JOIN patientes p ON d.patiente_id = p.id
    JOIN users medecin ON d.medecin_id = medecin.id
    $where_clause
    ORDER BY d.date_deces DESC
    LIMIT $limit OFFSET $offset
", $params);

$message = '';
$error = '';
if (isset($_POST['delete_deces'])) {
    $message = 'Décès supprimé avec succès !';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Décès - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        <main class="flex-1 p-8">
            <!-- En-tête -->
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Gestion des Décès</h2>
                <a href="deces/ajouter.php" class="px-4 py-2 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-md shadow hover:shadow-lg flex items-center">
                    <i class="fas fa-plus mr-2"></i>Nouveau décès
                </a>
            </div>

            <!-- Messages -->
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6" id="successMessage">
                    <div class="flex justify-between items-center">
                        <div>
                            <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?>
                        </div>
                        <button onclick="document.getElementById('successMessage').style.display='none'" class="text-green-700 hover:text-green-900 text-xl font-bold">&times;</button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Filtres -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">
                    <i class="fas fa-filter mr-2 text-red-600"></i>Filtres
                </h3>
                <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nom, prénom..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date début</label>
                        <input type="date" name="date_debut" value="<?php echo htmlspecialchars($date_debut); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date fin</label>
                        <input type="date" name="date_fin" value="<?php echo htmlspecialchars($date_fin); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lieu</label>
                        <input type="text" name="lieu" value="<?php echo htmlspecialchars($lieu); ?>" placeholder="Lieu du décès..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cause</label>
                        <input type="text" name="cause" value="<?php echo htmlspecialchars($cause); ?>" placeholder="Cause du décès..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    <div class="md:col-span-2 lg:col-span-5 flex justify-end space-x-2">
                        <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors">
                            <i class="fas fa-search mr-2"></i>Filtrer
                        </button>
                        <a href="deces.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">
                            <i class="fas fa-times mr-2"></i>Réinitialiser
                        </a>
                    </div>
                </form>
            </div>

            <!-- Statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600">
                            <i class="fas fa-cross text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total décès</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $total; ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-calendar-day text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Ce mois</p>
                            <p class="text-2xl font-bold text-gray-900">
                                <?php 
                                $ce_mois = $db->fetch("SELECT COUNT(*) as count FROM deces WHERE MONTH(date_deces) = MONTH(CURRENT_DATE()) AND YEAR(date_deces) = YEAR(CURRENT_DATE())")['count'];
                                echo $ce_mois;
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-calendar-week text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Cette semaine</p>
                            <p class="text-2xl font-bold text-gray-900">
                                <?php 
                                $cette_semaine = $db->fetch("SELECT COUNT(*) as count FROM deces WHERE YEARWEEK(date_deces) = YEARWEEK(CURRENT_DATE())")['count'];
                                echo $cette_semaine;
                                ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des décès -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gradient-to-r from-red-500 to-pink-500 text-white">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Patiente</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Cause</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Lieu</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Médecin</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($deces_list)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fas fa-cross text-4xl mb-4 text-gray-300"></i>
                                        <p class="text-lg">Aucun décès trouvé</p>
                                        <?php if (!empty($search) || !empty($date_debut) || !empty($date_fin) || !empty($lieu) || !empty($cause)): ?>
                                            <p class="text-sm text-gray-400 mt-2">Essayez de modifier vos filtres</p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($deces_list as $deces): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo date('d/m/Y', strtotime($deces['date_deces'])); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo date('H:i', strtotime($deces['date_deces'])); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($deces['prenom'] . ' ' . $deces['nom']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo date('d/m/Y', strtotime($deces['date_naissance'])); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <?php echo htmlspecialchars($deces['cause_deces']); ?>
                                            </div>
                                            <?php if ($deces['age_deces']): ?>
                                                <div class="text-sm text-gray-500">
                                                    Âge: <?php echo $deces['age_deces']; ?> heures
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <?php echo htmlspecialchars($deces['lieu_deces'] ?? 'Non spécifié'); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                Dr. <?php echo htmlspecialchars($deces['medecin_prenom'] . ' ' . $deces['medecin_nom']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="deces/voir.php?id=<?php echo $deces['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Voir">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="deces/modifier.php?id=<?php echo $deces['id']; ?>" class="text-green-600 hover:text-green-900" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button onclick="deleteDeces(<?php echo $deces['id']; ?>, '<?php echo htmlspecialchars($deces['prenom'] . ' ' . $deces['nom']); ?>')" class="text-red-600 hover:text-red-900" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="flex justify-center mt-6">
                    <nav class="flex items-center space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="px-3 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" class="px-3 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 <?php echo $i === $page ? 'bg-red-500 text-white border-red-500' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="px-3 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Formulaire de suppression caché -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="delete_deces" value="1">
        <input type="hidden" name="deces_id" id="deleteDecesId">
    </form>

    <script>
        function deleteDeces(decesId, patienteName) {
            if (confirm('Êtes-vous sûr de vouloir supprimer le décès de "' + patienteName + '" ? Cette action est irréversible.')) {
                document.getElementById('deleteDecesId').value = decesId;
                document.getElementById('deleteForm').submit();
            }
        }
    </script>
</body>
</html> 