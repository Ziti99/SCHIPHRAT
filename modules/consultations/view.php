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
$message = '';

// Récupérer l'ID de la consultation
$consultation_id = $_GET['id'] ?? null;

if (!$consultation_id) {
    header('Location: dashboard.php');
    exit();
}

// Récupérer les détails de la consultation avec les informations de la patiente
$consultation = $database->fetchOne("
    SELECT c.*, p.nom as patiente_nom, p.prenom as patiente_prenom, p.date_naissance, p.telephone, p.adresse
    FROM consultations c
    JOIN patientes p ON c.patiente_id = p.id
    WHERE c.id = ?
", [$consultation_id]);

if (!$consultation) {
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Consultation - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <h1 class="text-3xl font-bold text-gray-900">Détails Consultation</h1>
                    <div class="flex space-x-3">
                        <a href="edit.php?id=<?php echo $consultation_id; ?>" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Modifier
                        </a>
                        <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            Retour au tableau de bord
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <!-- Informations de la patiente -->
                        <div class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Informations de la patiente</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Nom complet</label>
                                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($consultation['patiente_nom'] . ' ' . $consultation['patiente_prenom']); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Date de naissance</label>
                                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($consultation['date_naissance']); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($consultation['telephone']); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Adresse</label>
                                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($consultation['adresse']); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Détails de la consultation -->
                        <div class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4">Détails de la consultation</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Date de consultation</label>
                                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($consultation['date_consultation']); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Type de consultation</label>
                                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($consultation['type_consultation']); ?></p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Prochaine consultation</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        <?php echo $consultation['prochaine_consultation'] ? htmlspecialchars($consultation['prochaine_consultation']) : 'Non programmée'; ?>
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Date de création</label>
                                    <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($consultation['created_at']); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Motif -->
                        <?php if ($consultation['motif']): ?>
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700">Motif de la consultation</label>
                            <p class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded"><?php echo nl2br(htmlspecialchars($consultation['motif'])); ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- Examen clinique -->
                        <?php if ($consultation['examen_clinique']): ?>
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700">Examen clinique</label>
                            <p class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded"><?php echo nl2br(htmlspecialchars($consultation['examen_clinique'])); ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- Diagnostic -->
                        <?php if ($consultation['diagnostic']): ?>
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700">Diagnostic</label>
                            <p class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded"><?php echo nl2br(htmlspecialchars($consultation['diagnostic'])); ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- Traitement -->
                        <?php if ($consultation['traitement']): ?>
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700">Traitement prescrit</label>
                            <p class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded"><?php echo nl2br(htmlspecialchars($consultation['traitement'])); ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- Observations -->
                        <?php if ($consultation['observations']): ?>
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700">Observations</label>
                            <p class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded"><?php echo nl2br(htmlspecialchars($consultation['observations'])); ?></p>
                        </div>
                        <?php endif; ?>

                        <!-- Boutons d'action -->
                        <div class="flex justify-end space-x-3 pt-6 border-t">
                            <a href="edit.php?id=<?php echo $consultation_id; ?>" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Modifier
                            </a>
                            <a href="delete.php?id=<?php echo $consultation_id; ?>" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette consultation ?')">
                                Supprimer
                            </a>
                            <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Retour
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 