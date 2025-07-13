<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$user = $auth->getCurrentUser();
$db = new Database();

// Récupérer l'ID de la patiente
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: dashboard.php');
    exit;
}

// Vérifier que la patiente existe
try {
    $patiente = $db->fetchOne("SELECT * FROM patientes WHERE id = ?", [$id]);
    if (!$patiente) {
        header('Location: dashboard.php');
        exit;
    }
} catch (Exception $e) {
    header('Location: dashboard.php');
    exit;
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Vérifier s'il y a des grossesses liées
        $grossesses = $db->fetchAll("SELECT COUNT(*) as total FROM grossesses WHERE patiente_id = ?", [$id]);
        $nombre_grossesses = $grossesses[0]['total'];
        
        if ($nombre_grossesses > 0) {
            // Supprimer d'abord les grossesses (et leurs données liées via CASCADE)
            $db->query("DELETE FROM grossesses WHERE patiente_id = ?", [$id]);
        }
        
        // Supprimer la patiente
        $db->query("DELETE FROM patientes WHERE id = ?", [$id]);
        
        // Rediriger avec un message de succès
        header('Location: dashboard.php?message=Patiente supprimée avec succès');
        exit;
        
    } catch (Exception $e) {
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer Patiente - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-cyan-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-purple-600 hover:text-purple-800 mr-4">
                        <i class="fas fa-arrow-left mr-2"></i>Retour
                    </a>
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-trash text-2xl text-red-600 mr-3"></i>
                        <span class="text-xl font-bold text-gray-900">Supprimer Patiente</span>
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

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Messages -->
        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Confirmation -->
        <div class="bg-white rounded-xl shadow-lg p-8">
            <div class="text-center mb-8">
                <i class="fas fa-exclamation-triangle text-6xl text-red-500 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Confirmer la suppression</h2>
                <p class="text-gray-600 mb-6">
                    Êtes-vous sûr de vouloir supprimer la patiente 
                    <strong><?php echo htmlspecialchars($patiente['nom'] . ' ' . $patiente['prenom']); ?></strong> ?
                </p>
                <p class="text-red-600 text-sm">
                    <i class="fas fa-warning mr-2"></i>
                    Cette action est irréversible et supprimera également toutes les données liées (grossesses, consultations, etc.)
                </p>
            </div>
            
            <!-- Informations de la patiente -->
            <div class="bg-gray-50 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations de la patiente</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <span class="font-medium text-gray-700">Nom :</span>
                        <span class="text-gray-900"><?php echo htmlspecialchars($patiente['nom'] . ' ' . $patiente['prenom']); ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Téléphone :</span>
                        <span class="text-gray-900"><?php echo htmlspecialchars($patiente['telephone']); ?></span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Âge :</span>
                        <span class="text-gray-900"><?php echo htmlspecialchars($patiente['age']); ?> ans</span>
                    </div>
                </div>
            </div>
            
            <!-- Boutons -->
            <div class="flex justify-center space-x-4">
                <a href="view.php?id=<?php echo $patiente['id']; ?>" 
                   class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                    <i class="fas fa-times mr-2"></i>Annuler
                </a>
                <form method="POST" class="inline">
                    <button type="submit" 
                            class="px-6 py-3 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors duration-200"
                            onclick="return confirm('Êtes-vous absolument sûr de vouloir supprimer cette patiente ?')">
                        <i class="fas fa-trash mr-2"></i>Supprimer définitivement
                    </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 