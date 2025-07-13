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

// Récupérer l'ID de la consultation
$consultation_id = $_GET['id'] ?? null;

if (!$consultation_id) {
    header('Location: dashboard.php');
    exit();
}

// Vérifier si la consultation existe
$consultation = $database->fetchOne("SELECT * FROM consultations WHERE id = ?", [$consultation_id]);

if (!$consultation) {
    header('Location: dashboard.php');
    exit();
}

// Traitement de la suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database->query("DELETE FROM consultations WHERE id = ?", [$consultation_id]);
        
        // Rediriger avec un message de succès
        header('Location: dashboard.php?message=Consultation supprimée avec succès');
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
    <title>Supprimer Consultation - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <h1 class="text-3xl font-bold text-gray-900">Supprimer Consultation</h1>
                    <a href="view.php?id=<?php echo $consultation_id; ?>" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Retour aux détails
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
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
                                        <p>Êtes-vous sûr de vouloir supprimer cette consultation ?</p>
                                        <p class="mt-2 font-medium">Cette action est irréversible.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Détails de la consultation à supprimer -->
                        <div class="mt-6 bg-gray-50 rounded-lg p-4">
                            <h4 class="text-lg font-medium text-gray-900 mb-3">Détails de la consultation</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-gray-700">Date :</span>
                                    <span class="text-gray-900"><?php echo htmlspecialchars($consultation['date_consultation']); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Type :</span>
                                    <span class="text-gray-900"><?php echo htmlspecialchars($consultation['type_consultation']); ?></span>
                                </div>
                                <?php if ($consultation['motif']): ?>
                                <div class="md:col-span-2">
                                    <span class="font-medium text-gray-700">Motif :</span>
                                    <span class="text-gray-900"><?php echo htmlspecialchars(substr($consultation['motif'], 0, 100)); ?><?php echo strlen($consultation['motif']) > 100 ? '...' : ''; ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Formulaire de confirmation -->
                        <form method="POST" class="mt-6">
                            <div class="flex justify-end space-x-3">
                                <a href="view.php?id=<?php echo $consultation_id; ?>" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                    Annuler
                                </a>
                                <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                    Confirmer la suppression
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 