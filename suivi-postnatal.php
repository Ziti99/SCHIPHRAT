<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$db = new Database();

// Recherche et filtres
$search = $_GET['search'] ?? '';
$type_visite = $_GET['type_visite'] ?? '';
$date_visite = $_GET['date_visite'] ?? '';
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
    $params[] = "%$search%";
}

if (!empty($type_visite)) {
    $where[] = "sp.type_visite = ?";
    $params[] = $type_visite;
}

if (!empty($date_visite)) {
    $where[] = "DATE(sp.date_visite) = ?";
    $params[] = $date_visite;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Récupération des visites post-natales
$visites = $db->fetchAll("
    SELECT sp.*, 
           p.nom, p.prenom,
           a.nom_bebe, a.sexe_bebe, a.poids_bebe,
           med.nom as medecin_nom, med.prenom as medecin_prenom,
           sf.nom as sage_femme_nom, sf.prenom as sage_femme_prenom
    FROM suivi_postnatal sp
    JOIN accouchements a ON sp.accouchement_id = a.id
    JOIN patientes p ON a.patiente_id = p.id
    LEFT JOIN users med ON sp.medecin_id = med.id
    LEFT JOIN users sf ON sp.sage_femme_id = sf.id
    $whereClause
    ORDER BY sp.date_visite DESC
    LIMIT $limit OFFSET $offset
", $params);

// Comptage total pour pagination
$total = $db->fetch("
    SELECT COUNT(*) as count
    FROM suivi_postnatal sp
    JOIN accouchements a ON sp.accouchement_id = a.id
    JOIN patientes p ON a.patiente_id = p.id
    LEFT JOIN users med ON sp.medecin_id = med.id
    LEFT JOIN users sf ON sp.sage_femme_id = sf.id
    $whereClause
", $params)['count'];

$totalPages = ceil($total / $limit);

// Statistiques
$stats = [
    'total_visites' => $db->fetch("SELECT COUNT(*) as count FROM suivi_postnatal")['count'],
    'visites_mere' => $db->fetch("SELECT COUNT(*) as count FROM suivi_postnatal WHERE type_visite = 'mere'")['count'],
    'visites_bebe' => $db->fetch("SELECT COUNT(*) as count FROM suivi_postnatal WHERE type_visite = 'bebe'")['count'],
    'visites_combinees' => $db->fetch("SELECT COUNT(*) as count FROM suivi_postnatal WHERE type_visite = 'mere_et_bebe'")['count']
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi Post-natal - Clinique Obstétrique</title>
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
                        <p class="text-xs text-gray-500">Suivi post-natal</p>
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
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        <!-- Contenu principal -->
        <main class="flex-1 p-8">
            <!-- En-tête -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Suivi post-natal</h2>
                    <p class="text-gray-600"><?php echo $total; ?> visite(s) post-natale(s) enregistrée(s)</p>
                </div>
                <a href="/suivi-postnatal/ajouter.php" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-3 rounded-lg hover:shadow-lg transition-all duration-300 transform hover:scale-105 flex items-center">
                    <i class="fas fa-plus mr-2"></i>
                    Nouvelle visite
                </a>
            </div>

            <!-- Statistiques -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-100">Total visites</p>
                            <p class="text-3xl font-bold"><?php echo $stats['total_visites']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-heartbeat text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-blue-500 to-cyan-500 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100">Visites mère</p>
                            <p class="text-3xl font-bold"><?php echo $stats['visites_mere']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-green-500 to-emerald-500 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-100">Visites bébé</p>
                            <p class="text-3xl font-bold"><?php echo $stats['visites_bebe']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-baby text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-orange-500 to-red-500 rounded-xl p-6 text-white">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-orange-100">Visites combinées</p>
                            <p class="text-3xl font-bold"><?php echo $stats['visites_combinees']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-xl"></i>
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
                            <label for="type_visite" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-heartbeat mr-2"></i>Type de visite
                            </label>
                            <select name="type_visite" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                                <option value="">Tous les types</option>
                                <option value="mere" <?php echo $type_visite == 'mere' ? 'selected' : ''; ?>>Mère uniquement</option>
                                <option value="bebe" <?php echo $type_visite == 'bebe' ? 'selected' : ''; ?>>Bébé uniquement</option>
                                <option value="mere_et_bebe" <?php echo $type_visite == 'mere_et_bebe' ? 'selected' : ''; ?>>Mère et bébé</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="date_visite" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-calendar mr-2"></i>Date de visite
                            </label>
                            <input 
                                type="date" 
                                name="date_visite" 
                                value="<?php echo htmlspecialchars($date_visite); ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                            >
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <button type="submit" class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition-colors">
                            <i class="fas fa-search mr-2"></i>
                            Filtrer
                        </button>
                        <?php if (!empty($search) || !empty($type_visite) || !empty($date_visite)): ?>
                            <a href="/suivi-postnatal.php" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition-colors">
                                <i class="fas fa-times mr-2"></i>
                                Effacer les filtres
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Liste des visites -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Mère
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Bébé
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date visite
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Type
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Praticien
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($visites)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fas fa-heartbeat text-4xl mb-4 text-gray-300"></i>
                                        <p class="text-lg font-medium">Aucune visite post-natale trouvée</p>
                                        <p class="text-sm"><?php echo !empty($search) || !empty($type_visite) || !empty($date_visite) ? 'Essayez de modifier vos critères de recherche.' : 'Commencez par ajouter une nouvelle visite.'; ?></p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($visites as $visite): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-semibold">
                                                    <?php echo strtoupper(substr($visite['prenom'], 0, 1) . substr($visite['nom'], 0, 1)); ?>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($visite['prenom'] . ' ' . $visite['nom']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
            
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <?php if ($visite['nom_bebe']): ?>
                                                    <strong><?php echo htmlspecialchars($visite['nom_bebe']); ?></strong><br>
                                                <?php endif; ?>
                                                <?php if ($visite['poids_bebe']): ?>
                                                    <?php echo $visite['poids_bebe']; ?> kg
                                                <?php endif; ?>
                                                <?php if ($visite['sexe_bebe']): ?>
                                                    - <?php echo $visite['sexe_bebe'] == 'M' ? 'Garçon' : 'Fille'; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php echo date('d/m/Y', strtotime($visite['date_visite'])); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                <?php 
                                                switch($visite['type_visite']) {
                                                    case 'mere': echo 'bg-blue-100 text-blue-800'; break;
                                                    case 'bebe': echo 'bg-green-100 text-green-800'; break;
                                                    case 'mere_et_bebe': echo 'bg-purple-100 text-purple-800'; break;
                                                    default: echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php 
                                                switch($visite['type_visite']) {
                                                    case 'mere': echo 'Mère'; break;
                                                    case 'bebe': echo 'Bébé'; break;
                                                    case 'mere_et_bebe': echo 'Mère & Bébé'; break;
                                                    default: echo 'Inconnu';
                                                }
                                                ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <?php if ($visite['medecin_nom']): ?>
                                                    <div class="flex items-center">
                                                        <i class="fas fa-user-md text-blue-600 mr-2"></i>
                                                        Dr. <?php echo htmlspecialchars($visite['medecin_nom'] . ' ' . $visite['medecin_prenom']); ?>
                                                    </div>
                                                <?php elseif ($visite['sage_femme_nom']): ?>
                                                    <div class="flex items-center">
                                                        <i class="fas fa-user-nurse text-pink-600 mr-2"></i>
                                                        <?php echo htmlspecialchars($visite['sage_femme_nom'] . ' ' . $visite['sage_femme_prenom']); ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-gray-500 italic">Praticien non assigné</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="/suivi-postnatal/voir.php?id=<?php echo $visite['id']; ?>" class="text-purple-600 hover:text-purple-900" title="Voir la visite">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="/suivi-postnatal/modifier.php?id=<?php echo $visite['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($visite['prochaine_visite']): ?>
                                                    <span class="text-green-600" title="Prochaine visite: <?php echo date('d/m/Y', strtotime($visite['prochaine_visite'])); ?>">
                                                        <i class="fas fa-calendar-check"></i>
                                                    </span>
                                                <?php endif; ?>
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
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type_visite=<?php echo urlencode($type_visite); ?>&date_visite=<?php echo urlencode($date_visite); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Précédent
                                </a>
                            <?php endif; ?>
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type_visite=<?php echo urlencode($type_visite); ?>&date_visite=<?php echo urlencode($date_visite); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
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
                                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type_visite=<?php echo urlencode($type_visite); ?>&date_visite=<?php echo urlencode($date_visite); ?>" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $page ? 'z-10 bg-purple-50 border-purple-500 text-purple-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
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