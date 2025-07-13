<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

// Utiliser la classe Auth correcte
// use Clinique\Auth\Auth; // This line is removed as per the edit hint.

// $auth = new Auth(); // This line is removed as per the edit hint.
// if (!$auth->isLoggedIn()) { // This line is removed as per the edit hint.
//     error_log("❌ DEBUG: User not logged in, redirecting to login.php"); // This line is removed as per the edit hint.
//     header('Location: login.php'); // This line is removed as per the edit hint.
//     exit; // This line is removed as per the edit hint.
// } // This line is removed as per the edit hint.

// error_log("✅ DEBUG: User logged in, continuing to patientes.php"); // This line is removed as per the edit hint.
// $user = $auth->getUser(); // This line is removed as per the edit hint.
$db = new Database();

// Paramètres de recherche et filtres
$search = $_GET['search'] ?? '';
$date_debut = $_GET['date_debut'] ?? '';
$date_fin = $_GET['date_fin'] ?? '';

// Construction de la requête avec filtres
$where = [];
$params = [];

if (!empty($search)) {
    // Recherche plus flexible : nom, prénom, ou nom + prénom ensemble
    $search_terms = explode(' ', trim($search));
    $search_conditions = [];
    
    foreach ($search_terms as $term) {
        if (!empty($term)) {
            $search_conditions[] = "(nom LIKE ? OR prenom LIKE ? OR CONCAT(nom, ' ', prenom) LIKE ? OR CONCAT(prenom, ' ', nom) LIKE ?)";
            $term_param = "%$term%";
            $params[] = $term_param; // nom
            $params[] = $term_param; // prenom
            $params[] = $term_param; // nom + prenom
            $params[] = $term_param; // prenom + nom
        }
    }
    
    if (!empty($search_conditions)) {
        $where[] = "(" . implode(' AND ', $search_conditions) . ")";
    }
}

if (!empty($date_debut)) {
    $where[] = "DATE(created_at) >= ?";
    $params[] = $date_debut;
}

if (!empty($date_fin)) {
    $where[] = "DATE(created_at) <= ?";
    $params[] = $date_fin;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Récupérer les patientes avec filtres
$sql = "SELECT * FROM patientes $whereClause ORDER BY created_at DESC LIMIT 50";
$patientes = $db->fetchAll($sql, $params);
error_log("📊 DEBUG: Found " . count($patientes) . " patientes");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Patientes - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Script de debug -->
    <script>
        console.log('🔍 DEBUG: Page patientes.php chargée');
        console.log('📍 URL actuelle:', window.location.href);
        console.log('👤 User info:', <?php echo json_encode($_SESSION['user_id']); ?>); // Modified to reflect session user_id
        console.log('🔐 Session active:', <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>);
        console.log('📊 Nombre de patientes:', <?php echo count($patientes); ?>);
        
        // Log au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('✅ DOM chargé - Page patientes.php');
            console.log('🔗 Liens trouvés:', document.querySelectorAll('a').length);
            
            // Vérifier les liens de navigation
            const links = document.querySelectorAll('a');
            links.forEach((link, index) => {
                console.log(`🔗 Lien ${index + 1}:`, link.href, link.textContent.trim());
            });
        });
        
        // Intercepter les clics sur les liens
        document.addEventListener('click', function(e) {
            if (e.target.tagName === 'A' || e.target.closest('a')) {
                const link = e.target.tagName === 'A' ? e.target : e.target.closest('a');
                console.log('🖱️ Clic sur lien:', link.href, link.textContent.trim());
            }
        });
        
        // Vérifier les redirections
        let redirectCount = 0;
        const originalLocation = window.location;
        
        // Intercepter les changements de location
        Object.defineProperty(window, 'location', {
            get: function() {
                return originalLocation;
            },
            set: function(value) {
                redirectCount++;
                console.log(`🔄 REDIRECTION #${redirectCount}:`, value);
                console.log('📍 Depuis:', window.location.href);
                console.log('⏰ Timestamp:', new Date().toISOString());
                console.trace('📚 Stack trace de la redirection');
                return originalLocation.assign(value);
            }
        });
        
        // Log des erreurs
        window.addEventListener('error', function(e) {
            console.error('❌ Erreur JavaScript:', e.message, e.filename, e.lineno);
        });
        
        // Log des erreurs réseau
        window.addEventListener('unhandledrejection', function(e) {
            console.error('❌ Erreur réseau:', e.reason);
        });
        
        console.log('🚀 Script de debug initialisé');
    </script>
