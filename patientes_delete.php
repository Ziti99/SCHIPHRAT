<?php
// Démarrer la session en premier, avant tout autre code
session_start();

// Point d'entrée public pour la suppression de patientes
require_once __DIR__ . '/config/database.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$user = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'] ?? '',
    'role' => $_SESSION['role'] ?? '',
];

$db = new Database();

$message = '';
$error = '';

// Récupérer l'ID de la patiente
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: patientes.php');
    exit();
}

// Récupérer les informations de la patiente
try {
    $patiente = $db->fetchOne("SELECT * FROM patientes WHERE id = ?", [$id]);
    if (!$patiente) {
        header('Location: patientes.php');
        exit();
    }
} catch (Exception $e) {
    header('Location: patientes.php');
    exit();
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Vérifier si la patiente a des données liées (sans grossesses car table inexistante)
        $hasConsultations = $db->fetchOne("SELECT COUNT(*) as count FROM consultations_prenatales WHERE patiente_id = ?", [$id]);
        $hasAccouchements = $db->fetchOne("SELECT COUNT(*) as count FROM accouchements WHERE patiente_id = ?", [$id]);
        
        if ($hasConsultations['count'] > 0 || $hasAccouchements['count'] > 0) {
            $error = "Impossible de supprimer cette patiente car elle a des données liées (consultations, accouchements).";
        } else {
            // Supprimer la patiente
            $sql = "DELETE FROM patientes WHERE id = ?";
            $db->query($sql, [$id]);
            
            $message = "Patiente supprimée avec succès !";
            
            // Rediriger vers la liste des patientes
            header('Location: patientes.php?message=' . urlencode($message));
            exit();
        }
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
                            <a href="patientes.php" class="text-purple-600 hover:text-purple-800 mr-4">
                                <i class="fas fa-arrow-left mr-2"></i>Retour
                            </a>
                            <div class="flex-shrink-0 flex items-center">
                                <i class="fas fa-user-times text-2xl text-red-600 mr-3"></i>
                                <span class="text-xl font-bold text-gray-900">Supprimer Patiente</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-gray-700">
                                <i class="fas fa-user mr-2"></i>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </span>
                            <a href="logout.php" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                            </a>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Messages -->
                <?php if ($error): ?>
                    <div id="message-error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 relative">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                        <button onclick="closeMessage('message-error')" class="absolute top-0 right-0 mt-2 mr-2 text-red-600 hover:text-red-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>
                
                <!-- En-tête -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Supprimer Patiente</h1>
                    <p class="text-gray-600">Confirmez la suppression de cette patiente</p>
                </div>

                <!-- Informations de la patiente -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-user text-red-600 text-xl"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">
                                <?php echo htmlspecialchars($patiente['nom'] . ' ' . $patiente['prenom']); ?>
                            </h2>
                            <p class="text-gray-600">ID: <?php echo htmlspecialchars($patiente['id']); ?></p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <span class="text-sm font-medium text-gray-500">Date de naissance:</span>
                            <p class="text-gray-900"><?php echo date('d/m/Y', strtotime($patiente['date_naissance'])); ?></p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">Âge:</span>
                            <p class="text-gray-900"><?php echo htmlspecialchars($patiente['age']); ?> ans</p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">Téléphone:</span>
                            <p class="text-gray-900"><?php echo htmlspecialchars($patiente['telephone']); ?></p>
                        </div>
                        <div>
                            <span class="text-sm font-medium text-gray-500">Nationalité:</span>
                            <p class="text-gray-900"><?php echo htmlspecialchars($patiente['nationalite'] ?? 'Non renseigné'); ?></p>
                        </div>
                    </div>
                    
                    <?php if (!empty($patiente['adresse'])): ?>
                        <div class="mb-4">
                            <span class="text-sm font-medium text-gray-500">Adresse:</span>
                            <p class="text-gray-900"><?php echo htmlspecialchars($patiente['adresse']); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($patiente['antecedents_medicaux'])): ?>
                        <div class="mb-4">
                            <span class="text-sm font-medium text-gray-500">Antécédents médicaux:</span>
                            <p class="text-gray-900"><?php echo htmlspecialchars($patiente['antecedents_medicaux']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Avertissement -->
                <div class="bg-red-50 border border-red-200 rounded-xl p-6 mb-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-medium text-red-800 mb-2">Attention !</h3>
                            <p class="text-red-700">
                                Cette action est irréversible. La suppression de cette patiente entraînera la perte définitive de toutes ses données.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Formulaire de confirmation -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <form method="POST" class="space-y-6">
                        <div class="flex items-center p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <input type="checkbox" id="confirm" name="confirm" required
                                   class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                            <label for="confirm" class="ml-3 text-sm text-yellow-800">
                                Je confirme que je souhaite supprimer définitivement cette patiente
                            </label>
                        </div>

                        <!-- Boutons -->
                        <div class="flex justify-end space-x-4 pt-6">
                            <a href="patientes.php" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                                Annuler
                            </a>
                            <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-all duration-300">
                                <i class="fas fa-trash mr-2"></i>Supprimer définitivement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Script pour fermer les messages -->
    <script>
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