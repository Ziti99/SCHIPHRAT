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
$medecin_id = $_GET['medecin_id'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Construction de la requête
$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(p.nom LIKE ? OR p.prenom LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($medecin_id)) {
    $where[] = "cp.medecin_id = ?";
    $params[] = $medecin_id;
}

if (!empty($date_debut)) {
    $where[] = "DATE(cp.date_consultation) >= ?";
    $params[] = $date_debut;
}

if (!empty($date_fin)) {
    $where[] = "DATE(cp.date_consultation) <= ?";
    $params[] = $date_fin;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Récupération des consultations
$consultations = $db->fetchAll("
    SELECT cp.*, 
           p.nom, p.prenom,
           u.nom as medecin_nom, u.prenom as medecin_prenom,
           cp.dpa
    FROM consultations_prenatales cp
    JOIN patientes p ON cp.patiente_id = p.id
    JOIN users u ON cp.medecin_id = u.id
    $whereClause
    ORDER BY cp.date_consultation DESC
    LIMIT $limit OFFSET $offset
", $params);

// Comptage total pour pagination
$total = $db->fetch("
    SELECT COUNT(*) as count
    FROM consultations_prenatales cp
    JOIN patientes p ON cp.patiente_id = p.id
    JOIN users u ON cp.medecin_id = u.id
    $whereClause
", $params)['count'];

$totalPages = ceil($total / $limit);

// Récupération des médecins pour le filtre
$medecins = $db->fetchAll("
    SELECT id, nom, prenom, specialite
    FROM users 
    WHERE role IN ('medecin', 'sage_femme') AND is_active = 1
    ORDER BY nom, prenom
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultations Prénatales - Clinique Obstétrique</title>
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
                        <p class="text-xs text-gray-500">Consultations prénatales</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($_SESSION['user_nom'] . ' ' . $_SESSION['user_prenom']); ?></p>
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
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <!-- Contenu principal -->
        <main class="flex-1 p-8">
            <!-- En-tête -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Consultations prénatales</h2>
                    <p class="text-gray-600"><?php echo $total; ?> consultation(s) enregistrée(s)</p>
                </div>
                <a href="/consultations/ajouter.php" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-3 rounded-lg hover:shadow-lg transition-all duration-300 transform hover:scale-105 flex items-center">
                    <i class="fas fa-plus mr-2"></i>
                    Nouvelle consultation
                </a>
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
                                placeholder="Nom ou prénom..."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                            >
                        </div>
                        
                        <div>
                            <label for="medecin_id" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user-md mr-2"></i>Médecin
                            </label>
                            <select name="medecin_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                                <option value="">Tous les médecins</option>
                                <?php foreach ($medecins as $medecin): ?>
                                    <option value="<?php echo $medecin['id']; ?>" <?php echo $medecin_id == $medecin['id'] ? 'selected' : ''; ?>>
                                        Dr. <?php echo htmlspecialchars($medecin['nom'] . ' ' . $medecin['prenom']); ?>
                                        <?php if ($medecin['specialite']): ?>
                                            (<?php echo htmlspecialchars($medecin['specialite']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
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
                        <?php if (!empty($search) || !empty($medecin_id) || !empty($date_debut) || !empty($date_fin)): ?>
                            <a href="/consultations.php" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition-colors">
                                <i class="fas fa-times mr-2"></i>
                                Effacer les filtres
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Liste des consultations -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Patient
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date consultation
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Médecin
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    DPA
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acte posé
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($consultations)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                        <i class="fas fa-calendar-check text-4xl mb-4 text-gray-300"></i>
                                        <p class="text-lg font-medium">Aucune consultation trouvée</p>
                                        <p class="text-sm"><?php echo !empty($search) || !empty($medecin_id) || !empty($date_debut) || !empty($date_fin) ? 'Essayez de modifier vos critères de recherche.' : 'Commencez par ajouter une nouvelle consultation.'; ?></p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($consultations as $consultation): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="flex items-center">
                                                <div class="w-10 h-10 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-full flex items-center justify-center text-white font-semibold">
                                                    <?php echo strtoupper(substr($consultation['prenom'], 0, 1) . substr($consultation['nom'], 0, 1)); ?>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($consultation['prenom'] . ' ' . $consultation['nom']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php echo date('d/m/Y H:i', strtotime($consultation['date_consultation'])); ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                Dr. <?php echo htmlspecialchars($consultation['medecin_nom'] . ' ' . $consultation['medecin_prenom']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <?php echo $consultation['dpa'] ? date('d/m/Y', strtotime($consultation['dpa'])) : 'Non renseigné'; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <?php 
                                                $acte_pose = $consultation['acte_pose'];
                                                if (strlen($acte_pose) > 50) {
                                                    echo htmlspecialchars(substr($acte_pose, 0, 50)) . '...';
                                                } else {
                                                    echo htmlspecialchars($acte_pose ?: 'Non spécifié');
                                                }
                                                ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="/consultations/voir.php?id=<?php echo $consultation['id']; ?>" class="text-purple-600 hover:text-purple-900" title="Voir la consultation">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="/consultations/modifier.php?id=<?php echo $consultation['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="/examens/ajouter.php?consultation_id=<?php echo $consultation['id']; ?>" class="text-green-600 hover:text-green-900" title="Ajouter un examen">
                                                    <i class="fas fa-microscope"></i>
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
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&medecin_id=<?php echo urlencode($medecin_id); ?>&date_debut=<?php echo urlencode($date_debut); ?>&date_fin=<?php echo urlencode($date_fin); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Précédent
                                </a>
                            <?php endif; ?>
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&medecin_id=<?php echo urlencode($medecin_id); ?>&date_debut=<?php echo urlencode($date_debut); ?>&date_fin=<?php echo urlencode($date_fin); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
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
                                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&medecin_id=<?php echo urlencode($medecin_id); ?>&date_debut=<?php echo urlencode($date_debut); ?>&date_fin=<?php echo urlencode($date_fin); ?>" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $page ? 'z-10 bg-purple-50 border-purple-500 text-purple-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
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