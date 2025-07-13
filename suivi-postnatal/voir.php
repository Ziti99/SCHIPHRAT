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
    header('Location: /suivi-postnatal.php');
    exit;
}

// Récupérer les détails de la visite
$visite = $db->fetchOne("
    SELECT 
        sp.*,
        p.nom, p.prenom, p.telephone,
        a.nom_bebe, a.sexe_bebe, a.poids_bebe, a.taille_bebe,
        medecin.nom as medecin_nom, medecin.prenom as medecin_prenom,
        sage_femme.nom as sage_femme_nom, sage_femme.prenom as sage_femme_prenom
    FROM suivi_postnatal sp
    JOIN accouchements a ON sp.accouchement_id = a.id
    JOIN patientes p ON a.patiente_id = p.id
    LEFT JOIN users medecin ON sp.medecin_id = medecin.id
    LEFT JOIN users sage_femme ON sp.sage_femme_id = sage_femme.id
    WHERE sp.id = ?
", [$id]);

if (!$visite) {
    header('Location: /suivi-postnatal.php');
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
                            <a href="/suivi-postnatal.php" class="text-purple-600 hover:text-purple-800 mr-4">
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
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Détails de la Visite Post-natale</h1>
                    <p class="text-gray-600">Informations complètes sur la visite</p>
                </div>

                <!-- Détails -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations de la Patiente</h3>
                            <div class="space-y-3">
                                <div>
                                    <span class="font-medium text-gray-700">Nom complet:</span>
                                    <span class="ml-2"><?php echo htmlspecialchars($visite['prenom'] . ' ' . $visite['nom']); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Téléphone:</span>
                                    <span class="ml-2"><?php echo htmlspecialchars($visite['telephone']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations de la Visite</h3>
                            <div class="space-y-3">
                                <div>
                                    <span class="font-medium text-gray-700">Date de visite:</span>
                                    <span class="ml-2"><?php echo date('d/m/Y H:i', strtotime($visite['date_visite'])); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Type de visite:</span>
                                    <span class="ml-2"><?php echo htmlspecialchars($visite['type_visite']); ?></span>
                                </div>
                                <?php if ($visite['prochaine_visite']): ?>
                                <div>
                                    <span class="font-medium text-gray-700">Prochaine visite:</span>
                                    <span class="ml-2"><?php echo date('d/m/Y', strtotime($visite['prochaine_visite'])); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations du Bébé</h3>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <span class="font-medium text-gray-700">Nom du bébé:</span>
                                <span class="ml-2"><?php echo htmlspecialchars($visite['nom_bebe']); ?></span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Sexe:</span>
                                <span class="ml-2"><?php echo $visite['sexe_bebe'] === 'M' ? 'Masculin' : 'Féminin'; ?></span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Poids:</span>
                                <span class="ml-2"><?php echo $visite['poids_bebe']; ?> kg</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Taille:</span>
                                <span class="ml-2"><?php echo $visite['taille_bebe']; ?> cm</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Équipe Médicale</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php if ($visite['medecin_nom']): ?>
                            <div>
                                <span class="font-medium text-gray-700">Médecin:</span>
                                <span class="ml-2">Dr. <?php echo htmlspecialchars($visite['medecin_prenom'] . ' ' . $visite['medecin_nom']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($visite['sage_femme_nom']): ?>
                            <div>
                                <span class="font-medium text-gray-700">Sage-femme:</span>
                                <span class="ml-2"><?php echo htmlspecialchars($visite['sage_femme_prenom'] . ' ' . $visite['sage_femme_nom']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($visite['observations_mere'] || $visite['observations_bebe'] || $visite['vaccinations']): ?>
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Observations</h3>
                        <div class="space-y-4">
                            <?php if ($visite['observations_mere']): ?>
                            <div>
                                <span class="font-medium text-gray-700">Observations mère:</span>
                                <p class="mt-1 text-gray-600"><?php echo nl2br(htmlspecialchars($visite['observations_mere'])); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if ($visite['observations_bebe']): ?>
                            <div>
                                <span class="font-medium text-gray-700">Observations bébé:</span>
                                <p class="mt-1 text-gray-600"><?php echo nl2br(htmlspecialchars($visite['observations_bebe'])); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if ($visite['vaccinations']): ?>
                            <div>
                                <span class="font-medium text-gray-700">Vaccinations:</span>
                                <p class="mt-1 text-gray-600"><?php echo nl2br(htmlspecialchars($visite['vaccinations'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-8 flex justify-end space-x-4">
                        <a href="/suivi-postnatal.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i>Retour
                        </a>
                        <a href="/suivi-postnatal/modifier.php?id=<?php echo $id; ?>" class="px-6 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600 transition-colors">
                            <i class="fas fa-edit mr-2"></i>Modifier
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 