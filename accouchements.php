<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$db = new Database();

// Paramètres de filtrage
$search = $_GET['search'] ?? '';
$date_accouchement = $_GET['date_accouchement'] ?? '';
$mode_accouchement = $_GET['mode_accouchement'] ?? '';
$sexe_bebe = $_GET['sexe_bebe'] ?? '';
$medecin_id = $_GET['medecin_id'] ?? '';

// Construction de la requête avec filtres
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(p.nom LIKE ? OR p.prenom LIKE ? OR a.nom_bebe LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($date_accouchement)) {
    $where_conditions[] = "DATE(a.date_accouchement) = ?";
    $params[] = $date_accouchement;
}

if (!empty($mode_accouchement)) {
    $where_conditions[] = "a.mode_accouchement = ?";
    $params[] = $mode_accouchement;
}

if (!empty($sexe_bebe)) {
    $where_conditions[] = "a.sexe_bebe = ?";
    $params[] = $sexe_bebe;
}

if (!empty($medecin_id)) {
    $where_conditions[] = "a.medecin_id = ?";
    $params[] = $medecin_id;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Fonction pour générer l'ID d'accouchement (même fonction que dans ajouter.php)
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

// Récupération des accouchements
$accouchements = $db->fetchAll("
    SELECT 
        a.id as accouchement_id,
        a.date_accouchement,
        a.mode_accouchement,
        a.duree_travail,
        a.complications,
        a.nom_bebe,
        a.sexe_bebe,
        a.poids_bebe,
        a.taille_bebe,
        a.apgar_score,
        a.observations,
        p.id as patiente_id,
        p.nom, 
        p.prenom, 
        p.telephone,
        medecin.nom as medecin_nom,
        medecin.prenom as medecin_prenom,
        sage_femme.nom as sage_femme_nom,
        sage_femme.prenom as sage_femme_prenom
    FROM accouchements a
    JOIN patientes p ON a.patiente_id = p.id
    JOIN users medecin ON a.medecin_id = medecin.id
    LEFT JOIN users sage_femme ON a.sage_femme_id = sage_femme.id
    $where_clause
    ORDER BY a.date_accouchement DESC
", $params);

// Générer les IDs pour chaque accouchement
foreach ($accouchements as &$accouchement) {
    $accouchement['generated_id'] = generateAccouchementId($db, $accouchement['date_accouchement'], $accouchement['accouchement_id']);
}

// Récupération des médecins pour le filtre
$medecins = $db->fetchAll("
    SELECT id, nom, prenom, specialite
    FROM users 
    WHERE role IN ('medecin', 'sage_femme') AND is_active = 1
    ORDER BY nom, prenom
");

// Statistiques
$total_accouchements = count($accouchements);
$accouchements_ce_mois = $db->fetch("
    SELECT COUNT(*) as count 
    FROM accouchements 
    WHERE MONTH(date_accouchement) = MONTH(CURDATE()) 
    AND YEAR(date_accouchement) = YEAR(CURDATE())
")['count'];

$cesariennes = $db->fetch("
    SELECT COUNT(*) as count 
    FROM accouchements 
    WHERE mode_accouchement = 'cesarienne'
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                            <a href="../registres.php" class="text-purple-600 hover:text-purple-800 mr-4">
                                <i class="fas fa-arrow-left mr-2"></i>Retour
                            </a>
                            <div class="flex-shrink-0 flex items-center">
                                <i class="fas fa-baby text-2xl text-green-600 mr-3"></i>
                                <span class="text-xl font-bold text-gray-900">Accouchements</span>
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
                        <h2 class="text-3xl font-bold text-gray-900 mb-2">Accouchements</h2>
                        <p class="text-gray-600"><?php echo $total_accouchements; ?> accouchement(s) enregistré(s)</p>
                    </div>
                    <a href="/accouchements/ajouter.php" class="bg-gradient-to-r from-green-500 to-emerald-500 text-white px-6 py-3 rounded-lg hover:shadow-lg transition-all duration-300 transform hover:scale-105 flex items-center">
                        <i class="fas fa-plus mr-2"></i>
                        Nouvel accouchement
                    </a>
                </div>

                <!-- Message de succès -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                        <div class="flex">
                            <i class="fas fa-check-circle mr-2 mt-1"></i>
                            <div>
                                <strong>Succès !</strong> L'accouchement a été enregistré avec succès.
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Statistiques -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
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

                    <div class="bg-gradient-to-r from-red-500 to-pink-500 rounded-xl p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-red-100">Césariennes</p>
                                <p class="text-3xl font-bold"><?php echo $cesariennes; ?></p>
                            </div>
                            <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-stethoscope text-xl"></i>
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
                                   placeholder="Nom, prénom, dossier ou nom bébé..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date d'accouchement</label>
                            <input type="date" name="date_accouchement" value="<?php echo htmlspecialchars($date_accouchement); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Mode d'accouchement</label>
                            <select name="mode_accouchement" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Tous les modes</option>
                                <option value="voie_basse" <?php echo $mode_accouchement === 'voie_basse' ? 'selected' : ''; ?>>Voie basse</option>
                                <option value="cesarienne" <?php echo $mode_accouchement === 'cesarienne' ? 'selected' : ''; ?>>Césarienne</option>
                                <option value="forceps" <?php echo $mode_accouchement === 'forceps' ? 'selected' : ''; ?>>Forceps</option>
                                <option value="ventouse" <?php echo $mode_accouchement === 'ventouse' ? 'selected' : ''; ?>>Ventouse</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sexe du bébé</label>
                            <select name="sexe_bebe" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Tous</option>
                                <option value="M" <?php echo $sexe_bebe === 'M' ? 'selected' : ''; ?>>Masculin</option>
                                <option value="F" <?php echo $sexe_bebe === 'F' ? 'selected' : ''; ?>>Féminin</option>
                            </select>
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
                        
                        <div class="md:col-span-2 lg:col-span-4 flex justify-end space-x-3">
                            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-md hover:shadow-lg transition-all duration-300">
                                <i class="fas fa-search mr-2"></i>Filtrer
                            </button>
                            <a href="accouchements.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                                <i class="fas fa-times mr-2"></i>Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Liste des accouchements -->
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Liste des Accouchements</h2>
                </div>

                <!-- Tableau des accouchements -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gradient-to-r from-green-500 to-emerald-500 text-white">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Patiente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Date Accouchement</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Mode</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Bébé</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Médecin</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Actions</th>
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
                                    <?php foreach ($accouchements as $accouchement): ?>
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-bold text-green-600">
                                                    <?php echo htmlspecialchars($accouchement['generated_id']); ?>
                                                </div>
                                                <div class="text-xs text-gray-500">
                                                    ID: <?php echo $accouchement['accouchement_id']; ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div>
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($accouchement['prenom'] . ' ' . $accouchement['nom']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
            
                                                    </div>
                                                    <div class="text-xs text-gray-400">
                                                        Tél: <?php echo htmlspecialchars($accouchement['telephone']); ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <?php echo date('d/m/Y', strtotime($accouchement['date_accouchement'])); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo date('H:i', strtotime($accouchement['date_accouchement'])); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    <?php 
                                                    switch($accouchement['mode_accouchement']) {
                                                        case 'cesarienne': echo 'bg-red-100 text-red-800'; break;
                                                        case 'voie_basse': echo 'bg-green-100 text-green-800'; break;
                                                        case 'forceps': echo 'bg-yellow-100 text-yellow-800'; break;
                                                        case 'ventouse': echo 'bg-blue-100 text-blue-800'; break;
                                                        default: echo 'bg-gray-100 text-gray-800';
                                                    }
                                                    ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $accouchement['mode_accouchement'])); ?>
                                                </span>
                                                <?php if ($accouchement['duree_travail']): ?>
                                                    <div class="text-xs text-gray-500 mt-1">
                                                        <?php echo $accouchement['duree_travail']; ?> min
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    <?php if ($accouchement['nom_bebe']): ?>
                                                        <?php echo htmlspecialchars($accouchement['nom_bebe']); ?>
                                                    <?php else: ?>
                                                        <span class="text-gray-400">Non défini</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo $accouchement['sexe_bebe'] === 'M' ? 'Masculin' : 'Féminin'; ?>
                                                    <?php if ($accouchement['poids_bebe']): ?>
                                                        - <?php echo $accouchement['poids_bebe']; ?> kg
                                                    <?php endif; ?>
                                                </div>
                                                <?php if ($accouchement['apgar_score']): ?>
                                                    <div class="text-xs text-gray-500">
                                                        Apgar: <?php echo $accouchement['apgar_score']; ?>/10
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">
                                                    Dr. <?php echo htmlspecialchars($accouchement['medecin_prenom'] . ' ' . $accouchement['medecin_nom']); ?>
                                                </div>
                                                <?php if ($accouchement['sage_femme_nom']): ?>
                                                    <div class="text-sm text-gray-500">
                                                        SF. <?php echo htmlspecialchars($accouchement['sage_femme_prenom'] . ' ' . $accouchement['sage_femme_nom']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button onclick="viewDetails(<?php echo $accouchement['accouchement_id']; ?>)" 
                                                        class="text-green-600 hover:text-green-900 mr-3">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <a href="../accouchements/modifier.php?id=<?php echo $accouchement['accouchement_id']; ?>" 
                                                   class="text-blue-600 hover:text-blue-900 mr-3">
                                                    <i class="fas fa-edit"></i>
                                                </a>
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

    <script>
        // Log du tableau HTML après génération
        document.addEventListener('DOMContentLoaded', function() {
            // Log chaque ligne du tableau accouchements
            const rows = document.querySelectorAll('table tbody tr');
            console.log('Nombre de lignes dans le tableau:', rows.length);
            rows.forEach((row, idx) => {
                console.log('Ligne', idx + 1, row.innerText);
            });
        });
        // Log de la redirection lors du clic sur "voir"
        function viewDetails(accouchementId) {
            console.log('Redirection vers la fiche accouchement ID:', accouchementId);
            window.location.href = '../accouchements/voir.php?id=' + accouchementId;
        }
    </script>
</body>
</html> 