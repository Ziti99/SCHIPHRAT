<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';

use Clinique\Config\Database;

$auth = new Auth();
$auth->requireAnyRole(['admin', 'medecin', 'sage_femme']);

$db = new Database();

// Récupération de l'ID de la visite
$visite_id = $_GET['id'] ?? null;

if (!$visite_id) {
    header('Location: ../suivi-postnatal.php');
    exit;
}

// Récupération des détails de la visite
$visite = $db->fetch("
    SELECT sp.*, 
           p.nom, p.prenom, p.date_naissance, p.telephone,
           a.nom_bebe, a.sexe_bebe, a.poids_bebe, a.date_accouchement,
           med.nom as medecin_nom, med.prenom as medecin_prenom, med.specialite as medecin_specialite,
           sf.nom as sage_femme_nom, sf.prenom as sage_femme_prenom, sf.specialite as sage_femme_specialite
    FROM suivi_postnatal sp
    JOIN accouchements a ON sp.accouchement_id = a.id
    JOIN patientes p ON a.patiente_id = p.id
    LEFT JOIN users med ON sp.medecin_id = med.id
    LEFT JOIN users sf ON sp.sage_femme_id = sf.id
    WHERE sp.id = ?
", [$visite_id]);

if (!$visite) {
    header('Location: ../suivi-postnatal.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Visite Post-natale - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-cyan-50 min-h-screen">
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
                            <a href="../suivi-postnatal.php" class="text-purple-600 hover:text-purple-800 mr-4">
                                <i class="fas fa-arrow-left mr-2"></i>Retour
                            </a>
                            <div class="flex-shrink-0 flex items-center">
                                <i class="fas fa-heartbeat text-2xl text-purple-600 mr-3"></i>
                                <span class="text-xl font-bold text-gray-900">Détails Visite Post-natale</span>
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
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Détails de la Visite Post-natale</h1>
                    <p class="text-gray-600">Visite du <?php echo date('d/m/Y', strtotime($visite['date_visite'])); ?></p>
                </div>

                <!-- Informations principales -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Informations de la mère -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white font-semibold mr-4">
                                <?php echo strtoupper(substr($visite['prenom'], 0, 1) . substr($visite['nom'], 0, 1)); ?>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($visite['prenom'] . ' ' . $visite['nom']); ?></h3>
                                <p class="text-gray-600">Mère</p>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Date de naissance:</span>
                                <span class="font-medium"><?php echo date('d/m/Y', strtotime($visite['date_naissance'])); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Téléphone:</span>
                                <span class="font-medium"><?php echo htmlspecialchars($visite['telephone']); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Date d'accouchement:</span>
                                <span class="font-medium"><?php echo date('d/m/Y', strtotime($visite['date_accouchement'])); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Informations du bébé -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-center mb-6">
                            <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full flex items-center justify-center text-white font-semibold mr-4">
                                <i class="fas fa-baby text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900">
                                    <?php echo $visite['nom_bebe'] ? htmlspecialchars($visite['nom_bebe']) : 'Bébé'; ?>
                                </h3>
                                <p class="text-gray-600">Nouveau-né</p>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <?php if ($visite['sexe_bebe']): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Sexe:</span>
                                <span class="font-medium"><?php echo $visite['sexe_bebe'] == 'M' ? 'Garçon' : 'Fille'; ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($visite['poids_bebe']): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Poids:</span>
                                <span class="font-medium"><?php echo $visite['poids_bebe']; ?> kg</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Détails de la visite -->
                <div class="bg-white rounded-xl shadow-lg p-6 mt-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-6">Détails de la Visite</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date de visite</label>
                            <p class="text-lg font-semibold text-gray-900"><?php echo date('d/m/Y H:i', strtotime($visite['date_visite'])); ?></p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Type de visite</label>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                <?php 
                                switch($visite['type_visite']) {
                                    case 'mere': echo 'bg-blue-100 text-blue-800'; break;
                                    case 'bebe': echo 'bg-green-100 text-green-800'; break;
                                    case 'mere_et_bebe': echo 'bg-purple-100 text-purple-800'; break;
                                    default: echo 'bg-gray-100 text-gray-800';
                                }
                                ?>">
                                <?php 
                                switch($visite['type_visite']) {
                                    case 'mere': echo 'Mère uniquement'; break;
                                    case 'bebe': echo 'Bébé uniquement'; break;
                                    case 'mere_et_bebe': echo 'Mère et bébé'; break;
                                    default: echo 'Inconnu';
                                }
                                ?>
                            </span>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Praticien</label>
                            <?php if ($visite['medecin_nom']): ?>
                                <div class="flex items-center">
                                    <i class="fas fa-user-md text-blue-600 mr-2"></i>
                                    <span class="font-semibold">Dr. <?php echo htmlspecialchars($visite['medecin_nom'] . ' ' . $visite['medecin_prenom']); ?></span>
                                </div>
                            <?php elseif ($visite['sage_femme_nom']): ?>
                                <div class="flex items-center">
                                    <i class="fas fa-user-nurse text-pink-600 mr-2"></i>
                                    <span class="font-semibold"><?php echo htmlspecialchars($visite['sage_femme_nom'] . ' ' . $visite['sage_femme_prenom']); ?></span>
                                </div>
                            <?php else: ?>
                                <span class="text-gray-500 italic">Non assigné</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($visite['prochaine_visite']): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Prochaine visite</label>
                            <p class="text-lg font-semibold text-green-600"><?php echo date('d/m/Y', strtotime($visite['prochaine_visite'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Observations -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
                    <?php if ($visite['observations_mere']): ?>
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">
                            <i class="fas fa-user text-purple-600 mr-2"></i>Observations de la mère
                        </h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-gray-800 whitespace-pre-wrap"><?php echo htmlspecialchars($visite['observations_mere']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($visite['observations_bebe']): ?>
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">
                            <i class="fas fa-baby text-green-600 mr-2"></i>Observations du bébé
                        </h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-gray-800 whitespace-pre-wrap"><?php echo htmlspecialchars($visite['observations_bebe']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Vaccinations -->
                <?php if ($visite['vaccinations']): ?>
                <div class="bg-white rounded-xl shadow-lg p-6 mt-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        <i class="fas fa-syringe text-blue-600 mr-2"></i>Vaccinations
                    </h3>
                    <div class="bg-blue-50 rounded-lg p-4">
                        <p class="text-gray-800"><?php echo htmlspecialchars($visite['vaccinations']); ?></p>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="flex justify-between items-center mt-8">
                    <a href="../suivi-postnatal.php" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
                    </a>
                    
                    <div class="flex space-x-4">
                        <a href="modifier.php?id=<?php echo $visite['id']; ?>" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-edit mr-2"></i>Modifier
                        </a>
                        <a href="ajouter.php?accouchement_id=<?php echo $visite['accouchement_id']; ?>" class="px-6 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-md hover:shadow-lg transition-all duration-300">
                            <i class="fas fa-plus mr-2"></i>Nouvelle visite
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 