</head>
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-cyan-50 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Contenu principal -->
        <div class="flex-1">
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
                                <p class="text-xs text-gray-500">Gestion des patientes</p>
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

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Messages -->
                <?php if (isset($_GET['message'])): ?>
                    <div id="message-success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 relative">
                        <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($_GET['message']); ?>
                        <button onclick="closeMessage('message-success')" class="absolute top-0 right-0 mt-2 mr-2 text-green-600 hover:text-green-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>
                
                <!-- En-tête harmonisé -->
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 mb-2">Gestion des Patientes</h1>
                        <p class="text-gray-600">Gérez les dossiers des patientes</p>
                    </div>
                    <a href="patientes_create.php" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-3 rounded-lg hover:shadow-lg transition-all duration-300 flex items-center">
                        <i class="fas fa-plus mr-2"></i>
                        Nouvelle Patiente
                    </a>
                </div>

                <!-- Formulaire de recherche -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-search mr-2"></i>Rechercher par nom/prénom
                            </label>
                            <input type="text" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   placeholder="Ex: Marie, Dupont, Marie Dupont..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="date_debut" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-calendar mr-2"></i>Date de création (du)
                            </label>
                            <input type="date" id="date_debut" name="date_debut" 
                                   value="<?php echo htmlspecialchars($date_debut); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="date_fin" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-calendar mr-2"></i>Date de création (au)
                            </label>
                            <input type="date" id="date_fin" name="date_fin" 
                                   value="<?php echo htmlspecialchars($date_fin); ?>"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        </div>
                        
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-2 rounded-lg hover:shadow-lg transition-all duration-300">
                                <i class="fas fa-search mr-2"></i>Rechercher
                            </button>
                        </div>
                    </form>
                    
                    <?php if (!empty($search) || !empty($date_debut) || !empty($date_fin)): ?>
                        <div class="mt-4 flex items-center justify-between">
                            <div class="text-sm text-gray-600">
                                <i class="fas fa-filter mr-2"></i>
                                Filtres actifs : 
                                <?php if (!empty($search)): ?>
                                    <span class="bg-purple-100 text-purple-800 px-2 py-1 rounded">Recherche: "<?php echo htmlspecialchars($search); ?>"</span>
                                <?php endif; ?>
                                <?php if (!empty($date_debut)): ?>
                                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded">Du: <?php echo htmlspecialchars($date_debut); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($date_fin)): ?>
                                    <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded">Au: <?php echo htmlspecialchars($date_fin); ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="patientes.php" class="text-red-600 hover:text-red-800 text-sm">
                                <i class="fas fa-times mr-1"></i>Effacer les filtres
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Liste des patientes -->
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Patientes récentes</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prénom</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Âge</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nationalité</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Téléphone</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($patientes)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                            Aucune patiente enregistrée
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($patientes as $patiente): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($patiente['nom']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($patiente['prenom']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($patiente['age']); ?> ans
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($patiente['nationalite'] ?? 'Non défini'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($patiente['telephone']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="patientes_view.php?id=<?php echo $patiente['id']; ?>" class="text-purple-600 hover:text-purple-900 mr-3">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="patientes_edit.php?id=<?php echo $patiente['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="patientes_delete.php?id=<?php echo $patiente['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette patiente ?')">
                                                    <i class="fas fa-trash"></i>
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
    
    <!-- Script de fin de page -->
    <script>
        console.log('🏁 Page patientes.php complètement chargée');
        console.log('📋 Contenu de la page:', document.body.innerHTML.length, 'caractères');
        
        // Fonction pour fermer les messages
        function closeMessage(messageId) {
            const message = document.getElementById(messageId);
            if (message) {
                message.style.display = 'none';
            }
        }
    </script>
</body>
</html> 