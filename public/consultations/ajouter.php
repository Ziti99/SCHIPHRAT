<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Clinique\Auth\Auth;
use Clinique\Config\Database;

$auth = new Auth();
$auth->requireAnyRole(['admin', 'medecin', 'sage_femme']);

$db = Database::getInstance();
$error = '';
$success = '';

// Récupération de toutes les patientes
$patientes = $db->fetchAll("
    SELECT p.*
    FROM patientes p
    ORDER BY p.nom, p.prenom
");

// Récupération des médecins
$medecins = $db->fetchAll("
    SELECT id, nom, prenom, specialite
    FROM users 
    WHERE role IN ('medecin', 'sage_femme') AND is_active = 1
    ORDER BY nom, prenom
");

// Récupération des actes disponibles
$actes = $db->fetchAll("
    SELECT id, nom_acte, montant, description
    FROM actes_poses 
    WHERE is_active = 1
    ORDER BY nom_acte
");

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patiente_id = $_POST['patiente_id'] ?? '';
    $medecin_id = $_POST['medecin_id'] ?? '';
    $date_consultation = $_POST['date_consultation'] ?? '';
    $acte_pose = $_POST['acte_pose'] ?? '';
    $observations = $_POST['observations'] ?? '';
    
    if (empty($patiente_id) || empty($medecin_id) || empty($date_consultation)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } else {
        try {
            $db->query("
                INSERT INTO consultations_prenatales (
                    patiente_id, medecin_id, date_consultation,
                    acte_pose, observations
                ) VALUES (?, ?, ?, ?, ?)
            ", [
                $patiente_id, $medecin_id, $date_consultation,
                $acte_pose, $observations
            ]);
            
            $success = 'Consultation enregistrée avec succès !';
        } catch (Exception $e) {
            $error = 'Erreur lors de l\'enregistrement : ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Consultation - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#8B5CF6',
                        secondary: '#EC4899',
                        accent: '#06B6D4'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-heartbeat text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                            Clinique Obstétrique
                        </h1>
                        <p class="text-xs text-gray-500">Nouvelle consultation</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($auth->getCurrentUserName()); ?></p>
                        <p class="text-xs text-gray-500 capitalize"><?php echo str_replace('_', ' ', $auth->getCurrentUserRole()); ?></p>
                    </div>
                    <a href="/logout.php" class="text-gray-600 hover:text-red-600 transition-colors">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        <!-- Contenu principal -->
        <main class="flex-1 p-8">
            <!-- En-tête -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Nouvelle consultation prénatale</h2>
                    <p class="text-gray-600">Enregistrer une nouvelle consultation</p>
                </div>
                <a href="/consultations.php" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour aux consultations
                </a>
            </div>

            <!-- Messages -->
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                    <i class="fas fa-exclamation-circle mr-3"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div id="message-success" class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center relative">
                    <i class="fas fa-check-circle mr-3"></i>
                    <?php echo htmlspecialchars($success); ?>
                    <button onclick="closeMessage('message-success')" class="absolute top-0 right-0 mt-2 mr-2 text-green-600 hover:text-green-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Formulaire -->
            <div class="bg-white rounded-xl shadow-lg p-8">
                <form method="POST" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Patiente -->
                        <div>
                            <label for="patiente_id" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user-injured mr-2"></i>Patiente *
                            </label>
                            <select name="patiente_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                                <option value="">Sélectionner une patiente</option>
                                <?php foreach ($patientes as $patiente): ?>
                                    <option value="<?php echo $patiente['id']; ?>" <?php echo (isset($_POST['patiente_id']) && $_POST['patiente_id'] == $patiente['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($patiente['prenom'] . ' ' . $patiente['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Médecin -->
                        <div>
                            <label for="medecin_id" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user-md mr-2"></i>Médecin/Sage-femme *
                            </label>
                            <select name="medecin_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200">
                                <option value="">Sélectionner un médecin</option>
                                <?php foreach ($medecins as $medecin): ?>
                                    <option value="<?php echo $medecin['id']; ?>" <?php echo (isset($_POST['medecin_id']) && $_POST['medecin_id'] == $medecin['id']) ? 'selected' : ''; ?>>
                                        Dr. <?php echo htmlspecialchars($medecin['nom'] . ' ' . $medecin['prenom']); ?>
                                        <?php if ($medecin['specialite']): ?>
                                            (<?php echo htmlspecialchars($medecin['specialite']); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date de consultation -->
                        <div>
                            <label for="date_consultation" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-calendar mr-2"></i>Date et heure de consultation *
                            </label>
                            <input 
                                type="datetime-local" 
                                name="date_consultation" 
                                value="<?php echo isset($_POST['date_consultation']) ? $_POST['date_consultation'] : date('Y-m-d\TH:i'); ?>"
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                            >
                        </div>

                        <!-- Acte posé -->
                        <div>
                            <label for="acte_pose" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-stethoscope mr-2"></i>Acte posé <span class="text-red-500">*</span>
                            </label>
                            <select 
                                name="acte_pose" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                            >
                                <option value="">Sélectionner un acte</option>
                                <?php foreach ($actes as $acte): ?>
                                    <option value="<?php echo htmlspecialchars($acte['nom_acte']); ?>" 
                                            <?php echo (isset($_POST['acte_pose']) && $_POST['acte_pose'] == $acte['nom_acte']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($acte['nom_acte']); ?>
                                        <?php if ($acte['montant']): ?>
                                            (<?php echo number_format($acte['montant'], 0, ',', ' '); ?> FCFA)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Observations -->
                    <div>
                        <label for="observations" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-notes-medical mr-2"></i>Observations
                        </label>
                        <textarea 
                            name="observations" 
                            rows="4"
                            placeholder="Observations médicales, symptômes, etc."
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200"
                        ><?php echo htmlspecialchars($_POST['observations'] ?? ''); ?></textarea>
                    </div>

                        <!-- Boutons -->
                        <div class="flex justify-end space-x-4">
                            <a href="/consultations.php" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition-colors">
                                <i class="fas fa-times mr-2"></i>
                                Annuler
                            </a>
                            <button 
                                type="submit" 
                                class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-8 py-3 rounded-lg font-semibold hover:shadow-lg transition-all duration-300 transform hover:scale-105"
                            >
                                <i class="fas fa-save mr-2"></i>
                                Enregistrer la consultation
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <script>
function closeMessage(id) {
    var el = document.getElementById(id);
    if (el) el.style.display = 'none';
}
</script>
</body>
</html> 