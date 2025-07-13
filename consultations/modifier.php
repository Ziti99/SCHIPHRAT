<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$db = new Database();

// Récupérer l'ID de la consultation
$consultation_id = $_GET['id'] ?? 0;

if (!$consultation_id) {
    header('Location: /consultations.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tension_arterielle = $_POST['tension_arterielle'] ?? '';
    $poids = $_POST['poids'] ?? '';
    $hauteur_uterine = $_POST['hauteur_uterine'] ?? '';
    $position_foetus = $_POST['position_foetus'] ?? '';
    $frequence_cardiaque_foetale = $_POST['frequence_cardiaque_foetale'] ?? '';
    $observations = $_POST['observations'] ?? '';
    $recommandations = $_POST['recommandations'] ?? '';
    
    try {
        $db->query("
            UPDATE consultations_prenatales 
            SET tension_arterielle = ?, poids = ?, hauteur_uterine = ?, position_foetus = ?, 
                frequence_cardiaque_foetale = ?, observations = ?, recommandations = ?
            WHERE id = ?
        ", [$tension_arterielle, $poids, $hauteur_uterine, $position_foetus, 
             $frequence_cardiaque_foetale, $observations, $recommandations, $consultation_id]);
        
        header('Location: voir.php?id=' . $consultation_id . '&success=1');
        exit;
    } catch (Exception $e) {
        $error = "Erreur lors de la modification : " . $e->getMessage();
    }
}

// Récupérer les détails de la consultation
$consultation = $db->fetch("
    SELECT c.*, p.nom, p.prenom, p.date_naissance, p.nationalite, p.telephone, p.adresse, p.groupe_sanguin,
           medecin.nom as medecin_nom, medecin.prenom as medecin_prenom, medecin.specialite
    FROM consultations_prenatales c
    JOIN patientes p ON c.patiente_id = p.id
    JOIN users medecin ON c.medecin_id = medecin.id
    WHERE c.id = ?
", [$consultation_id]);

if (!$consultation) {
    header('Location: /consultations.php');
    exit;
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
<body class="bg-gradient-to-br from-gray-50 via-blue-50 to-indigo-50 min-h-screen">
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
                            <a href="voir.php?id=<?php echo $consultation_id; ?>" class="text-purple-600 hover:text-purple-800 mr-4">
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
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </span>
                            <a href="../logout.php" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                            </a>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- En-tête -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-900">Modifier la Consultation</h2>
                    <p class="text-gray-600">Consultation #<?php echo $consultation_id; ?> - <?php echo htmlspecialchars($consultation['prenom'] . ' ' . $consultation['nom']); ?></p>
                </div>

                <!-- Messages d'erreur -->
                <?php if (isset($error)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle mr-2 mt-1"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Informations de la patiente (lecture seule) -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-user mr-2 text-blue-600"></i>Informations de la Patiente
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nom complet</label>
                            <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($consultation['prenom'] . ' ' . $consultation['nom']); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date de consultation</label>
                            <p class="text-gray-900"><?php echo date('d/m/Y H:i', strtotime($consultation['date_consultation'])); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Médecin</label>
                            <p class="text-gray-900">Dr. <?php echo htmlspecialchars($consultation['medecin_prenom'] . ' ' . $consultation['medecin_nom']); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Spécialité</label>
                            <p class="text-gray-900"><?php echo htmlspecialchars($consultation['specialite'] ?: 'Non défini'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Formulaire de modification -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">
                        <i class="fas fa-stethoscope mr-2 text-blue-600"></i>Modifier les Détails
                    </h3>
                    
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tension artérielle</label>
                                <input type="text" name="tension_arterielle" value="<?php echo htmlspecialchars($consultation['tension_arterielle'] ?? ''); ?>" 
                                       placeholder="Ex: 120/80" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Poids (kg)</label>
                                <input type="number" name="poids" value="<?php echo htmlspecialchars($consultation['poids'] ?? ''); ?>" 
                                       step="0.1" min="0" placeholder="Ex: 65.5" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Hauteur utérine (cm)</label>
                                <input type="number" name="hauteur_uterine" value="<?php echo htmlspecialchars($consultation['hauteur_uterine'] ?? ''); ?>" 
                                       step="0.1" min="0" placeholder="Ex: 25.5" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Position du fœtus</label>
                                <select name="position_foetus" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Sélectionner</option>
                                    <option value="Céphalique" <?php echo ($consultation['position_foetus'] === 'Céphalique') ? 'selected' : ''; ?>>Céphalique</option>
                                    <option value="Siège" <?php echo ($consultation['position_foetus'] === 'Siège') ? 'selected' : ''; ?>>Siège</option>
                                    <option value="Transverse" <?php echo ($consultation['position_foetus'] === 'Transverse') ? 'selected' : ''; ?>>Transverse</option>
                                    <option value="Oblique" <?php echo ($consultation['position_foetus'] === 'Oblique') ? 'selected' : ''; ?>>Oblique</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Fréquence cardiaque fœtale</label>
                                <input type="text" name="frequence_cardiaque_foetale" value="<?php echo htmlspecialchars($consultation['frequence_cardiaque_foetale'] ?? ''); ?>" 
                                       placeholder="Ex: 140 bpm" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Observations</label>
                            <textarea name="observations" rows="4" placeholder="Observations médicales..." 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($consultation['observations'] ?? ''); ?></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Recommandations</label>
                            <textarea name="recommandations" rows="4" placeholder="Recommandations pour la patiente..." 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($consultation['recommandations'] ?? ''); ?></textarea>
                        </div>
                        
                        <!-- Boutons d'action -->
                        <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                            <a href="voir.php?id=<?php echo $consultation_id; ?>" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                                <i class="fas fa-times mr-2"></i>Annuler
                            </a>
                            <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors">
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