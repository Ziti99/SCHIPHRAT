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

$suivi = $db->fetchOne("SELECT * FROM suivi_postnatal WHERE id = ?", [$id]);

if (!$suivi) {
    header('Location: dashboard.php');
    exit;
}

// Récupérer les patientes pour le select
$patientes = $db->fetchAll("SELECT id, nom, prenom FROM patientes ORDER BY nom, prenom");

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patiente_id = (int)$_POST['patiente_id'];
    $date_consultation = $_POST['date_consultation'];
    $poids = $_POST['poids'] ? (float)$_POST['poids'] : null;
    $tension_arterielle = trim($_POST['tension_arterielle']);
    $temperature = $_POST['temperature'] ? (float)$_POST['temperature'] : null;
    $observations = trim($_POST['observations']);
    $recommandations = trim($_POST['recommandations']);
    $prochaine_consultation = $_POST['prochaine_consultation'] ?: null;
    
    if (!$patiente_id) {
        $error = 'La patiente est requise';
    } elseif (!$date_consultation) {
        $error = 'La date de consultation est requise';
    } else {
        try {
            $db->query("
                UPDATE suivi_postnatal 
                SET patiente_id = ?, date_consultation = ?, poids = ?, tension_arterielle = ?, temperature = ?, observations = ?, recommandations = ?, prochaine_consultation = ?
                WHERE id = ?
            ", [$patiente_id, $date_consultation, $poids, $tension_arterielle, $temperature, $observations, $recommandations, $prochaine_consultation, $id]);
            
            $message = 'Suivi postnatal modifié avec succès';
            $suivi = $db->fetchOne("SELECT * FROM suivi_postnatal WHERE id = ?", [$id]);
        } catch (Exception $e) {
            $error = 'Erreur lors de la modification du suivi postnatal';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le Suivi Postnatal - Clinique Obstétrique</title>
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
                        <span class="text-xl font-bold text-gray-900">Modifier le Suivi Postnatal</span>
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
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Modifier le Suivi Postnatal</h1>
            <p class="text-gray-600">Modifier les informations du suivi postnatal</p>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="patiente_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Patiente *
                        </label>
                        <select name="patiente_id" id="patiente_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                            <option value="">Sélectionner une patiente</option>
                            <?php foreach ($patientes as $patiente): ?>
                                <option value="<?php echo $patiente['id']; ?>" <?php echo $suivi['patiente_id'] == $patiente['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($patiente['nom'] . ' ' . $patiente['prenom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="date_consultation" class="block text-sm font-medium text-gray-700 mb-2">
                            Date de Consultation *
                        </label>
                        <input type="date" name="date_consultation" id="date_consultation" required value="<?php echo $suivi['date_consultation']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="poids" class="block text-sm font-medium text-gray-700 mb-2">
                            Poids (kg)
                        </label>
                        <input type="number" step="0.1" name="poids" id="poids" value="<?php echo $suivi['poids']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="Ex: 65.5">
                    </div>

                    <div>
                        <label for="tension_arterielle" class="block text-sm font-medium text-gray-700 mb-2">
                            Tension Artérielle
                        </label>
                        <input type="text" name="tension_arterielle" id="tension_arterielle" value="<?php echo htmlspecialchars($suivi['tension_arterielle']); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="Ex: 120/80">
                    </div>

                    <div>
                        <label for="temperature" class="block text-sm font-medium text-gray-700 mb-2">
                            Température (°C)
                        </label>
                        <input type="number" step="0.1" name="temperature" id="temperature" value="<?php echo $suivi['temperature']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="Ex: 37.2">
                    </div>
                </div>

                <div>
                    <label for="observations" class="block text-sm font-medium text-gray-700 mb-2">
                        Observations
                    </label>
                    <textarea name="observations" id="observations" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="Observations cliniques..."><?php echo htmlspecialchars($suivi['observations']); ?></textarea>
                </div>

                <div>
                    <label for="recommandations" class="block text-sm font-medium text-gray-700 mb-2">
                        Recommandations
                    </label>
                    <textarea name="recommandations" id="recommandations" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent" placeholder="Recommandations pour la patiente..."><?php echo htmlspecialchars($suivi['recommandations']); ?></textarea>
                </div>

                <div>
                    <label for="prochaine_consultation" class="block text-sm font-medium text-gray-700 mb-2">
                        Prochaine Consultation
                    </label>
                    <input type="date" name="prochaine_consultation" id="prochaine_consultation" value="<?php echo $suivi['prochaine_consultation']; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent">
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="view.php?id=<?php echo $suivi['id']; ?>" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                        Annuler
                    </a>
                    <button type="submit" class="bg-gradient-to-r from-pink-500 to-purple-500 text-white px-6 py-2 rounded-lg hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-save mr-2"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 