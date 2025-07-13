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

$suivi = $db->fetchOne("
    SELECT sp.*, p.nom, p.prenom, u.nom as nom_utilisateur, u.prenom as prenom_utilisateur
    FROM suivi_postnatal sp
    JOIN patientes p ON sp.patiente_id = p.id
    JOIN utilisateurs u ON sp.utilisateur_id = u.id
    WHERE sp.id = ?
", [$id]);

if (!$suivi) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails du Suivi Postnatal - Clinique Obstétrique</title>
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
                        <i class="fas fa-heartbeat text-2xl text-pink-600 mr-3"></i>
                        <span class="text-xl font-bold text-gray-900">Détails du Suivi Postnatal</span>
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
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Détails du Suivi Postnatal</h1>
            <p class="text-gray-600">Informations détaillées du suivi postnatal</p>
        </div>

        <!-- Actions -->
        <div class="mb-6 flex space-x-4">
            <a href="edit.php?id=<?php echo $suivi['id']; ?>" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                <i class="fas fa-edit mr-2"></i>Modifier
            </a>
            <a href="delete.php?id=<?php echo $suivi['id']; ?>" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce suivi postnatal ?')">
                <i class="fas fa-trash mr-2"></i>Supprimer
            </a>
        </div>

        <!-- Détails du suivi -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations Générales</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Patiente</label>
                            <p class="text-lg text-gray-900"><?php echo htmlspecialchars($suivi['nom'] . ' ' . $suivi['prenom']); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Date de Consultation</label>
                            <p class="text-lg text-gray-900"><?php echo date('d/m/Y', strtotime($suivi['date_consultation'])); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Créé par</label>
                            <p class="text-lg text-gray-900"><?php echo htmlspecialchars($suivi['nom_utilisateur'] . ' ' . $suivi['prenom_utilisateur']); ?></p>
                        </div>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Paramètres Cliniques</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Poids</label>
                            <p class="text-lg text-gray-900"><?php echo $suivi['poids'] ? $suivi['poids'] . ' kg' : 'Non renseigné'; ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Tension Artérielle</label>
                            <p class="text-lg text-gray-900"><?php echo $suivi['tension_arterielle'] ?: 'Non renseigné'; ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Température</label>
                            <p class="text-lg text-gray-900"><?php echo $suivi['temperature'] ? $suivi['temperature'] . ' °C' : 'Non renseigné'; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-8 space-y-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Observations</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <?php if ($suivi['observations']): ?>
                            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($suivi['observations'])); ?></p>
                        <?php else: ?>
                            <p class="text-gray-500 italic">Aucune observation</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Recommandations</h3>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <?php if ($suivi['recommandations']): ?>
                            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($suivi['recommandations'])); ?></p>
                        <?php else: ?>
                            <p class="text-gray-500 italic">Aucune recommandation</p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($suivi['prochaine_consultation']): ?>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Prochaine Consultation</h3>
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <p class="text-blue-700 font-medium"><?php echo date('d/m/Y', strtotime($suivi['prochaine_consultation'])); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 