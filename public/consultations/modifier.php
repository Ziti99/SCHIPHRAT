<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Clinique\Auth\Auth;
use Clinique\Config\Database;

$auth = new Auth();
$auth->requireAnyRole(['admin', 'medecin', 'sage_femme']);

$db = Database::getInstance();

$consultation_id = $_GET['id'] ?? null;

if (!$consultation_id) {
    header('Location: /consultations.php');
    exit;
}

// Récupération de la consultation
$consultation = $db->fetch("
    SELECT cp.*, 
           p.nom, p.prenom
    FROM consultations_prenatales cp
    JOIN patientes p ON cp.patiente_id = p.id
    WHERE cp.id = ?
", [$consultation_id]);

if (!$consultation) {
    header('Location: /consultations.php');
    exit;
}

// Récupération des patientes pour le select
$patientes = $db->fetchAll("
    SELECT id, nom, prenom, telephone 
    FROM patientes 
    ORDER BY nom, prenom
");

// Récupération des médecins
$medecins = $db->fetchAll("
    SELECT id, nom, prenom, specialite
    FROM users 
    WHERE role IN ('medecin', 'sage_femme') AND is_active = 1
    ORDER BY nom, prenom
");

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $patiente_id = $_POST['patiente_id'] ?? '';
        $medecin_id = $_POST['medecin_id'] ?? '';
        $date_consultation = $_POST['date_consultation'] ?? '';
        $tension_arterielle = $_POST['tension_arterielle'] ?? '';
        $poids = $_POST['poids'] ?? '';
        $hauteur_uterine = $_POST['hauteur_uterine'] ?? '';
        $position_foetus = $_POST['position_foetus'] ?? '';
        $frequence_cardiaque_foetale = $_POST['frequence_cardiaque_foetale'] ?? '';
        $observations = $_POST['observations'] ?? '';
        $recommandations = $_POST['recommandations'] ?? '';
        $acte_pose = $_POST['acte_pose'] ?? '';
        $dpa = $_POST['dpa'] ?? '';

        // Validation
        if (empty($patiente_id) || empty($medecin_id) || empty($date_consultation)) {
            throw new Exception('Les champs obligatoires doivent être remplis.');
        }

        // Mise à jour de la consultation
        $db->query("
            UPDATE consultations_prenatales SET
                patiente_id = ?,
                medecin_id = ?,
                date_consultation = ?,
                tension_arterielle = ?,
                poids = ?,
                hauteur_uterine = ?,
                position_foetus = ?,
                frequence_cardiaque_foetale = ?,
                observations = ?,
                recommandations = ?,
                acte_pose = ?,
                dpa = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ", [
            $patiente_id,
            $medecin_id,
            $date_consultation,
            $tension_arterielle,
            $poids ?: null,
            $hauteur_uterine ?: null,
            $position_foetus,
            $frequence_cardiaque_foetale ?: null,
            $observations,
            $recommandations,
            $acte_pose,
            $dpa ?: null,

            $consultation_id
        ]);

        $success_message = "Consultation modifiée avec succès !";
        
        // Redirection après 2 secondes
        header("refresh:2;url=/consultations.php");
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Consultation - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="flex">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>
        
        <!-- Contenu principal -->
        <div class="flex-1">
            <!-- Navigation -->
            <nav class="bg-white shadow-lg border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <a href="/consultations.php" class="text-purple-600 hover:text-purple-800 mr-4">
                                <i class="fas fa-arrow-left mr-2"></i>Retour
                            </a>
                            <div class="flex-shrink-0 flex items-center">
                                <i class="fas fa-edit text-2xl text-blue-600 mr-3"></i>
                                <span class="text-xl font-bold text-gray-900">Modifier Consultation</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-gray-700">
                                <i class="fas fa-user mr-2"></i>
                                <?php echo htmlspecialchars($auth->getCurrentUserName()); ?>
                            </span>
                            <a href="/logout.php" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                            </a>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- Messages -->
                <?php if (isset($success_message)): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 flex justify-between items-center">
                        <div>
                            <i class="fas fa-check-circle mr-2"></i>
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-green-700 hover:text-green-900">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 flex justify-between items-center">
                        <div>
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-red-700 hover:text-red-900">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- En-tête -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900">Modifier Consultation #<?php echo $consultation_id; ?></h1>
                    <p class="text-gray-600">Modifiez les informations de la consultation prénatale</p>
                </div>

                <!-- Formulaire -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Patiente -->
                            <div>
                                <label for="patiente_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-user mr-2"></i>Patiente *
                                </label>
                                <select name="patiente_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    <option value="">Sélectionner une patiente</option>
                                    <?php foreach ($patientes as $patiente): ?>
                                        <option value="<?php echo $patiente['id']; ?>" <?php echo $consultation['patiente_id'] == $patiente['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($patiente['prenom'] . ' ' . $patiente['nom'] . ' - ' . $patiente['telephone']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Médecin -->
                            <div>
                                <label for="medecin_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-user-md mr-2"></i>Médecin *
                                </label>
                                <select name="medecin_id" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                    <option value="">Sélectionner un médecin</option>
                                    <?php foreach ($medecins as $medecin): ?>
                                        <option value="<?php echo $medecin['id']; ?>" <?php echo $consultation['medecin_id'] == $medecin['id'] ? 'selected' : ''; ?>>
                                            Dr. <?php echo htmlspecialchars($medecin['prenom'] . ' ' . $medecin['nom']); ?>
                                            <?php if ($medecin['specialite']): ?>
                                                (<?php echo htmlspecialchars($medecin['specialite']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Date consultation -->
                            <div>
                                <label for="date_consultation" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-calendar mr-2"></i>Date et heure consultation *
                                </label>
                                <input 
                                    type="datetime-local" 
                                    name="date_consultation" 
                                    value="<?php echo date('Y-m-d\TH:i', strtotime($consultation['date_consultation'])); ?>"
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                >
                            </div>

                            <!-- DPA -->
                            <div>
                                <label for="dpa" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-calendar-alt mr-2"></i>DPA
                                </label>
                                <input 
                                    type="date" 
                                    name="dpa" 
                                    value="<?php echo $consultation['dpa'] ? date('Y-m-d', strtotime($consultation['dpa'])) : ''; ?>"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                >
                            </div>

                            <!-- Tension artérielle -->
                            <div>
                                <label for="tension_arterielle" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-heartbeat mr-2"></i>Tension artérielle
                                </label>
                                <input 
                                    type="text" 
                                    name="tension_arterielle" 
                                    value="<?php echo htmlspecialchars($consultation['tension_arterielle'] ?? ''); ?>"
                                    placeholder="ex: 12/8"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                >
                            </div>

                            <!-- Poids -->
                            <div>
                                <label for="poids" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-weight mr-2"></i>Poids (kg)
                                </label>
                                <input 
                                    type="number" 
                                    name="poids" 
                                    step="0.1"
                                    value="<?php echo htmlspecialchars($consultation['poids'] ?? ''); ?>"
                                    placeholder="ex: 65.5"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                >
                            </div>

                            <!-- Hauteur utérine -->
                            <div>
                                <label for="hauteur_uterine" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-ruler-vertical mr-2"></i>Hauteur utérine (cm)
                                </label>
                                <input 
                                    type="number" 
                                    name="hauteur_uterine" 
                                    value="<?php echo htmlspecialchars($consultation['hauteur_uterine'] ?? ''); ?>"
                                    placeholder="ex: 28"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                >
                            </div>

                            <!-- Position fœtus -->
                            <div>
                                <label for="position_foetus" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-baby mr-2"></i>Position fœtus
                                </label>
                                <input 
                                    type="text" 
                                    name="position_foetus" 
                                    value="<?php echo htmlspecialchars($consultation['position_foetus'] ?? ''); ?>"
                                    placeholder="ex: Céphalique"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                >
                            </div>

                            <!-- Fréquence cardiaque fœtale -->
                            <div>
                                <label for="frequence_cardiaque_foetale" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-heart mr-2"></i>Fréquence cardiaque fœtale (bpm)
                                </label>
                                <input 
                                    type="number" 
                                    name="frequence_cardiaque_foetale" 
                                    value="<?php echo htmlspecialchars($consultation['frequence_cardiaque_foetale'] ?? ''); ?>"
                                    placeholder="ex: 140"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                >
                            </div>


                        </div>

                        <!-- Acte posé -->
                        <div>
                            <label for="acte_pose" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-stethoscope mr-2"></i>Acte posé
                            </label>
                            <input 
                                type="text" 
                                name="acte_pose" 
                                value="<?php echo htmlspecialchars($consultation['acte_pose'] ?? ''); ?>"
                                placeholder="ex: Consultation prénatale, Échographie..."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            >
                        </div>

                        <!-- Observations -->
                        <div>
                            <label for="observations" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-clipboard-list mr-2"></i>Observations
                            </label>
                            <textarea 
                                name="observations" 
                                rows="4"
                                placeholder="Observations cliniques..."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            ><?php echo htmlspecialchars($consultation['observations'] ?? ''); ?></textarea>
                        </div>

                        <!-- Recommandations -->
                        <div>
                            <label for="recommandations" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-lightbulb mr-2"></i>Recommandations
                            </label>
                            <textarea 
                                name="recommandations" 
                                rows="4"
                                placeholder="Recommandations pour la patiente..."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                            ><?php echo htmlspecialchars($consultation['recommandations'] ?? ''); ?></textarea>
                        </div>

                        <!-- Boutons -->
                        <div class="flex justify-end space-x-4 pt-6">
                            <a href="/consultations.php" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                                <i class="fas fa-times mr-2"></i>Annuler
                            </a>
                            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-lg hover:shadow-lg transition-all duration-300 transform hover:scale-105">
                                <i class="fas fa-save mr-2"></i>Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 