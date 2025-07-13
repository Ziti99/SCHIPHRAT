<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

$db = new Database();

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: /accouchements.php');
    exit;
}

// Récupérer les détails de l'accouchement
$accouchement = $db->fetchOne("
    SELECT 
        a.*,
        p.nom, p.prenom, p.telephone, p.date_naissance,
        medecin.nom as medecin_nom, medecin.prenom as medecin_prenom,
        sage_femme.nom as sage_femme_nom, sage_femme.prenom as sage_femme_prenom
    FROM accouchements a
    JOIN patientes p ON a.patiente_id = p.id
    JOIN users medecin ON a.medecin_id = medecin.id
    LEFT JOIN users sage_femme ON a.sage_femme_id = sage_femme.id
    WHERE a.id = ?
", [$id]);

if (!$accouchement) {
    header('Location: /accouchements.php');
    exit;
}
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
<body class="bg-gradient-to-br from-green-50 via-cyan-50 to-blue-50 min-h-screen">
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
                            <a href="/accouchements.php" class="text-purple-600 hover:text-purple-800 mr-4">
                                <i class="fas fa-arrow-left mr-2"></i>Retour
                            </a>
                            <div class="flex-shrink-0 flex items-center">
                                <i class="fas fa-baby text-2xl text-green-600 mr-3"></i>
                                <span class="text-xl font-bold text-gray-900">Détails Accouchement</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-gray-700">
                                <i class="fas fa-user mr-2"></i>
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </span>
                            <a href="/logout.php" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                            </a>
                        </div>
                    </div>
                </div>
            </nav>

            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <!-- En-tête -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Détails de l'Accouchement</h1>
                    <p class="text-gray-600">Informations complètes sur l'accouchement</p>
                </div>

                <!-- Détails -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations de la Patiente</h3>
                            <div class="space-y-3">
                                <div>
                                    <span class="font-medium text-gray-700">Nom complet:</span>
                                    <span class="ml-2"><?php echo htmlspecialchars($accouchement['prenom'] . ' ' . $accouchement['nom']); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Téléphone:</span>
                                    <span class="ml-2"><?php echo htmlspecialchars($accouchement['telephone']); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Date de naissance:</span>
                                    <span class="ml-2"><?php echo date('d/m/Y', strtotime($accouchement['date_naissance'])); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations de l'Accouchement</h3>
                            <div class="space-y-3">
                                <div>
                                    <span class="font-medium text-gray-700">Date d'accouchement:</span>
                                    <span class="ml-2"><?php echo date('d/m/Y H:i', strtotime($accouchement['date_accouchement'])); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Mode d'accouchement:</span>
                                    <span class="ml-2"><?php echo htmlspecialchars($accouchement['mode_accouchement']); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Durée du travail:</span>
                                    <span class="ml-2"><?php echo htmlspecialchars($accouchement['duree_travail']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations du Bébé</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <span class="font-medium text-gray-700">Nom du bébé:</span>
                                <span class="ml-2"><?php echo htmlspecialchars($accouchement['nom_bebe']); ?></span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Sexe:</span>
                                <span class="ml-2"><?php echo $accouchement['sexe_bebe'] === 'M' ? 'Masculin' : 'Féminin'; ?></span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Poids:</span>
                                <span class="ml-2"><?php echo $accouchement['poids_bebe']; ?> kg</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Taille:</span>
                                <span class="ml-2"><?php echo $accouchement['taille_bebe']; ?> cm</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Score APGAR:</span>
                                <span class="ml-2"><?php echo htmlspecialchars($accouchement['apgar_score']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Équipe Médicale</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <span class="font-medium text-gray-700">Médecin:</span>
                                <span class="ml-2">Dr. <?php echo htmlspecialchars($accouchement['medecin_prenom'] . ' ' . $accouchement['medecin_nom']); ?></span>
                            </div>
                            <?php if ($accouchement['sage_femme_nom']): ?>
                            <div>
                                <span class="font-medium text-gray-700">Sage-femme:</span>
                                <span class="ml-2"><?php echo htmlspecialchars($accouchement['sage_femme_prenom'] . ' ' . $accouchement['sage_femme_nom']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($accouchement['complications'] || $accouchement['observations']): ?>
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Observations</h3>
                        <div class="space-y-3">
                            <?php if ($accouchement['complications']): ?>
                            <div>
                                <span class="font-medium text-gray-700">Complications:</span>
                                <p class="mt-1 text-gray-600"><?php echo nl2br(htmlspecialchars($accouchement['complications'])); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if ($accouchement['observations']): ?>
                            <div>
                                <span class="font-medium text-gray-700">Observations:</span>
                                <p class="mt-1 text-gray-600"><?php echo nl2br(htmlspecialchars($accouchement['observations'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-8 flex justify-end space-x-4">
                        <a href="/accouchements.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i>Retour
                        </a>
                        <a href="/accouchements/modifier.php?id=<?php echo $id; ?>" class="px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors">
                            <i class="fas fa-edit mr-2"></i>Modifier
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 