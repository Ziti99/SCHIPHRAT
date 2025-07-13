<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$user = $auth->getCurrentUser();
$db = new Database();

// Récupérer l'ID de la patiente
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: dashboard.php');
    exit;
}

// Récupérer les informations de la patiente
try {
    $patiente = $db->fetchOne("SELECT * FROM patientes WHERE id = ?", [$id]);
    if (!$patiente) {
        header('Location: dashboard.php');
        exit;
    }
    
    // Récupérer les grossesses
    $grossesses = $db->fetchAll("SELECT * FROM grossesses WHERE patiente_id = ? ORDER BY date_debut_grossesse DESC", [$id]);
    
} catch (Exception $e) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détail Patiente - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-purple-50 via-pink-50 to-cyan-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-purple-600 hover:text-purple-800 mr-4">
                        <i class="fas fa-arrow-left mr-2"></i>Retour
                    </a>
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-user text-2xl text-purple-600 mr-3"></i>
                        <span class="text-xl font-bold text-gray-900">Détail Patiente</span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">
                        <i class="fas fa-user mr-2"></i>
                        <?php echo htmlspecialchars($user['nom'] . ' ' . $user['prenom']); ?>
                    </span>
                    <a href="../../logout.php" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- En-tête -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <?php echo htmlspecialchars($patiente['nom'] . ' ' . $patiente['prenom']); ?>
            </h1>
        </div>

        <!-- Actions -->
        <div class="mb-6 flex space-x-4">
            <a href="edit.php?id=<?php echo $patiente['id']; ?>" 
               class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors duration-200">
                <i class="fas fa-edit mr-2"></i>Modifier
            </a>
            <a href="delete.php?id=<?php echo $patiente['id']; ?>" 
               class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors duration-200"
               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette patiente ?')">
                <i class="fas fa-trash mr-2"></i>Supprimer
            </a>
        </div>

        <!-- Informations de la patiente -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Informations personnelles -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">
                    <i class="fas fa-user mr-2 text-purple-600"></i>Informations personnelles
                </h2>
                
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">Nom complet :</span>
                        <span class="text-gray-900"><?php echo htmlspecialchars($patiente['nom'] . ' ' . $patiente['prenom']); ?></span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">Date de naissance :</span>
                        <span class="text-gray-900"><?php echo date('d/m/Y', strtotime($patiente['date_naissance'])); ?></span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">Âge :</span>
                        <span class="text-gray-900"><?php echo htmlspecialchars($patiente['age']); ?> ans</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">Téléphone :</span>
                        <span class="text-gray-900"><?php echo htmlspecialchars($patiente['telephone']); ?></span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">Groupe sanguin :</span>
                        <span class="text-gray-900"><?php echo htmlspecialchars($patiente['groupe_sanguin'] ?? 'Non renseigné'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Informations médicales -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">
                    <i class="fas fa-heartbeat mr-2 text-pink-600"></i>Informations médicales
                </h2>
                
                <div class="space-y-4">
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">Nombre de grossesses :</span>
                        <span class="text-gray-900"><?php echo htmlspecialchars($patiente['nombre_grossesses']); ?></span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">Fausses couches :</span>
                        <span class="text-gray-900"><?php echo htmlspecialchars($patiente['nombre_fausses_couches']); ?></span>
                    </div>
                    
                    <div>
                        <span class="font-medium text-gray-700">Antécédents médicaux :</span>
                        <p class="text-gray-900 mt-1"><?php echo htmlspecialchars($patiente['antecedents_medicaux'] ?: 'Aucun antécédent renseigné'); ?></p>
                    </div>
                    
                    <div>
                        <span class="font-medium text-gray-700">Allergies :</span>
                        <p class="text-gray-900 mt-1"><?php echo htmlspecialchars($patiente['allergies'] ?: 'Aucune allergie renseignée'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Adresse -->
        <div class="bg-white rounded-xl shadow-lg p-6 mt-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">
                <i class="fas fa-map-marker-alt mr-2 text-green-600"></i>Adresse
            </h2>
            <p class="text-gray-900"><?php echo nl2br(htmlspecialchars($patiente['adresse'])); ?></p>
        </div>

        <!-- Grossesses -->
        <div class="bg-white rounded-xl shadow-lg p-6 mt-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">
                    <i class="fas fa-baby mr-2 text-cyan-600"></i>Grossesses
                </h2>
                <a href="../grossesses/create.php?patiente_id=<?php echo $patiente['id']; ?>" 
                   class="bg-cyan-500 text-white px-4 py-2 rounded-lg hover:bg-cyan-600 transition-colors duration-200">
                    <i class="fas fa-plus mr-2"></i>Nouvelle grossesse
                </a>
            </div>
            
            <?php if (empty($grossesses)): ?>
                <p class="text-gray-500 text-center py-8">Aucune grossesse enregistrée</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Début</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Terme prévu</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($grossesses as $grossesse): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('d/m/Y', strtotime($grossesse['date_debut_grossesse'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('d/m/Y', strtotime($grossesse['date_terme_prevue'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php 
                                        $statuts = [
                                            'en_cours' => '<span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded-full">En cours</span>',
                                            'terminee' => '<span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">Terminée</span>',
                                            'interrompue' => '<span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded-full">Interrompue</span>'
                                        ];
                                        echo $statuts[$grossesse['statut']] ?? $grossesse['statut'];
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="../grossesses/view.php?id=<?php echo $grossesse['id']; ?>" 
                                           class="text-cyan-600 hover:text-cyan-900 mr-3">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="../grossesses/edit.php?id=<?php echo $grossesse['id']; ?>" 
                                           class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 