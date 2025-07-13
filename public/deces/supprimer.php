<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Clinique\Auth\Auth;
use Clinique\Config\Database;

$auth = new Auth();
$auth->requireAnyRole(['admin', 'medecin']);

$db = Database::getInstance();

// Récupération de l'ID du décès
$deces_id = $_GET['id'] ?? null;

if (!$deces_id) {
    header('Location: ../deces.php');
    exit;
}

// Récupération des détails du décès
$deces = $db->fetch("
    SELECT d.*, 
           p.nom, p.prenom, p.date_naissance
    FROM deces d
    JOIN patientes p ON d.patiente_id = p.id
    WHERE d.id = ?
", [$deces_id]);

if (!$deces) {
    header('Location: ../deces.php');
    exit;
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->query("DELETE FROM deces WHERE id = ?", [$deces_id]);
        
        // Redirection avec message de succès
        header('Location: ../deces.php?success=Le décès a été supprimé avec succès.');
        exit;
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
    <title>Supprimer Décès - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-red-50 via-pink-50 to-purple-50 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>
        
        <!-- Contenu principal -->
        <div class="flex-1">
            <!-- Navigation -->
            <nav class="bg-white shadow-lg border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <a href="../deces.php" class="text-red-600 hover:text-red-800 mr-4">
                                <i class="fas fa-arrow-left mr-2"></i>Retour
                            </a>
                            <div class="flex-shrink-0 flex items-center">
                                <i class="fas fa-cross text-2xl text-red-600 mr-3"></i>
                                <span class="text-xl font-bold text-gray-900">Supprimer Décès</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-gray-700">
                                <i class="fas fa-user mr-2"></i>
                                <?php echo htmlspecialchars($auth->getCurrentUserName()); ?>
                            </span>
                            <a href="../logout.php" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                            </a>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Messages d'erreur -->
                <?php if (isset($error)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <!-- En-tête -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Supprimer un décès</h1>
                    <p class="text-gray-600">Confirmez la suppression de ce décès</p>
                </div>

                <!-- Avertissement -->
                <div class="bg-red-50 border border-red-200 rounded-xl p-6 mb-8">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-exclamation-triangle text-red-600 text-2xl mr-3"></i>
                        <h3 class="text-lg font-bold text-red-800">Attention</h3>
                    </div>
                    <p class="text-red-700 mb-4">
                        Cette action est irréversible. La suppression d'un décès entraînera la perte définitive de toutes les données associées.
                    </p>
                </div>

                <!-- Informations du décès -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-6">Détails du décès à supprimer</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Patiente</label>
                            <p class="text-lg font-semibold text-gray-900">
                                <?php echo htmlspecialchars($deces['prenom'] . ' ' . $deces['nom']); ?>
                            </p>
                            <p class="text-sm text-gray-600">
                                <?php echo date('d/m/Y', strtotime($deces['date_naissance'])); ?>
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date du décès</label>
                            <p class="text-lg font-semibold text-gray-900">
                                <?php echo date('d/m/Y H:i', strtotime($deces['date_deces'])); ?>
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cause du décès</label>
                            <p class="text-gray-900">
                                <?php echo htmlspecialchars($deces['cause_deces']); ?>
                            </p>
                        </div>
                        
                        <?php if ($deces['lieu_deces']): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Lieu du décès</label>
                            <p class="text-gray-900">
                                <?php echo htmlspecialchars($deces['lieu_deces']); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Formulaire de confirmation -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <form method="POST" class="space-y-6">
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-question-circle text-yellow-600 mr-2"></i>
                                <span class="font-medium text-yellow-800">Confirmation requise</span>
                            </div>
                            <p class="text-yellow-700 text-sm">
                                Pour confirmer la suppression, tapez <strong>SUPPRIMER</strong> dans le champ ci-dessous.
                            </p>
                        </div>
                        
                        <div>
                            <label for="confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                                Confirmation <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="confirmation" required
                                   placeholder="Tapez SUPPRIMER pour confirmer"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                                   pattern="SUPPRIMER"
                                   title="Tapez SUPPRIMER pour confirmer la suppression">
                        </div>

                        <!-- Boutons -->
                        <div class="flex justify-between items-center pt-6">
                            <a href="voir.php?id=<?php echo $deces_id; ?>" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i>Annuler
                            </a>
                            
                            <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors" onclick="return confirm('Êtes-vous absolument sûr de vouloir supprimer ce décès ? Cette action est irréversible.')">
                                <i class="fas fa-trash mr-2"></i>Supprimer définitivement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Validation côté client
        document.querySelector('form').addEventListener('submit', function(e) {
            const confirmation = document.querySelector('input[name="confirmation"]').value;
            if (confirmation !== 'SUPPRIMER') {
                e.preventDefault();
                alert('Veuillez taper SUPPRIMER pour confirmer la suppression.');
                return false;
            }
            
            if (!confirm('Êtes-vous absolument sûr de vouloir supprimer ce décès ? Cette action est irréversible.')) {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html> 