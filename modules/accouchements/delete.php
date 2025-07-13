<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';

// Vérifier l'authentification
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

$database = new Database();
$user = $auth->getCurrentUser();

// Récupérer l'ID de l'accouchement
$accouchement_id = $_GET['id'] ?? null;

if (!$accouchement_id) {
    header('Location: dashboard.php');
    exit();
}

// Vérifier si l'accouchement existe
$accouchement = $database->fetchOne("SELECT * FROM accouchements WHERE id = ?", [$accouchement_id]);

if (!$accouchement) {
    header('Location: dashboard.php');
    exit();
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database->query("DELETE FROM accouchements WHERE id = ?", [$accouchement_id]);
        
        // Rediriger avec un message de succès
        header('Location: dashboard.php?message=Accouchement supprimé avec succès');
        exit();
    } catch (Exception $e) {
        $error = 'Erreur lors de la suppression : ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer Accouchement - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-cyan-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="view.php?id=<?php echo $accouchement_id; ?>" class="text-purple-600 hover:text-purple-800 mr-4">
                        <i class="fas fa-arrow-left mr-2"></i>Retour
                    </a>
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-baby text-2xl text-pink-600 mr-3"></i>
                        <span class="text-xl font-bold text-gray-900">Supprimer Accouchement</span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">
                        <i class="fas fa-user mr-2"></i>
                        <?php echo htmlspecialchars($user['nom'] . ' ' . $user['prenom']); ?>
                    </span>
                    <a href="../../logout.php" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- En-tête -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Supprimer Accouchement</h1>
            <p class="text-gray-600">Confirmer la suppression de l'accouchement</p>
        </div>

        <!-- Contenu -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Confirmation de suppression</h2>
            </div>
            <div class="p-6">
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">
                                Attention
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>Êtes-vous sûr de vouloir supprimer cet accouchement ?</p>
                                <p class="mt-2 font-medium">Cette action est irréversible.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Détails de l'accouchement à supprimer -->
                <div class="mt-6 bg-gray-50 rounded-lg p-4">
                    <h4 class="text-lg font-medium text-gray-900 mb-3">Détails de l'accouchement</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-gray-700">Date :</span>
                            <span class="text-gray-900"><?php echo htmlspecialchars($accouchement['date_accouchement']); ?></span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Type :</span>
                            <span class="text-gray-900"><?php echo htmlspecialchars($accouchement['type_accouchement']); ?></span>
                        </div>
                        <?php if ($accouchement['sexe_bebe']): ?>
                        <div>
                            <span class="font-medium text-gray-700">Sexe du bébé :</span>
                            <span class="text-gray-900"><?php echo htmlspecialchars($accouchement['sexe_bebe']); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($accouchement['poids_bebe']): ?>
                        <div>
                            <span class="font-medium text-gray-700">Poids :</span>
                            <span class="text-gray-900"><?php echo htmlspecialchars($accouchement['poids_bebe']); ?> g</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Formulaire de confirmation -->
                <form method="POST" class="mt-6">
                    <div class="flex justify-end space-x-3">
                        <a href="view.php?id=<?php echo $accouchement_id; ?>" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition-all duration-300">
                            <i class="fas fa-times mr-2"></i>Annuler
                        </a>
                        <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition-all duration-300">
                            <i class="fas fa-trash mr-2"></i>Confirmer la suppression
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 