<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Clinique\Auth\Auth;
use Clinique\Config\Database;

$auth = new Auth();
$auth->requireAnyRole(['admin', 'medecin', 'sage_femme']);

$db = Database::getInstance();

// Récupération de l'ID de l'accouchement
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: ../accouchements.php');
    exit;
}

// Récupération de l'accouchement avec toutes les informations
$accouchement = $db->fetch("
    SELECT a.*, 
           p.nom, p.prenom, p.telephone, p.adresse,
           medecin.nom as medecin_nom, medecin.prenom as medecin_prenom, medecin.specialite as medecin_specialite,
           sage_femme.nom as sage_femme_nom, sage_femme.prenom as sage_femme_prenom, sage_femme.specialite as sage_femme_specialite
    FROM accouchements a
    JOIN patientes p ON a.patiente_id = p.id
    JOIN users medecin ON a.medecin_id = medecin.id
    LEFT JOIN users sage_femme ON a.sage_femme_id = sage_femme.id
    WHERE a.id = ?
", [$id]);

if (!$accouchement) {
    header('Location: ../accouchements.php');
    exit;
}

// Fonction pour générer l'ID d'accouchement
function generateAccouchementId($db, $date_accouchement, $accouchement_db_id) {
    $mois = date('m', strtotime($date_accouchement));
    $annee = date('Y', strtotime($date_accouchement));
    
    // Compter les accouchements du mois jusqu'à cet accouchement
    $accouchements_mois = $db->fetch("
        SELECT COUNT(*) as count 
        FROM accouchements 
        WHERE MONTH(date_accouchement) = ? AND YEAR(date_accouchement) = ? AND id <= ?
    ", [$mois, $annee, $accouchement_db_id])['count'];
    
    // Compter les accouchements de l'année jusqu'à cet accouchement
    $accouchements_annee = $db->fetch("
        SELECT COUNT(*) as count 
        FROM accouchements 
        WHERE YEAR(date_accouchement) = ? AND id <= ?
    ", [$annee, $accouchement_db_id])['count'];
    
    // Générer l'ID : 5eme accouchement du 3eme mois et 12eme de l'année = 0503122025
    $numero_mois = str_pad($accouchements_mois, 2, '0', STR_PAD_LEFT);
    $numero_annee = str_pad($accouchements_annee, 2, '0', STR_PAD_LEFT);
    
    return $numero_mois . $mois . $numero_annee . $annee;
}

$generated_id = generateAccouchementId($db, $accouchement['date_accouchement'], $accouchement['id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails Accouchement - Clinique Obstétrique</title>
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
                            <a href="../accouchements.php" class="text-purple-600 hover:text-purple-800 mr-4">
                                <i class="fas fa-arrow-left mr-2"></i>Retour
                            </a>
                            <div class="flex-shrink-0 flex items-center">
                                <i class="fas fa-eye text-2xl text-purple-600 mr-3"></i>
                                <span class="text-xl font-bold text-gray-900">Détails Accouchement</span>
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

            <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- En-tête avec ID -->
                <div class="mb-8">
                    <div class="flex justify-between items-start">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">Détails Accouchement</h1>
                            <p class="text-gray-600">Informations complètes de l'accouchement</p>
                        </div>
                        <div class="text-right">
                            <div class="bg-gradient-to-r from-green-500 to-emerald-500 text-white px-6 py-3 rounded-lg">
                                <div class="text-sm font-medium">ID Généré</div>
                                <div class="text-2xl font-bold"><?php echo htmlspecialchars($generated_id); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informations principales -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Informations de la mère -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full flex items-center justify-center text-white font-semibold text-lg">
                                <?php echo strtoupper(substr($accouchement['prenom'], 0, 1) . substr($accouchement['nom'], 0, 1)); ?>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">Informations de la mère</h3>
                                <p class="text-sm text-gray-500">Patiente</p>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm font-medium text-gray-500">Nom complet :</span>
                                <p class="text-gray-900"><?php echo htmlspecialchars($accouchement['prenom'] . ' ' . $accouchement['nom']); ?></p>
                            </div>
                            <?php if ($accouchement['telephone']): ?>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Téléphone :</span>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($accouchement['telephone']); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ($accouchement['adresse']): ?>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Adresse :</span>
                                    <p class="text-gray-900"><?php echo htmlspecialchars($accouchement['adresse']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Informations de l'accouchement -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-500 rounded-full flex items-center justify-center text-white">
                                <i class="fas fa-baby text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">Accouchement</h3>
                                <p class="text-sm text-gray-500">Détails</p>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm font-medium text-gray-500">Date et heure :</span>
                                <p class="text-gray-900"><?php echo date('d/m/Y à H:i', strtotime($accouchement['date_accouchement'])); ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Mode :</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    <?php 
                                    switch($accouchement['mode_accouchement']) {
                                        case 'voie_basse': echo 'bg-green-100 text-green-800'; break;
                                        case 'cesarienne': echo 'bg-red-100 text-red-800'; break;
                                        case 'forceps': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'ventouse': echo 'bg-blue-100 text-blue-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $accouchement['mode_accouchement'])); ?>
                                </span>
                            </div>
                            <?php if ($accouchement['duree_travail']): ?>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Durée du travail :</span>
                                    <p class="text-gray-900"><?php echo $accouchement['duree_travail']; ?> minutes</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Informations du bébé -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full flex items-center justify-center text-white">
                                <i class="fas fa-baby text-xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">Bébé</h3>
                                <p class="text-sm text-gray-500">Informations</p>
                            </div>
                        </div>
                        
                        <div class="space-y-3">
                            <?php if ($accouchement['nom_bebe']): ?>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Nom :</span>
                                    <p class="text-gray-900 font-semibold"><?php echo htmlspecialchars($accouchement['nom_bebe']); ?></p>
                                </div>
                            <?php endif; ?>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Sexe :</span>
                                <p class="text-gray-900"><?php echo $accouchement['sexe_bebe'] == 'M' ? 'Garçon' : 'Fille'; ?></p>
                            </div>
                            <?php if ($accouchement['poids_bebe']): ?>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Poids :</span>
                                    <p class="text-gray-900"><?php echo $accouchement['poids_bebe']; ?> kg</p>
                                </div>
                            <?php endif; ?>
                            <?php if ($accouchement['taille_bebe']): ?>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Taille :</span>
                                    <p class="text-gray-900"><?php echo $accouchement['taille_bebe']; ?> cm</p>
                                </div>
                            <?php endif; ?>
                            <?php if ($accouchement['apgar_score']): ?>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Score d'Apgar :</span>
                                    <p class="text-gray-900"><?php echo $accouchement['apgar_score']; ?>/10</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Personnel médical -->
                <div class="mt-8 bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Personnel médical</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center mb-3">
                                <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full flex items-center justify-center text-white">
                                    <i class="fas fa-user-md"></i>
                                </div>
                                <div class="ml-3">
                                    <h4 class="font-semibold text-gray-900">Médecin</h4>
                                    <p class="text-sm text-gray-500">Responsable</p>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <p class="text-gray-900 font-medium">Dr. <?php echo htmlspecialchars($accouchement['medecin_nom'] . ' ' . $accouchement['medecin_prenom']); ?></p>
                                <?php if ($accouchement['medecin_specialite']): ?>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($accouchement['medecin_specialite']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center mb-3">
                                <div class="w-10 h-10 bg-gradient-to-r from-pink-500 to-rose-500 rounded-full flex items-center justify-center text-white">
                                    <i class="fas fa-user-nurse"></i>
                                </div>
                                <div class="ml-3">
                                    <h4 class="font-semibold text-gray-900">Sage-femme</h4>
                                    <p class="text-sm text-gray-500">Assistante</p>
                                </div>
                            </div>
                            <?php if ($accouchement['sage_femme_nom']): ?>
                                <div class="space-y-2">
                                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($accouchement['sage_femme_nom'] . ' ' . $accouchement['sage_femme_prenom']); ?></p>
                                    <?php if ($accouchement['sage_femme_specialite']): ?>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($accouchement['sage_femme_specialite']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-500 italic">Aucune sage-femme assignée</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Complications et observations -->
                <?php if ($accouchement['complications'] || $accouchement['observations']): ?>
                    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php if ($accouchement['complications']): ?>
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <div class="flex items-center mb-4">
                                    <div class="w-10 h-10 bg-gradient-to-r from-red-500 to-pink-500 rounded-full flex items-center justify-center text-white">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-lg font-semibold text-gray-900">Complications</h3>
                                        <p class="text-sm text-gray-500">Problèmes rencontrés</p>
                                    </div>
                                </div>
                                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($accouchement['complications'])); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($accouchement['observations']): ?>
                            <div class="bg-white rounded-xl shadow-lg p-6">
                                <div class="flex items-center mb-4">
                                    <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full flex items-center justify-center text-white">
                                        <i class="fas fa-clipboard-list"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-lg font-semibold text-gray-900">Observations</h3>
                                        <p class="text-sm text-gray-500">Notes médicales</p>
                                    </div>
                                </div>
                                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($accouchement['observations'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Actions -->
                <div class="mt-8 flex justify-center space-x-4">
                    <a href="modifier.php?id=<?php echo $id; ?>" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-edit mr-2"></i>Modifier
                    </a>
                    <a href="../suivi-postnatal/ajouter.php?accouchement_id=<?php echo $id; ?>" class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors">
                        <i class="fas fa-heartbeat mr-2"></i>Suivi post-natal
                    </a>
                    <a href="../accouchements.php" class="bg-gray-500 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition-colors">
                        <i class="fas fa-list mr-2"></i>Retour à la liste
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 