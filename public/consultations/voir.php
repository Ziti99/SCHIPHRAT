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

// Récupération des détails de la consultation
$consultation = $db->fetch("
    SELECT cp.*, 
           p.nom, p.prenom, p.telephone, p.date_naissance, p.adresse, p.groupe_sanguin,
           u.nom as medecin_nom, u.prenom as medecin_prenom, u.specialite as medecin_specialite
    FROM consultations_prenatales cp
    JOIN patientes p ON cp.patiente_id = p.id
    JOIN users u ON cp.medecin_id = u.id
    WHERE cp.id = ?
", [$consultation_id]);

if (!$consultation) {
    header('Location: /consultations.php');
    exit;
}

// Récupération des examens associés
$examens = $db->fetchAll("
    SELECT * FROM examens 
    WHERE consultation_id = ? 
    ORDER BY date_examen DESC
", [$consultation_id]);
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
                                <i class="fas fa-stethoscope text-2xl text-blue-600 mr-3"></i>
                                <span class="text-xl font-bold text-gray-900">Détails Consultation</span>
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
                <!-- En-tête -->
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Consultation #<?php echo $consultation_id; ?></h1>
                        <p class="text-gray-600">Détails de la consultation prénatale</p>
                    </div>
                    <div class="flex space-x-3">
                        <a href="modifier.php?id=<?php echo $consultation_id; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-edit mr-2"></i>Modifier
                        </a>
                        <a href="/examens/ajouter.php?consultation_id=<?php echo $consultation_id; ?>" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors">
                            <i class="fas fa-microscope mr-2"></i>Ajouter examen
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Informations patiente -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-user mr-2 text-blue-600"></i>Informations patiente
                            </h3>
                            <div class="space-y-3">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Nom complet</label>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($consultation['prenom'] . ' ' . $consultation['nom']); ?></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Téléphone</label>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($consultation['telephone']); ?></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Date de naissance</label>
                                    <p class="text-gray-900"><?php echo date('d/m/Y', strtotime($consultation['date_naissance'])); ?></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Adresse</label>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($consultation['adresse']); ?></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Groupe sanguin</label>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($consultation['groupe_sanguin'] ?? 'Non renseigné'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Détails consultation -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-stethoscope mr-2 text-purple-600"></i>Détails de la consultation
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Date et heure</label>
                                    <p class="text-gray-900"><?php echo date('d/m/Y H:i', strtotime($consultation['date_consultation'])); ?></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Médecin</label>
                                    <p class="text-gray-900">Dr. <?php echo htmlspecialchars($consultation['medecin_prenom'] . ' ' . $consultation['medecin_nom']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($consultation['medecin_specialite'] ?? ''); ?></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">DPA</label>
                                    <p class="text-gray-900"><?php echo $consultation['dpa'] ? date('d/m/Y', strtotime($consultation['dpa'])) : 'Non renseigné'; ?></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Tension artérielle</label>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($consultation['tension_arterielle'] ?? 'Non mesurée'); ?></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Poids</label>
                                    <p class="text-gray-900"><?php echo $consultation['poids'] ? $consultation['poids'] . ' kg' : 'Non mesuré'; ?></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Hauteur utérine</label>
                                    <p class="text-gray-900"><?php echo $consultation['hauteur_uterine'] ? $consultation['hauteur_uterine'] . ' cm' : 'Non mesurée'; ?></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Position fœtus</label>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($consultation['position_foetus'] ?? 'Non déterminée'); ?></p>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Fréquence cardiaque fœtale</label>
                                    <p class="text-gray-900"><?php echo $consultation['frequence_cardiaque_foetale'] ? $consultation['frequence_cardiaque_foetale'] . ' bpm' : 'Non mesurée'; ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Observations et recommandations -->
                        <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-clipboard-list mr-2 text-green-600"></i>Observations et recommandations
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Observations</label>
                                    <div class="mt-1 p-3 bg-gray-50 rounded-md">
                                        <p class="text-gray-900"><?php echo nl2br(htmlspecialchars($consultation['observations'] ?? 'Aucune observation')); ?></p>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Recommandations</label>
                                    <div class="mt-1 p-3 bg-gray-50 rounded-md">
                                        <p class="text-gray-900"><?php echo nl2br(htmlspecialchars($consultation['recommandations'] ?? 'Aucune recommandation')); ?></p>
                                    </div>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-500">Acte posé</label>
                                    <div class="mt-1 p-3 bg-gray-50 rounded-md">
                                        <p class="text-gray-900"><?php echo htmlspecialchars($consultation['acte_pose'] ?? 'Aucun acte spécifié'); ?></p>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Examens associés -->
                        <div class="bg-white rounded-xl shadow-lg p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">
                                    <i class="fas fa-microscope mr-2 text-orange-600"></i>Examens associés
                                </h3>
                                <a href="/examens/ajouter.php?consultation_id=<?php echo $consultation_id; ?>" class="bg-orange-600 text-white px-3 py-1 rounded-md hover:bg-orange-700 transition-colors text-sm">
                                    <i class="fas fa-plus mr-1"></i>Ajouter
                                </a>
                            </div>
                            
                            <?php if (empty($examens)): ?>
                                <div class="text-center py-8 text-gray-500">
                                    <i class="fas fa-microscope text-4xl mb-4 text-gray-300"></i>
                                    <p class="text-lg font-medium">Aucun examen associé</p>
                                    <p class="text-sm">Aucun examen n'a été enregistré pour cette consultation.</p>
                                </div>
                            <?php else: ?>
                                <div class="space-y-3">
                                    <?php foreach ($examens as $examen): ?>
                                        <div class="border border-gray-200 rounded-lg p-4">
                                            <div class="flex justify-between items-start">
                                                <div class="flex-1">
                                                    <h4 class="font-medium text-gray-900">
                                                        <?php 
                                                        switch($examen['type_examen']) {
                                                            case 'echographie': echo 'Échographie'; break;
                                                            case 'bilan_sanguin': echo 'Bilan sanguin'; break;
                                                            case 'urine': echo 'Analyse d\'urine'; break;
                                                            default: echo ucfirst($examen['type_examen']);
                                                        }
                                                        ?>
                                                    </h4>
                                                    <p class="text-sm text-gray-500">Date: <?php echo date('d/m/Y', strtotime($examen['date_examen'])); ?></p>
                                                    <?php if ($examen['resultats']): ?>
                                                        <p class="text-sm text-gray-700 mt-2"><?php echo htmlspecialchars(substr($examen['resultats'], 0, 100)) . (strlen($examen['resultats']) > 100 ? '...' : ''); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex space-x-2">
                                                    <a href="/examens/voir.php?id=<?php echo $examen['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Voir l'examen">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="/examens/modifier.php?id=<?php echo $examen['id']; ?>" class="text-green-600 hover:text-green-900" title="Modifier">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 