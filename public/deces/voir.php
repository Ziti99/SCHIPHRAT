<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Clinique\Auth\Auth;
use Clinique\Config\Database;

$auth = new Auth();
$auth->requireAnyRole(['admin', 'medecin']);

$db = Database::getInstance();

// Récupération de l'ID du décès
$deces_id = $_GET['id'] ?? null;

if (!$deces_id) {
    header('Location: ../deces.php');
    exit;
}

// Récupération des détails du décès
$deces = $db->fetch("
    SELECT d.*, 
           p.nom, p.prenom, p.date_naissance, p.telephone,
           med.nom as medecin_nom, med.prenom as medecin_prenom, med.specialite as medecin_specialite
    FROM deces d
    JOIN patientes p ON d.patiente_id = p.id
    LEFT JOIN users med ON d.medecin_id = med.id
    WHERE d.id = ?
", [$deces_id]);

if (!$deces) {
    header('Location: ../deces.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Décès - Clinique Obstétrique</title>
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
                                <span class="text-xl font-bold text-gray-900">Détails du Décès</span>
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
                <!-- En-tête -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Détails du Décès</h1>
                    <p class="text-gray-600">Décès du <?php echo date('d/m/Y H:i', strtotime($deces['date_deces'])); ?></p>
                </div>

                <!-- Informations de la patiente -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                    <div class="flex items-center mb-6">
                        <div class="w-12 h-12 bg-gradient-to-r from-red-500 to-pink-500 rounded-full flex items-center justify-center text-white font-semibold mr-4">
                            <?php echo strtoupper(substr($deces['prenom'], 0, 1) . substr($deces['nom'], 0, 1)); ?>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($deces['prenom'] . ' ' . $deces['nom']); ?></h3>
                            <p class="text-gray-600">Patiente décédée</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Date de naissance:</span>
                                <span class="font-medium"><?php echo date('d/m/Y', strtotime($deces['date_naissance'])); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Téléphone:</span>
                                <span class="font-medium"><?php echo htmlspecialchars($deces['telephone']); ?></span>
                            </div>
                            <?php if ($deces['age_deces']): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Âge au décès:</span>
                                <span class="font-medium"><?php echo $deces['age_deces']; ?> ans</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Date du décès:</span>
                                <span class="font-medium"><?php echo date('d/m/Y H:i', strtotime($deces['date_deces'])); ?></span>
                            </div>
                            <?php if ($deces['lieu_deces']): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Lieu du décès:</span>
                                <span class="font-medium"><?php echo htmlspecialchars($deces['lieu_deces']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Cause du décès -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>Cause du décès
                    </h3>
                    <div class="bg-red-50 rounded-lg p-4">
                        <p class="text-gray-800 whitespace-pre-wrap"><?php echo htmlspecialchars($deces['cause_deces']); ?></p>
                    </div>
                </div>

                <!-- Médecin déclarant -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        <i class="fas fa-user-md text-blue-600 mr-2"></i>Médecin déclarant
                    </h3>
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full flex items-center justify-center text-white font-semibold mr-4">
                            <?php echo strtoupper(substr($deces['medecin_prenom'], 0, 1) . substr($deces['medecin_nom'], 0, 1)); ?>
                        </div>
                        <div>
                            <p class="text-lg font-semibold text-gray-900">
                                Dr. <?php echo htmlspecialchars($deces['medecin_prenom'] . ' ' . $deces['medecin_nom']); ?>
                            </p>
                            <?php if ($deces['medecin_specialite']): ?>
                                <p class="text-gray-600"><?php echo htmlspecialchars($deces['medecin_specialite']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Observations -->
                <?php if ($deces['observations']): ?>
                <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        <i class="fas fa-clipboard-list text-gray-600 mr-2"></i>Observations complémentaires
                    </h3>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-gray-800 whitespace-pre-wrap"><?php echo htmlspecialchars($deces['observations']); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Informations techniques -->
                <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        <i class="fas fa-info-circle text-gray-600 mr-2"></i>Informations techniques
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date d'enregistrement</label>
                            <p class="text-gray-900"><?php echo date('d/m/Y H:i', strtotime($deces['created_at'])); ?></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Dernière modification</label>
                            <p class="text-gray-900"><?php echo date('d/m/Y H:i', strtotime($deces['updated_at'])); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-between items-center">
                    <a href="../deces.php" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
                    </a>
                    
                    <div class="flex space-x-4">
                        <a href="modifier.php?id=<?php echo $deces['id']; ?>" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-edit mr-2"></i>Modifier
                        </a>
                        <a href="supprimer.php?id=<?php echo $deces['id']; ?>" class="px-6 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce décès ?')">
                            <i class="fas fa-trash mr-2"></i>Supprimer
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 