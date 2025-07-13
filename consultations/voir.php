<?php
session_start();

// Debug: Afficher les informations de session
error_log("Session debug - user_id: " . ($_SESSION['user_id'] ?? 'non défini'));
error_log("Session debug - username: " . ($_SESSION['username'] ?? 'non défini'));
error_log("Session debug - user_role: " . ($_SESSION['user_role'] ?? 'non défini'));

if (!isset($_SESSION['user_id'])) {
    error_log("Redirection vers login.php - user_id non défini");
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$db = new Database();

// Récupérer l'ID de la consultation
$consultation_id = $_GET['id'] ?? 0;

error_log("Consultation ID: " . $consultation_id);

if (!$consultation_id) {
    error_log("Redirection vers consultations.php - consultation_id vide");
    header('Location: /consultations.php');
    exit;
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
    error_log("Redirection vers consultations.php - consultation non trouvée pour ID: " . $consultation_id);
    header('Location: /consultations.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Consultation - Clinique Obstétrique</title>
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
                            <a href="../consultations.php" class="text-purple-600 hover:text-purple-800 mr-4">
                                <i class="fas fa-arrow-left mr-2"></i>Retour
                            </a>
                            <div class="flex-shrink-0 flex items-center">
                                <i class="fas fa-stethoscope text-2xl text-blue-600 mr-3"></i>
                                <span class="text-xl font-bold text-gray-900">Détails Consultation</span>
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
                    <h2 class="text-2xl font-bold text-gray-900">Détails de la Consultation</h2>
                    <p class="text-gray-600">Consultation #<?php echo $consultation_id; ?></p>
                </div>
                
                <!-- Message de succès -->
                <?php if (isset($_GET['success'])): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                        <div class="flex">
                            <i class="fas fa-check-circle mr-2 mt-1"></i>
                            <div>
                                <strong>Succès !</strong> La consultation a été modifiée avec succès.
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Informations de la patiente -->
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
                            <label class="block text-sm font-medium text-gray-700">Date de naissance</label>
                            <p class="text-gray-900"><?php echo date('d/m/Y', strtotime($consultation['date_naissance'])); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nationalité</label>
                            <p class="text-gray-900"><?php echo htmlspecialchars($consultation['nationalite']); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Téléphone</label>
                            <p class="text-gray-900"><?php echo htmlspecialchars($consultation['telephone'] ?: 'Non défini'); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Adresse</label>
                            <p class="text-gray-900"><?php echo htmlspecialchars($consultation['adresse'] ?: 'Non défini'); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Groupe sanguin</label>
                            <p class="text-gray-900"><?php echo htmlspecialchars($consultation['groupe_sanguin'] ?: 'Non défini'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Détails de la consultation -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-stethoscope mr-2 text-blue-600"></i>Détails de la Consultation
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tension artérielle</label>
                            <p class="text-gray-900"><?php echo htmlspecialchars($consultation['tension_arterielle'] ?: 'Non défini'); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Poids (kg)</label>
                            <p class="text-gray-900"><?php echo htmlspecialchars($consultation['poids'] ?: 'Non défini'); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Hauteur utérine (cm)</label>
                            <p class="text-gray-900"><?php echo htmlspecialchars($consultation['hauteur_uterine'] ?: 'Non défini'); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Position du fœtus</label>
                            <p class="text-gray-900"><?php echo htmlspecialchars($consultation['position_foetus'] ?: 'Non défini'); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fréquence cardiaque fœtale</label>
                            <p class="text-gray-900"><?php echo htmlspecialchars($consultation['frequence_cardiaque_foetale'] ?: 'Non défini'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Observations et recommandations -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-notes-medical mr-2 text-blue-600"></i>Observations et Recommandations
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Observations</label>
                            <p class="text-gray-900 mt-1"><?php echo nl2br(htmlspecialchars($consultation['observations'] ?: 'Aucune observation')); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Recommandations</label>
                            <p class="text-gray-900 mt-1"><?php echo nl2br(htmlspecialchars($consultation['recommandations'] ?: 'Aucune recommandation')); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="flex justify-between items-center">
                    <a href="../consultations.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
                    </a>
                    <div class="flex space-x-4">
                        <a href="modifier.php?id=<?php echo $consultation_id; ?>" class="px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors">
                            <i class="fas fa-edit mr-2"></i>Modifier
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 