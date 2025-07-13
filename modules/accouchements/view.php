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
$message = '';

// Récupérer l'ID de l'accouchement
$accouchement_id = $_GET['id'] ?? null;

if (!$accouchement_id) {
    header('Location: dashboard.php');
    exit();
}

// Récupérer les détails de l'accouchement avec les informations de la patiente
$accouchement = $database->fetchOne("
    SELECT a.*, p.nom as patiente_nom, p.prenom as patiente_prenom, p.date_naissance, p.telephone, p.adresse
    FROM accouchements a
    JOIN patientes p ON a.patiente_id = p.id
    WHERE a.id = ?
", [$accouchement_id]);

if (!$accouchement) {
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Accouchement - Clinique Obstétrique</title>
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
                        <i class="fas fa-baby text-2xl text-pink-600 mr-3"></i>
                        <span class="text-xl font-bold text-gray-900">Détails Accouchement</span>
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
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Détails Accouchement</h1>
            <p class="text-gray-600">Informations complètes de l'accouchement</p>
        </div>

        <!-- Contenu -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Informations de l'accouchement</h2>
            </div>
            <div class="p-6">
                <!-- Informations de la patiente -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations de la patiente</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nom complet</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($accouchement['patiente_nom'] . ' ' . $accouchement['patiente_prenom']); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date de naissance</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($accouchement['date_naissance']); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($accouchement['telephone']); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Adresse</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($accouchement['adresse']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Détails de l'accouchement -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Détails de l'accouchement</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date d'accouchement</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($accouchement['date_accouchement']); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type d'accouchement</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($accouchement['type_accouchement']); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Heure de début</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo $accouchement['heure_debut'] ? htmlspecialchars($accouchement['heure_debut']) : 'Non renseignée'; ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Heure de fin</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo $accouchement['heure_fin'] ? htmlspecialchars($accouchement['heure_fin']) : 'Non renseignée'; ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Présentation</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo $accouchement['presentation'] ? htmlspecialchars($accouchement['presentation']) : 'Non renseignée'; ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date de création</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($accouchement['created_at']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Informations du bébé -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations du bébé</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sexe</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo $accouchement['sexe_bebe'] ? htmlspecialchars($accouchement['sexe_bebe']) : 'Non renseigné'; ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Poids (g)</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo $accouchement['poids_bebe'] ? htmlspecialchars($accouchement['poids_bebe']) . ' g' : 'Non renseigné'; ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Taille (cm)</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo $accouchement['taille_bebe'] ? htmlspecialchars($accouchement['taille_bebe']) . ' cm' : 'Non renseignée'; ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Score APGAR</label>
                            <p class="mt-1 text-sm text-gray-900"><?php echo $accouchement['apgar_score'] ? htmlspecialchars($accouchement['apgar_score']) : 'Non renseigné'; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Complications -->
                <?php if ($accouchement['complications']): ?>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700">Complications</label>
                    <p class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded"><?php echo nl2br(htmlspecialchars($accouchement['complications'])); ?></p>
                </div>
                <?php endif; ?>

                <!-- Interventions -->
                <?php if ($accouchement['interventions']): ?>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700">Interventions réalisées</label>
                    <p class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded"><?php echo nl2br(htmlspecialchars($accouchement['interventions'])); ?></p>
                </div>
                <?php endif; ?>

                <!-- Observations -->
                <?php if ($accouchement['observations']): ?>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700">Observations</label>
                    <p class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded"><?php echo nl2br(htmlspecialchars($accouchement['observations'])); ?></p>
                </div>
                <?php endif; ?>

                <!-- Boutons d'action -->
                <div class="flex justify-end space-x-3 pt-6 border-t">
                    <a href="edit.php?id=<?php echo $accouchement_id; ?>" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-all duration-300">
                        <i class="fas fa-edit mr-2"></i>Modifier
                    </a>
                    <a href="delete.php?id=<?php echo $accouchement_id; ?>" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition-all duration-300" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet accouchement ?')">
                        <i class="fas fa-trash mr-2"></i>Supprimer
                    </a>
                    <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition-all duration-300">
                        <i class="fas fa-arrow-left mr-2"></i>Retour
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 