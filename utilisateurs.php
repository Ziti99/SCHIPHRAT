<?php
require_once __DIR__ . '/vendor/autoload.php';

use Clinique\Auth\Auth;
use Clinique\Config\Database;

$auth = new Auth();
$auth->requireAnyRole(['admin']);

$db = Database::getInstance();
$message = '';
$error = '';

// Traitement du formulaire de création
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_user']) && !isset($_POST['update_user'])) {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $role = $_POST['role'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $specialite = $_POST['specialite'] ?? '';
    
    if (empty($username) || empty($password) || empty($nom) || empty($prenom) || empty($role)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } else {
        try {
            // Vérifier si l'utilisateur existe déjà
            $existing_user = $db->fetch("SELECT id FROM users WHERE username = ?", [$username]);
            if ($existing_user) {
                $error = 'Un utilisateur avec ce nom d\'utilisateur existe déjà.';
            } else {
                // Vérifier l'email seulement s'il n'est pas vide
                if (!empty($email)) {
                    $existing_email = $db->fetch("SELECT id FROM users WHERE email = ?", [$email]);
                    if ($existing_email) {
                        $error = 'Un utilisateur avec cet email existe déjà.';
                    } else {
                        // Continuer avec la création
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $db->query("
                            INSERT INTO users (username, email, password, nom, prenom, role, telephone, specialite, is_active)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
                        ", [$username, $email, $hashed_password, $nom, $prenom, $role, $telephone, $specialite]);
                        
                        $message = 'Utilisateur créé avec succès !';
                        $_POST = array();
                    }
                } else {
                    // Pas d'email, créer directement
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $db->query("
                        INSERT INTO users (username, email, password, nom, prenom, role, telephone, specialite, is_active)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
                    ", [$username, $email, $hashed_password, $nom, $prenom, $role, $telephone, $specialite]);
                    
                    $message = 'Utilisateur créé avec succès !';
                    $_POST = array();
                }
            }
        } catch (Exception $e) {
            $error = 'Erreur lors de la création : ' . $e->getMessage();
        }
    }
}

// Traitement de la suppression d'utilisateur
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'] ?? 0;
    if ($user_id > 0) {
        try {
            $db->query("DELETE FROM users WHERE id = ?", [$user_id]);
            $message = 'Utilisateur supprimé avec succès !';
        } catch (Exception $e) {
            $error = 'Erreur lors de la suppression : ' . $e->getMessage();
        }
    }
}

