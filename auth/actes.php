<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Clinique\Config\Database;

$auth = new Auth();
$auth->requireAuth();

// Vérifier que l'utilisateur est admin
if ($auth->getCurrentUserRole() !== 'admin') {
    header('Location: /dashboard.php');
    exit;
}

$db = Database::getInstance();
$message = '';

// Traitement des actions
if ($_POST) {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'ajouter':
                    $nom = $_POST['nom_acte'];
                    $montant = $_POST['montant'];
                    $description = $_POST['description'] ?? '';
                    
                    $sql = "INSERT INTO actes_poses (nom_acte, montant, description) VALUES (?, ?, ?)";
                    $db->query($sql, [$nom, $montant, $description]);
                    $message = "Acte ajouté avec succès !";
                    break;
                    
                case 'modifier':
                    $id = $_POST['acte_id'];
                    $nom = $_POST['nom_acte'];
                    $montant = $_POST['montant'];
                    $description = $_POST['description'] ?? '';
                    
                    $sql = "UPDATE actes_poses SET nom_acte = ?, montant = ?, description = ? WHERE id = ?";
                    $db->query($sql, [$nom, $montant, $description, $id]);
                    $message = "Acte modifié avec succès !";
                    break;
                    
                case 'supprimer':
                    $id = $_POST['acte_id'];
                    
                    // Vérifier si l'acte est utilisé
                    $count = $db->fetch("SELECT COUNT(*) as count FROM permanences WHERE acte_id = ?", [$id]);
                    if ($count['count'] > 0) {
                        $message = "Impossible de supprimer cet acte car il est utilisé dans des permanences.";
                    } else {
                        $sql = "DELETE FROM actes_poses WHERE id = ?";
                        $db->query($sql, [$id]);
                        $message = "Acte supprimé avec succès !";
                    }
                    break;
                    
                case 'toggle_status':
                    $id = $_POST['acte_id'];
                    $is_active = $_POST['is_active'] ? 0 : 1;
                    
                    $sql = "UPDATE actes_poses SET is_active = ? WHERE id = ?";
                    $db->query($sql, [$is_active, $id]);
                    $message = "Statut de l'acte modifié avec succès !";
                    break;
            }
        }
    } catch (Exception $e) {
        $message = "Erreur : " . $e->getMessage();
    }
}

// Récupérer tous les actes
$actes = $db->fetchAll("SELECT * FROM actes_poses ORDER BY nom_acte");

