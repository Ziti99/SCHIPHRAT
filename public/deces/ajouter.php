<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Clinique\Auth\Auth;
use Clinique\Config\Database;

$auth = new Auth();
$auth->requireAnyRole(['admin', 'medecin']);

$db = Database::getInstance();
$error = '';
$success = '';

// Récupération de l'ID de la patiente si fourni
$patiente_id = $_GET['patiente_id'] ?? null;

// Récupération de toutes les patientes pour le formulaire
$patientes = $db->fetchAll("
    SELECT id, nom, prenom, date_naissance, telephone
    FROM patientes 
    ORDER BY nom, prenom
");

// Récupération des médecins
$medecins = $db->fetchAll("
    SELECT id, nom, prenom, specialite
    FROM users 
    WHERE role = 'medecin' AND is_active = 1
    ORDER BY nom, prenom
");

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patiente_id = $_POST['patiente_id'] ?? '';
    $date_deces = $_POST['date_deces'] ?? '';
    $cause_deces = $_POST['cause_deces'] ?? '';
    $age_deces = $_POST['age_deces'] ?? '';
    $lieu_deces = $_POST['lieu_deces'] ?? '';
    $medecin_id = $_POST['medecin_id'] ?? '';
    $observations = $_POST['observations'] ?? '';
    
    if (empty($patiente_id) || empty($date_deces) || empty($cause_deces) || empty($medecin_id)) {
        $error = 'Veuillez remplir tous les champs obligatoires (patiente, date de décès, cause et médecin).';
    } else {
        try {
            $db->query("
                INSERT INTO deces (
                    patiente_id, date_deces, cause_deces, age_deces, lieu_deces, medecin_id, observations
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ", [
                $patiente_id, $date_deces, $cause_deces, 
                $age_deces ?: null, $lieu_deces, $medecin_id, $observations
            ]);
            
            $success = 'Décès enregistré avec succès !';
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
    <title>Nouveau Décès - Clinique Obstétrique</title>
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
                                <span class="text-xl font-bold text-gray-900">Nouveau Décès</span>
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
                <!-- Messages -->
                <?php if ($error): ?>
                    <div id="message-error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 relative">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                        <button onclick="closeMessage('message-error')" class="absolute top-0 right-0 mt-2 mr-2 text-red-600 hover:text-red-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div id="message-success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 relative">
                        <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                        <button onclick="closeMessage('message-success')" class="absolute top-0 right-0 mt-2 mr-2 text-green-600 hover:text-green-800">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                <?php endif; ?>
                
                <!-- En-tête -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Nouveau Décès</h1>
                    <p class="text-gray-600">Enregistrer un nouveau décès</p>
                </div>

                <!-- Formulaire -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <form method="POST" class="space-y-6">
                        <!-- Informations de base -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="patiente_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Patiente <span class="text-red-500">*</span>
                                </label>
                                <select name="patiente_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                                    <option value="">Sélectionner une patiente</option>
                                    <?php foreach ($patientes as $patiente): ?>
                                        <option value="<?php echo $patiente['id']; ?>" <?php echo $patiente_id == $patiente['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($patiente['prenom'] . ' ' . $patiente['nom']); ?>
                                            (<?php echo date('d/m/Y', strtotime($patiente['date_naissance'])); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="date_deces" class="block text-sm font-medium text-gray-700 mb-2">
                                    Date et heure du décès <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" name="date_deces" required
                                       value="<?php echo date('Y-m-d\TH:i'); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="cause_deces" class="block text-sm font-medium text-gray-700 mb-2">
                                    Cause du décès <span class="text-red-500">*</span>
                                </label>
                                <textarea name="cause_deces" rows="3" required
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                                          placeholder="Décrivez la cause du décès..."><?php echo htmlspecialchars($_POST['cause_deces'] ?? ''); ?></textarea>
                            </div>
                            
                            <div>
                                <label for="medecin_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Médecin déclarant <span class="text-red-500">*</span>
                                </label>
                                <select name="medecin_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                                    <option value="">Sélectionner un médecin</option>
                                    <?php foreach ($medecins as $medecin): ?>
                                        <option value="<?php echo $medecin['id']; ?>">
                                            Dr. <?php echo htmlspecialchars($medecin['nom'] . ' ' . $medecin['prenom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="age_deces" class="block text-sm font-medium text-gray-700 mb-2">
                                    Âge au décès
                                </label>
                                <input type="number" name="age_deces" min="0" max="120"
                                       value="<?php echo htmlspecialchars($_POST['age_deces'] ?? ''); ?>"
                                       placeholder="Âge en années"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            
                            <div>
                                <label for="lieu_deces" class="block text-sm font-medium text-gray-700 mb-2">
                                    Lieu du décès
                                </label>
                                <input type="text" name="lieu_deces"
                                       value="<?php echo htmlspecialchars($_POST['lieu_deces'] ?? ''); ?>"
                                       placeholder="ex: Hôpital, domicile, route..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>

                        <!-- Observations -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Observations</h3>
                            
                            <div>
                                <label for="observations" class="block text-sm font-medium text-gray-700 mb-2">
                                    Observations complémentaires
                                </label>
                                <textarea name="observations" rows="4"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"
                                          placeholder="Observations complémentaires sur les circonstances du décès..."><?php echo htmlspecialchars($_POST['observations'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <!-- Boutons -->
                        <div class="flex justify-end space-x-4 pt-6">
                            <a href="../deces.php" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                                Annuler
                            </a>
                            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-md hover:shadow-lg transition-all duration-300">
                                <i class="fas fa-save mr-2"></i>Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function closeMessage(messageId) {
            const message = document.getElementById(messageId);
            if (message) {
                message.style.display = 'none';
            }
        }
    </script>
</body>
</html> 