// Traitement de la modification d'utilisateur
if (isset($_POST['update_user'])) {
    $user_id = $_POST['user_id'] ?? 0;
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $email = $_POST['email'] ?? '';
    $role = $_POST['role'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $specialite = $_POST['specialite'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($nom) || empty($prenom) || empty($role)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } else {
        try {
            $update_fields = "nom = ?, prenom = ?, email = ?, role = ?, telephone = ?, specialite = ?";
            $params = [$nom, $prenom, $email, $role, $telephone, $specialite];
            
            // Si un nouveau mot de passe est fourni, l'ajouter à la mise à jour
            if (!empty($password)) {
                $update_fields .= ", password = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            $params[] = $user_id;
            $db->query("UPDATE users SET $update_fields WHERE id = ?", $params);
            
            $message = 'Utilisateur modifié avec succès !';
        } catch (Exception $e) {
            $error = 'Erreur lors de la modification : ' . $e->getMessage();
        }
    }
}

// Récupération des utilisateurs existants
$utilisateurs = $db->fetchAll("
    SELECT id, username, email, nom, prenom, role, telephone, specialite, is_active, created_at
    FROM users 
    ORDER BY nom, prenom
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                        <p class="text-xs text-gray-500">Gestion des utilisateurs</p>
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
        <?php include 'includes/sidebar.php'; ?>
        <!-- Contenu principal -->
        <main class="flex-1 p-8">
            <!-- En-tête -->
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Gestion des Utilisateurs</h2>
                <button id="showAddUserBtn" class="px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-md shadow hover:shadow-lg flex items-center">
                    <i class="fas fa-user-plus mr-2"></i>Ajouter un nouvel utilisateur
                </button>
            </div>

            <!-- Formulaire d'ajout d'utilisateur (masqué par défaut) -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8" id="formAddUser" style="display:none;">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">
                        <i class="fas fa-user-plus mr-2 text-purple-600"></i>Nouvel utilisateur
                    </h2>
                    <button id="hideAddUserBtn" type="button" class="text-gray-400 hover:text-red-500 text-xl font-bold">&times;</button>
                </div>
                <form method="POST" autocomplete="off" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom <span class="text-red-500">*</span></label>
                        <input type="text" name="nom" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prénom <span class="text-red-500">*</span></label>
                        <input type="text" name="prenom" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom d'utilisateur <span class="text-red-500">*</span></label>
                        <input type="text" name="username" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe <span class="text-red-500">*</span></label>
                        <input type="password" name="password" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rôle <span class="text-red-500">*</span></label>
                        <select name="role" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            <option value="">Sélectionner</option>
                            <option value="admin" <?php if(($_POST['role'] ?? '')==='admin') echo 'selected'; ?>>Administrateur</option>
                            <option value="medecin" <?php if(($_POST['role'] ?? '')==='medecin') echo 'selected'; ?>>Médecin</option>
                            <option value="sage_femme" <?php if(($_POST['role'] ?? '')==='sage_femme') echo 'selected'; ?>>Sage-femme</option>
                            <option value="secretaire" <?php if(($_POST['role'] ?? '')==='secretaire') echo 'selected'; ?>>Secrétaire</option>
                            <option value="caissiere" <?php if(($_POST['role'] ?? '')==='caissiere') echo 'selected'; ?>>Caissière</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                        <input type="text" name="telephone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Spécialité</label>
                        <input type="text" name="specialite" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500" value="<?php echo htmlspecialchars($_POST['specialite'] ?? ''); ?>">
                    </div>
                    <div class="md:col-span-2 flex justify-end mt-2 space-x-2">
                        <button type="button" id="cancelAddUserBtn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Annuler</button>
                        <button type="submit" class="px-6 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-md hover:shadow-lg transition-all duration-300 flex items-center">
                            <i class="fas fa-user-plus mr-2"></i>Créer l'utilisateur
                        </button>
                    </div>
                </form>
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
                
                <!-- Liste des utilisateurs -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">
                        <i class="fas fa-users mr-2 text-blue-600"></i>Utilisateurs existants
                    </h2>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisateur</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rôle</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($utilisateurs as $utilisateur): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    <?php echo htmlspecialchars($utilisateur['username']); ?>
                                                </div>
                                                <div class="text-xs text-gray-400">
                                                    <?php echo htmlspecialchars($utilisateur['email']); ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                <?php 
                                                switch($utilisateur['role']) {
                                                    case 'admin': echo 'bg-red-100 text-red-800'; break;
                                                    case 'medecin': echo 'bg-blue-100 text-blue-800'; break;
                                                    case 'sage_femme': echo 'bg-green-100 text-green-800'; break;
                                                    case 'secretaire': echo 'bg-purple-100 text-purple-800'; break;
                                                    case 'caissiere': echo 'bg-yellow-100 text-yellow-800'; break;
                                                    default: echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $utilisateur['role'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                <?php echo $utilisateur['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo $utilisateur['is_active'] ? 'Actif' : 'Inactif'; ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button onclick="editUser(<?php echo $utilisateur['id']; ?>)" 
                                                        class="text-blue-600 hover:text-blue-900" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="deleteUser(<?php echo $utilisateur['id']; ?>, '<?php echo htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']); ?>')" 
                                                        class="text-red-600 hover:text-red-900" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Formulaire de modification d'utilisateur (masqué par défaut) -->
    <div id="editUserModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full" style="display:none;">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    <i class="fas fa-edit mr-2 text-blue-600"></i>Modifier l'utilisateur
                </h3>
                <form method="POST" id="editUserForm">
                    <input type="hidden" name="update_user" value="1">
                    <input type="hidden" name="user_id" id="editUserId">
                    
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nom <span class="text-red-500">*</span></label>
                        <input type="text" name="nom" id="editNom" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Prénom <span class="text-red-500">*</span></label>
                        <input type="text" name="prenom" id="editPrenom" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="editEmail" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Rôle <span class="text-red-500">*</span></label>
                        <select name="role" id="editRole" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Sélectionner</option>
                            <option value="admin">Administrateur</option>
                            <option value="medecin">Médecin</option>
                            <option value="sage_femme">Sage-femme</option>
                            <option value="secretaire">Secrétaire</option>
                            <option value="caissiere">Caissière</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                        <input type="text" name="telephone" id="editTelephone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Spécialité</label>
                        <input type="text" name="specialite" id="editSpecialite" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                        <input type="password" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Annuler</button>
                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">Modifier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Affichage/masquage du formulaire d'ajout
        const showAddUserBtn = document.getElementById('showAddUserBtn');
        const formAddUser = document.getElementById('formAddUser');
        const hideAddUserBtn = document.getElementById('hideAddUserBtn');
        const cancelAddUserBtn = document.getElementById('cancelAddUserBtn');
        if (showAddUserBtn && formAddUser) {
            showAddUserBtn.onclick = function() {
                formAddUser.style.display = '';
                showAddUserBtn.style.display = 'none';
            };
        }
        if (hideAddUserBtn && formAddUser) {
            hideAddUserBtn.onclick = function() {
                formAddUser.style.display = 'none';
                showAddUserBtn.style.display = '';
            };
        }
        if (cancelAddUserBtn && formAddUser) {
            cancelAddUserBtn.onclick = function() {
                formAddUser.style.display = 'none';
                showAddUserBtn.style.display = '';
            };
        }

        function editUser(userId) {
            // Récupérer les données de l'utilisateur via AJAX ou les passer en paramètre
            // Pour l'instant, on va utiliser une approche simple
            fetch('get_user_data.php?id=' + userId)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('editUserId').value = data.id;
                    document.getElementById('editNom').value = data.nom;
                    document.getElementById('editPrenom').value = data.prenom;
                    document.getElementById('editEmail').value = data.email;
                    document.getElementById('editRole').value = data.role;
                    document.getElementById('editTelephone').value = data.telephone;
                    document.getElementById('editSpecialite').value = data.specialite;
                    document.getElementById('editUserModal').style.display = '';
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors du chargement des données utilisateur');
                });
        }

        function closeEditModal() {
            document.getElementById('editUserModal').style.display = 'none';
        }

        function deleteUser(userId, userName) {
            if (confirm('Êtes-vous sûr de vouloir supprimer l\'utilisateur "' + userName + '" ? Cette action est irréversible.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = '<input type="hidden" name="delete_user" value="1"><input type="hidden" name="user_id" value="' + userId + '">';
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html> 