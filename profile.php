<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$db = new Database();
$user = $db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);

$message = '';
$messageType = '';

// Traitement de la mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_profile') {
            // Mise à jour des informations personnelles
            $nom = trim($_POST['nom']);
            $prenom = trim($_POST['prenom']);
            $email = trim($_POST['email']);
            $telephone = trim($_POST['telephone']);
            $specialite = trim($_POST['specialite']);

            // Validation
            if (empty($nom) || empty($prenom) || empty($email)) {
                $message = 'Les champs nom, prénom et email sont obligatoires.';
                $messageType = 'error';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $message = 'L\'adresse email n\'est pas valide.';
                $messageType = 'error';
            } else {
                // Vérifier si l'email existe déjà (sauf pour l'utilisateur actuel)
                $existingUser = $db->fetch(
                    "SELECT id FROM users WHERE email = ? AND id != ?",
                    [$email, $user['id']]
                );

                if ($existingUser) {
                    $message = 'Cette adresse email est déjà utilisée par un autre utilisateur.';
                    $messageType = 'error';
                } else {
                    // Mettre à jour le profil
                    $db->query(
                        "UPDATE users SET nom = ?, prenom = ?, email = ?, telephone = ?, specialite = ?, updated_at = NOW() WHERE id = ?",
                        [$nom, $prenom, $email, $telephone, $specialite, $user['id']]
                    );

                    $message = 'Profil mis à jour avec succès !';
                    $messageType = 'success';

                    // Mettre à jour les données de session
                    $_SESSION['user_nom'] = $nom;
                    $_SESSION['user_prenom'] = $prenom;

                    // Recharger les données utilisateur
                    $user = $db->fetch("SELECT * FROM users WHERE id = ?", [$user['id']]);
                }
            }
        } elseif ($_POST['action'] === 'change_password') {
            // Changement de mot de passe
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];

            // Validation
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $message = 'Tous les champs du mot de passe sont obligatoires.';
                $messageType = 'error';
            } elseif (!password_verify($currentPassword, $user['password'])) {
                $message = 'Le mot de passe actuel est incorrect.';
                $messageType = 'error';
            } elseif (strlen($newPassword) < 6) {
                $message = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
                $messageType = 'error';
            } elseif ($newPassword !== $confirmPassword) {
                $message = 'Les nouveaux mots de passe ne correspondent pas.';
                $messageType = 'error';
            } else {
                // Mettre à jour le mot de passe
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $db->query(
                    "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?",
                    [$hashedPassword, $user['id']]
                );

                $message = 'Mot de passe modifié avec succès !';
                $messageType = 'success';
            }
        }
    }
}

// Récupérer les statistiques de l'utilisateur
$userStats = [
    'consultations' => 0,
    'accouchements' => 0,
    'permanences' => 0,
    'registres' => 0
];

// Vérifier et récupérer les statistiques de permanences (table qui existe)
try {
    $userStats['permanences'] = $db->fetch("SELECT COUNT(*) as count FROM permanences WHERE secretaire_id = ?", [$user['id']])['count'];
} catch (Exception $e) {
    // Table n'existe pas ou erreur
}

// Récupérer les dernières activités de l'utilisateur
$recentActivities = [];

