<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Clinique\Auth\Auth;
use Clinique\Config\Database;

$auth = new Auth();
$auth->requireAnyRole(['admin', 'medecin', 'sage_femme']);

$db = Database::getInstance();

// Fonction pour générer l'ID d'accouchement
function generateAccouchementId($db, $date_accouchement, $accouchement_db_id) {
    $mois = date('m', strtotime($date_accouchement));
    $annee = date('Y', strtotime($date_accouchement));
    
    // Compter les accouchements du mois jusqu'à cet accouchement
    $accouchements_mois = $db->fetch("
        SELECT COUNT(*) as count 
        FROM accouchements 
        WHERE MONTH(date_accouchement) = ? AND YEAR(date_accouchement) = ? AND id <= ?
    ", [$mois, $annee, $accouchement_db_id])['count'];
    
    // Compter les accouchements de l'année jusqu'à cet accouchement
    $accouchements_annee = $db->fetch("
        SELECT COUNT(*) as count 
        FROM accouchements 
        WHERE YEAR(date_accouchement) = ? AND id <= ?
    ", [$annee, $accouchement_db_id])['count'];
    
    // Générer l'ID : 5eme accouchement du 3eme mois et 12eme de l'année = 0503122025
    $numero_mois = str_pad($accouchements_mois, 2, '0', STR_PAD_LEFT);
    $numero_annee = str_pad($accouchements_annee, 2, '0', STR_PAD_LEFT);
    
    return $numero_mois . $mois . $numero_annee . $annee;
}

// Recherche et filtres
$search = $_GET['search'] ?? '';
$mode_accouchement = $_GET['mode_accouchement'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Construction de la requête
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(p.nom LIKE ? OR p.prenom LIKE ? OR a.nom_bebe LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($mode_accouchement)) {
    $where[] = "a.mode_accouchement = ?";
    $params[] = $mode_accouchement;
}

if (!empty($date_debut)) {
    $where[] = "DATE(a.date_accouchement) >= ?";
    $params[] = $date_debut;
}

if (!empty($date_fin)) {
    $where[] = "DATE(a.date_accouchement) <= ?";
    $params[] = $date_fin;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Récupération des accouchements
$accouchements = $db->fetchAll("
    SELECT a.*, 
           p.nom, p.prenom,
           medecin.nom as medecin_nom, medecin.prenom as medecin_prenom,
           sage_femme.nom as sage_femme_nom, sage_femme.prenom as sage_femme_prenom
    FROM accouchements a
    JOIN patientes p ON a.patiente_id = p.id
    JOIN users medecin ON a.medecin_id = medecin.id
    LEFT JOIN users sage_femme ON a.sage_femme_id = sage_femme.id
    $whereClause
    ORDER BY a.date_accouchement DESC
    LIMIT $limit OFFSET $offset
", $params);

// Comptage total pour pagination
$total = $db->fetch("
    SELECT COUNT(*) as count
    FROM accouchements a
    JOIN patientes p ON a.patiente_id = p.id
    JOIN users medecin ON a.medecin_id = medecin.id
    LEFT JOIN users sage_femme ON a.sage_femme_id = sage_femme.id
    $whereClause
", $params)['count'];

$totalPages = ceil($total / $limit);

// Générer les IDs pour chaque accouchement
foreach ($accouchements as &$accouchement) {
    $accouchement['generated_id'] = generateAccouchementId($db, $accouchement['date_accouchement'], $accouchement['id']);
}

// Statistiques
$stats = [
    'total_accouchements' => $db->fetch("SELECT COUNT(*) as count FROM accouchements")['count'],
    'cesariennes' => $db->fetch("SELECT COUNT(*) as count FROM accouchements WHERE mode_accouchement = 'cesarienne'")['count'],
    'voie_basse' => $db->fetch("SELECT COUNT(*) as count FROM accouchements WHERE mode_accouchement = 'voie_basse'")['count'],
    'ce_mois' => $db->fetch("SELECT COUNT(*) as count FROM accouchements WHERE MONTH(date_accouchement) = MONTH(CURDATE()) AND YEAR(date_accouchement) = YEAR(CURDATE())")['count']
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accouchements - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#8B5CF6',
                        secondary: '#EC4899',
                        accent: '#06B6D4'
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
                    <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-heartbeat text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                            Clinique Obstétrique
                        </h1>
                        <p class="text-xs text-gray-500">Accouchements</p>
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
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Accouchements</h2>
                    <p class="text-gray-600"><?php echo $total; ?> accouchement(s) enregistré(s)</p>
                </div>
                <a href="/accouchements/ajouter.php" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-3 rounded-lg hover:shadow-lg transition-all duration-300 transform hover:scale-105 flex items-center">
                    <i class="fas fa-plus mr-2"></i>
                    Nouvel accouchement
                </a>
            </div>

            <!-- Statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100">Total accouchements</p>
                            <p class="text-3xl font-bold"><?php echo $stats['total_accouchements']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-baby text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-blue-500 to-cyan-500 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100">Voie basse</p>
                            <p class="text-3xl font-bold"><?php echo $stats['voie_basse']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-baby text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-orange-500 to-red-500 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-orange-100">Césariennes</p>
                            <p class="text-3xl font-bold"><?php echo $stats['cesariennes']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-cut text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100">Ce mois</p>
                            <p class="text-3xl font-bold"><?php echo $stats['ce_mois']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-calendar text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres et recherche -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <form method="GET" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-search mr-2"></i>Rechercher
                            </label>
                            <input 
                                type="text" 
                                name="search" 
                                value="<?php echo htmlspecialchars($search); ?>"
                                placeholder="Nom patiente ou bébé..."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                            >
                        </div>
                        
                        <div>
                            <label for="mode_accouchement" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-baby mr-2"></i>Mode d'accouchement
                            </label>
                            <select name="mode_accouchement" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                                <option value="">Tous les modes</option>
                                <option value="voie_basse" <?php echo $mode_accouchement == 'voie_basse' ? 'selected' : ''; ?>>Voie basse</option>
                                <option value="cesarienne" <?php echo $mode_accouchement == 'cesarienne' ? 'selected' : ''; ?>>Césarienne</option>
                                <option value="forceps" <?php echo $mode_accouchement == 'forceps' ? 'selected' : ''; ?>>Forceps</option>
                                <option value="ventouse" <?php echo $mode_accouchement == 'ventouse' ? 'selected' : ''; ?>>Ventouse</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="date_debut" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-calendar mr-2"></i>Date début
                            </label>
                            <input 
                                type="date" 
                                name="date_debut" 
                                value="<?php echo htmlspecialchars($date_debut); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
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
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                            >
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <button type="submit" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition-colors">
                            <i class="fas fa-search mr-2"></i>
                            Filtrer
                        </button>
                        <?php if (!empty($search) || !empty($mode_accouchement) || !empty($date_debut) || !empty($date_fin)): ?>
                            <a href="/accouchements.php" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition-colors">
                                <i class="fas fa-times mr-2"></i>
                                Effacer les filtres
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Liste des accouchements -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ID
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Mère
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date accouchement
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Mode
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Bébé
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Personnel
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($accouchements)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fas fa-baby text-4xl mb-4 text-gray-300"></i>
                                        <p class="text-lg font-medium">Aucun accouchement trouvé</p>
                                        <p class="text-sm"><?php echo !empty($search) || !empty($mode_accouchement) || !empty($date_debut) || !empty($date_fin) ? 'Essayez de modifier vos critères de recherche.' : 'Commencez par ajouter un nouvel accouchement.'; ?></p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($accouchements as $accouchement): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-bold text-green-600">
                                                <?php echo htmlspecialchars($accouchement['generated_id']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                ID: <?php echo $accouchement['id']; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full flex items-center justify-center text-white font-semibold">
                                                    <?php echo strtoupper(substr($accouchement['prenom'], 0, 1) . substr($accouchement['nom'], 0, 1)); ?>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($accouchement['prenom'] . ' ' . $accouchement['nom']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
            
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php echo date('d/m/Y H:i', strtotime($accouchement['date_accouchement'])); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                <?php 
                                                switch($accouchement['mode_accouchement']) {
                                                    case 'voie_basse': echo 'bg-green-100 text-green-800'; break;
                                                    case 'cesarienne': echo 'bg-red-100 text-red-800'; break;
                                                    case 'forceps': echo 'bg-yellow-100 text-yellow-800'; break;
                                                    case 'ventouse': echo 'bg-blue-100 text-blue-800'; break;
                                                    default: echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $accouchement['mode_accouchement'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <?php if ($accouchement['nom_bebe']): ?>
                                                    <strong><?php echo htmlspecialchars($accouchement['nom_bebe']); ?></strong><br>
                                                <?php endif; ?>
                                                <?php if ($accouchement['poids_bebe']): ?>
                                                    <?php echo $accouchement['poids_bebe']; ?> kg
                                                <?php endif; ?>
                                                <?php if ($accouchement['sexe_bebe']): ?>
                                                    - <?php echo $accouchement['sexe_bebe'] == 'M' ? 'Garçon' : 'Fille'; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <div>Dr. <?php echo htmlspecialchars($accouchement['medecin_nom'] . ' ' . $accouchement['medecin_prenom']); ?></div>
                                                <?php if ($accouchement['sage_femme_nom']): ?>
                                                    <div class="text-xs text-gray-500">
                                                        S-F. <?php echo htmlspecialchars($accouchement['sage_femme_nom'] . ' ' . $accouchement['sage_femme_prenom']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-xs text-gray-400 italic">
                                                        Aucune sage-femme
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="/accouchements/voir.php?id=<?php echo $accouchement['id']; ?>" class="text-purple-600 hover:text-purple-900" title="Voir l'accouchement">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="/accouchements/modifier.php?id=<?php echo $accouchement['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="/suivi-postnatal/ajouter.php?accouchement_id=<?php echo $accouchement['id']; ?>" class="text-green-600 hover:text-green-900" title="Suivi post-natal">
                                                    <i class="fas fa-heartbeat"></i>
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
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&mode_accouchement=<?php echo urlencode($mode_accouchement); ?>&date_debut=<?php echo urlencode($date_debut); ?>&date_fin=<?php echo urlencode($date_fin); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Précédent
                                </a>
                            <?php endif; ?>
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&mode_accouchement=<?php echo urlencode($mode_accouchement); ?>&date_debut=<?php echo urlencode($date_debut); ?>&date_fin=<?php echo urlencode($date_fin); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
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
                                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&mode_accouchement=<?php echo urlencode($mode_accouchement); ?>&date_debut=<?php echo urlencode($date_debut); ?>&date_fin=<?php echo urlencode($date_fin); ?>" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $page ? 'z-10 bg-purple-50 border-purple-500 text-purple-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
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

    <script>
        function closeMessage(messageId) {
            const message = document.getElementById(messageId);
            if (message) {
                message.style.display = 'none';
            }
        }
    </script>
</body>
</html> 