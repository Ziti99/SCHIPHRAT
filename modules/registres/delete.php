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

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: dashboard.php');
    exit;
}

$registre = $db->fetchOne("SELECT * FROM registres WHERE id = ?", [$id]);

if (!$registre) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->query("DELETE FROM registres WHERE id = ?", [$id]);
        header('Location: dashboard.php?message=Registre supprimé avec succès');
        exit;
    } catch (Exception $e) {
        $error = 'Erreur lors de la suppression du registre';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer le Registre - Clinique Obstétrique</title>
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
                        <i class="fas fa-book text-2xl text-purple-600 mr-3"></i>
                        <span class="text-xl font-bold text-gray-900">Supprimer le Registre</span>
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
        <!-- En-tête -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Supprimer le Registre</h1>
            <p class="text-gray-600">Confirmer la suppression du registre</p>
        </div>

        <!-- Messages -->
        <?php if (isset($error)): ?>
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Confirmation -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="text-center mb-6">
                <i class="fas fa-exclamation-triangle text-6xl text-red-500 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Êtes-vous sûr ?</h2>
                <p class="text-gray-600">Cette action est irréversible.</p>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg mb-6">
                <h3 class="font-semibold text-gray-900 mb-2">Détails du registre à supprimer :</h3>
                <div class="space-y-2">
                    <p><strong>Type :</strong> <?php echo htmlspecialchars($registre['type_registre']); ?></p>
                    <p><strong>Date de création :</strong> <?php echo date('d/m/Y H:i', strtotime($registre['date_creation'])); ?></p>
                    <?php if ($registre['description']): ?>
                        <p><strong>Description :</strong> <?php echo htmlspecialchars($registre['description']); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <form method="POST" class="flex justify-center space-x-4">
                <a href="view.php?id=<?php echo $registre['id']; ?>" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                    Annuler
                </a>
                <button type="submit" class="bg-red-500 text-white px-6 py-2 rounded-lg hover:bg-red-600 transition-colors">
                    <i class="fas fa-trash mr-2"></i>Confirmer la suppression
                </button>
            </form>
        </div>
    </div>
</body>
</html> 