// Permanences récentes (table qui existe)
try {
    $recentPermanences = $db->fetchAll("
        SELECT p.*, a.nom_acte
        FROM permanences p
        JOIN actes_poses a ON p.acte_id = a.id
        WHERE p.secretaire_id = ?
        ORDER BY p.created_at DESC
        LIMIT 10
    ", [$user['id']]);

    foreach ($recentPermanences as $permanence) {
        $recentActivities[] = [
            'type' => 'permanence',
            'date' => $permanence['created_at'],
            'description' => 'Permanence - ' . $permanence['nom_patient'] . ' ' . $permanence['prenom_patient'] . ' (' . $permanence['nom_acte'] . ')',
            'icon' => 'fas fa-calendar-day',
            'color' => 'text-purple-600'
        ];
    }
} catch (Exception $e) {
    // Table n'existe pas ou erreur
}

// Trier par date
usort($recentActivities, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

$recentActivities = array_slice($recentActivities, 0, 10);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Utilisateur - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-cyan-50 min-h-screen">
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
                        <p class="text-xs text-gray-500">Profil utilisateur</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['nom'] . ' ' . $user['prenom']); ?></p>
                        <p class="text-xs text-gray-500 capitalize"><?= str_replace('_', ' ', $user['role']) ?></p>
                    </div>
                    <a href="/logout.php" class="text-gray-600 hover:text-red-600 transition-colors">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex">
        <?php include __DIR__ . '/includes/sidebar.php'; ?>
        <main class="flex-1 py-8 px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto">
                <!-- En-tête -->
                <div class="mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2 flex items-center">
                        <i class="fas fa-user-circle text-purple-600 mr-3"></i>
                        Mon Profil
                    </h2>
                    <p class="text-gray-600">Gérez vos informations personnelles et votre mot de passe</p>
                </div>

                <!-- Message de notification -->
                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200' ?>">
                        <div class="flex items-center">
                            <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
                            <?= htmlspecialchars($message) ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Informations du profil -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Carte d'information -->
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-xl font-bold text-gray-900">Informations Personnelles</h3>
                                <div class="w-16 h-16 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-white text-xl"></i>
                                </div>
                            </div>

                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="nom" class="block text-sm font-medium text-gray-700 mb-1">Nom *</label>
                                        <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label for="prenom" class="block text-sm font-medium text-gray-700 mb-1">Prénom *</label>
                                        <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($user['prenom']) ?>" required
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    </div>
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="telephone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                                        <input type="tel" id="telephone" name="telephone" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label for="specialite" class="block text-sm font-medium text-gray-700 mb-1">Spécialité</label>
                                        <input type="text" id="specialite" name="specialite" value="<?= htmlspecialchars($user['specialite'] ?? '') ?>"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    </div>
                                </div>

                                <div class="flex items-center justify-between pt-4">
                                    <div class="text-sm text-gray-500">
                                        <i class="fas fa-info-circle mr-1"></i>
                                        Rôle : <span class="font-semibold capitalize"><?= str_replace('_', ' ', $user['role']) ?></span>
                                    </div>
                                    <button type="submit" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-2 rounded-lg font-semibold shadow hover:shadow-lg transition-all duration-300">
                                        <i class="fas fa-save mr-2"></i>
                                        Mettre à jour
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Changement de mot de passe -->
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-xl font-bold text-gray-900">Changer le mot de passe</h3>
                                <div class="w-16 h-16 bg-gradient-to-r from-orange-500 to-red-500 rounded-full flex items-center justify-center">
                                    <i class="fas fa-lock text-white text-xl"></i>
                                </div>
                            </div>

                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe actuel *</label>
                                    <input type="password" id="current_password" name="current_password" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe *</label>
                                        <input type="password" id="new_password" name="new_password" required minlength="6"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    </div>
                                    <div>
                                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe *</label>
                                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    </div>
                                </div>

                                <div class="pt-4">
                                    <button type="submit" class="bg-gradient-to-r from-orange-500 to-red-500 text-white px-6 py-2 rounded-lg font-semibold shadow hover:shadow-lg transition-all duration-300">
                                        <i class="fas fa-key mr-2"></i>
                                        Changer le mot de passe
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Statistiques et activités -->
                    <div class="space-y-6">
                        <!-- Statistiques utilisateur -->
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-chart-bar text-purple-600 mr-2"></i>
                                Mes Statistiques
                            </h3>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fas fa-list text-purple-600 mr-3"></i>
                                        <span class="text-sm font-medium text-gray-700">Permanences</span>
                                    </div>
                                    <span class="text-lg font-bold text-purple-600"><?= $userStats['permanences'] ?></span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fas fa-user text-blue-600 mr-3"></i>
                                        <span class="text-sm font-medium text-gray-700">Compte actif</span>
                                    </div>
                                    <span class="text-lg font-bold text-blue-600"><?= $user['is_active'] ? 'Oui' : 'Non' ?></span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar text-green-600 mr-3"></i>
                                        <span class="text-sm font-medium text-gray-700">Membre depuis</span>
                                    </div>
                                    <span class="text-lg font-bold text-green-600"><?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-orange-50 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fas fa-clock text-orange-600 mr-3"></i>
                                        <span class="text-sm font-medium text-gray-700">Dernière activité</span>
                                    </div>
                                    <span class="text-lg font-bold text-orange-600"><?= date('d/m/Y', strtotime($user['updated_at'])) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Activités récentes -->
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-clock text-purple-600 mr-2"></i>
                                Activités Récentes
                            </h3>
                            <?php if (empty($recentActivities)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-inbox text-gray-400 text-2xl mb-2"></i>
                                    <p class="text-gray-500 text-sm">Aucune activité récente</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($recentActivities as $activity): ?>
                                        <div class="flex items-center space-x-3 p-3 hover:bg-gray-50 rounded-lg transition-colors">
                                            <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                                <i class="<?= $activity['icon'] ?> <?= $activity['color'] ?> text-sm"></i>
                                            </div>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($activity['description']) ?></p>
                                                <p class="text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($activity['date'])) ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Informations de compte -->
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-info-circle text-purple-600 mr-2"></i>
                                Informations de Compte
                            </h3>
                            <div class="space-y-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Nom d'utilisateur :</span>
                                    <span class="font-medium text-gray-900"><?= htmlspecialchars($user['username']) ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Membre depuis :</span>
                                    <span class="font-medium text-gray-900"><?= date('d/m/Y', strtotime($user['created_at'])) ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Dernière mise à jour :</span>
                                    <span class="font-medium text-gray-900"><?= date('d/m/Y H:i', strtotime($user['updated_at'])) ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Statut :</span>
                                    <span class="font-medium <?= $user['is_active'] ? 'text-green-600' : 'text-red-600' ?>">
                                        <?= $user['is_active'] ? 'Actif' : 'Inactif' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Validation du formulaire de mot de passe
        document.getElementById('change_password_form')?.addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
                return false;
            }
        });

        // Animation des messages
        setTimeout(function() {
            const messages = document.querySelectorAll('.bg-green-100, .bg-red-100');
            messages.forEach(function(message) {
                message.style.opacity = '0';
                setTimeout(function() {
                    message.remove();
                }, 500);
            });
        }, 5000);
    </script>
</body>
</html> 