// Récupérer les statistiques d'utilisation
$stats_actes = $db->fetchAll("
    SELECT a.id, a.nom_acte, a.montant, a.is_active,
           COUNT(p.id) as nb_utilisations,
           SUM(p.montant_paye) as total_montant
    FROM actes_poses a
    LEFT JOIN permanences p ON a.id = p.acte_id
    GROUP BY a.id
    ORDER BY a.nom_acte
");
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Actes - Admin</title>
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
                        <p class="text-xs text-gray-500">Gestion des actes</p>
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
        <?php include '../includes/sidebar.php'; ?>
        <!-- Contenu principal -->
        <main class="flex-1 p-8">
            <!-- En-tête -->
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Gestion des Actes</h2>
                <a href="#form-acte" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-3 rounded-lg hover:shadow-lg transition-all duration-300 flex items-center">
                    <i class="fas fa-plus mr-2"></i>
                    Nouvel acte
                </a>
            </div>

            <!-- Messages -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Message -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Formulaire d'ajout/modification -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-plus-circle text-purple-600 mr-3"></i>
                    Ajouter un Acte
                </h2>
                
                <form method="POST" class="space-y-6">
                    <input type="hidden" name="action" value="ajouter">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nom de l'acte *</label>
                        <input type="text" name="nom_acte" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Montant (FCFA) *</label>
                        <input type="number" name="montant" required step="100" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
                    </div>
                    
                    <button type="submit" class="w-full bg-gradient-to-r from-purple-500 to-pink-500 text-white py-4 rounded-xl text-lg font-semibold hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                        <i class="fas fa-save mr-3"></i>
                        Ajouter l'acte
                    </button>
                </form>
            </div>

            <!-- Liste des actes -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <h2 class="text-2xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-list text-purple-600 mr-3"></i>
                    Actes Configurés
                </h2>
                
                <?php if (empty($actes)): ?>
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-clipboard-list text-4xl mb-4"></i>
                        <p>Aucun acte configuré</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        <?php foreach ($actes as $acte): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex-1">
                                        <h3 class="font-semibold text-gray-900">
                                            <?= htmlspecialchars($acte['nom_acte']) ?>
                                        </h3>
                                        <p class="text-sm text-gray-600">
                                            <?= number_format($acte['montant'], 0, ',', ' ') ?> FCFA
                                        </p>
                                        <?php if ($acte['description']): ?>
                                            <p class="text-xs text-gray-500 mt-1">
                                                <?= htmlspecialchars($acte['description']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="acte_id" value="<?= $acte['id'] ?>">
                                            <input type="hidden" name="is_active" value="<?= $acte['is_active'] ?>">
                                            <button type="submit" class="px-3 py-1 rounded-full text-xs font-medium
                                                <?= $acte['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                                <?= $acte['is_active'] ? 'Actif' : 'Inactif' ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                
                                <!-- Statistiques d'utilisation -->
                                <?php 
                                $stats = array_filter($stats_actes, function($stat) use ($acte) {
                                    return $stat['id'] == $acte['id'];
                                });
                                $stat = reset($stats);
                                ?>
                                <?php if ($stat): ?>
                                    <div class="text-xs text-gray-500 mt-2">
                                        <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded mr-2">
                                            <?= $stat['nb_utilisations'] ?> utilisations
                                        </span>
                                        <?php if ($stat['total_montant']): ?>
                                            <span class="inline-block bg-purple-100 text-purple-800 px-2 py-1 rounded">
                                                <?= number_format($stat['total_montant'], 0, ',', ' ') ?> FCFA total
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex space-x-2 mt-3">
                                    <button onclick="editActe(<?= htmlspecialchars(json_encode($acte)) ?>)" 
                                            class="flex-1 bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition-colors text-sm">
                                        <i class="fas fa-edit mr-2"></i>Modifier
                                    </button>
                                    <form method="POST" class="flex-1" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet acte ?')">
                                        <input type="hidden" name="action" value="supprimer">
                                        <input type="hidden" name="acte_id" value="<?= $acte['id'] ?>">
                                        <button type="submit" class="w-full bg-red-500 text-white py-2 px-4 rounded-lg hover:bg-red-600 transition-colors text-sm">
                                            <i class="fas fa-trash mr-2"></i>Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal de modification -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl shadow-xl p-8 max-w-md w-full">
                <h3 class="text-xl font-bold text-gray-900 mb-6">Modifier l'acte</h3>
                
                <form method="POST" id="editForm" class="space-y-6">
                    <input type="hidden" name="action" value="modifier">
                    <input type="hidden" name="acte_id" id="edit_acte_id">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nom de l'acte *</label>
                        <input type="text" name="nom_acte" id="edit_nom_acte" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Montant (FCFA) *</label>
                        <input type="number" name="montant" id="edit_montant" required step="100" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" id="edit_description" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
                    </div>
                    
                    <div class="flex space-x-4">
                        <button type="submit" class="flex-1 bg-gradient-to-r from-purple-500 to-pink-500 text-white py-3 rounded-lg font-semibold hover:shadow-lg transition-all duration-300">
                            <i class="fas fa-save mr-2"></i>Enregistrer
                        </button>
                        <button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-500 text-white py-3 rounded-lg font-semibold hover:bg-gray-600 transition-colors">
                            Annuler
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editActe(acte) {
            document.getElementById('edit_acte_id').value = acte.id;
            document.getElementById('edit_nom_acte').value = acte.nom_acte;
            document.getElementById('edit_montant').value = acte.montant;
            document.getElementById('edit_description').value = acte.description || '';
            document.getElementById('editModal').classList.remove('hidden');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
        
        // Fermer le modal en cliquant à l'extérieur
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html> 