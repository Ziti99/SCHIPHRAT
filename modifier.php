<?php
require_once __DIR__ . '/vendor/autoload.php';

use Clinique\Auth\Auth;
use Clinique\Config\Database;

$auth = new Auth();
$auth->requireAnyRole(['admin', 'medecin', 'sage_femme']);

$db = Database::getInstance();
$error = '';
$success = '';

// Récupération de l'ID de la visite
$visite_id = $_GET['id'] ?? null;

if (!$visite_id) {
    header('Location: ../suivi-postnatal.php');
    exit;
}

// Récupération des détails de la visite
$visite = $db->fetch("
    SELECT sp.*, 
           p.nom, p.prenom,
           a.nom_bebe, a.sexe_bebe, a.poids_bebe, a.date_accouchement
    FROM suivi_postnatal sp
    JOIN accouchements a ON sp.accouchement_id = a.id
    JOIN patientes p ON a.patiente_id = p.id
    WHERE sp.id = ?
", [$visite_id]);

if (!$visite) {
    header('Location: ../suivi-postnatal.php');
    exit;
}

// Récupération des médecins
$medecins = $db->fetchAll("
    SELECT id, nom, prenom, specialite
    FROM users 
    WHERE role = 'medecin' AND is_active = 1
    ORDER BY nom, prenom
");

// Récupération des sage-femmes
$sage_femmes = $db->fetchAll("
    SELECT id, nom, prenom, specialite
    FROM users 
    WHERE role = 'sage_femme' AND is_active = 1
    ORDER BY nom, prenom
");

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_visite = $_POST['date_visite'] ?? '';
    $type_visite = $_POST['type_visite'] ?? '';
    $medecin_id = $_POST['medecin_id'] ?? '';
    $sage_femme_id = $_POST['sage_femme_id'] ?? '';
    $observations_mere = $_POST['observations_mere'] ?? '';
    $observations_bebe = $_POST['observations_bebe'] ?? '';
    $vaccinations = $_POST['vaccinations'] ?? '';
    $prochaine_visite = !empty($_POST['prochaine_visite']) ? $_POST['prochaine_visite'] : null;
    
    if (empty($date_visite) || empty($type_visite)) {
        $error = 'Veuillez remplir tous les champs obligatoires (date de visite, type de visite).';
    } elseif (empty($medecin_id) && empty($sage_femme_id)) {
        $error = 'Veuillez sélectionner soit un médecin soit une sage-femme.';
    } else {
        try {
            $db->query("
                UPDATE suivi_postnatal SET
                    date_visite = ?, type_visite = ?, medecin_id = ?, sage_femme_id = ?,
                    observations_mere = ?, observations_bebe = ?, vaccinations = ?, prochaine_visite = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ", [
                $date_visite, $type_visite, 
                $medecin_id ?: null, $sage_femme_id ?: null,
                $observations_mere, $observations_bebe, $vaccinations, $prochaine_visite,
                $visite_id
            ]);
            
            $success = 'Visite post-natale modifiée avec succès !';
            
            // Recharger les données de la visite
            $visite = $db->fetch("
                SELECT sp.*, 
                       p.nom, p.prenom,
                       a.nom_bebe, a.sexe_bebe, a.poids_bebe, a.date_accouchement
                FROM suivi_postnatal sp
                JOIN accouchements a ON sp.accouchement_id = a.id
                JOIN patientes p ON a.patiente_id = p.id
                WHERE sp.id = ?
            ", [$visite_id]);
            
        } catch (Exception $e) {
            $error = 'Erreur lors de la modification : ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Visite Post-natale - Clinique Obstétrique</title>
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
                                <span class="text-xl font-bold text-gray-900">Modifier Visite Post-natale</span>
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
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Modifier Visite Post-natale</h1>
                    <p class="text-gray-600">
                        Visite pour <?php echo htmlspecialchars($visite['prenom'] . ' ' . $visite['nom']); ?> 
                        (<?php echo date('d/m/Y', strtotime($visite['date_accouchement'])); ?>)
                    </p>
                </div>

                <!-- Formulaire -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <form method="POST" class="space-y-6">
                        <!-- Informations de base -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="date_visite" class="block text-sm font-medium text-gray-700 mb-2">
                                    Date de visite <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" name="date_visite" required
                                       value="<?php echo date('Y-m-d\TH:i', strtotime($visite['date_visite'])); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            </div>
                            
                            <div>
                                <label for="type_visite" class="block text-sm font-medium text-gray-700 mb-2">
                                    Type de visite <span class="text-red-500">*</span>
                                </label>
                                <select name="type_visite" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <option value="">Sélectionner</option>
                                    <option value="mere" <?php echo $visite['type_visite'] == 'mere' ? 'selected' : ''; ?>>Mère uniquement</option>
                                    <option value="bebe" <?php echo $visite['type_visite'] == 'bebe' ? 'selected' : ''; ?>>Bébé uniquement</option>
                                    <option value="mere_et_bebe" <?php echo $visite['type_visite'] == 'mere_et_bebe' ? 'selected' : ''; ?>>Mère et bébé</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="medecin_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Médecin
                                </label>
                                <select name="medecin_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <option value="">Sélectionner un médecin</option>
                                    <?php foreach ($medecins as $medecin): ?>
                                        <option value="<?php echo $medecin['id']; ?>" <?php echo $visite['medecin_id'] == $medecin['id'] ? 'selected' : ''; ?>>
                                            Dr. <?php echo htmlspecialchars($medecin['nom'] . ' ' . $medecin['prenom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="sage_femme_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Sage-femme
                                </label>
                                <select name="sage_femme_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <option value="">Sélectionner une sage-femme</option>
                                    <?php foreach ($sage_femmes as $sage_femme): ?>
                                        <option value="<?php echo $sage_femme['id']; ?>" <?php echo $visite['sage_femme_id'] == $sage_femme['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($sage_femme['nom'] . ' ' . $sage_femme['prenom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="flex items-center justify-center">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                                <i class="fas fa-info-circle text-blue-600 mb-2"></i>
                                <p class="text-sm text-blue-800">
                                    <strong>Note :</strong> Sélectionnez soit un médecin soit une sage-femme
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="vaccinations" class="block text-sm font-medium text-gray-700 mb-2">
                                    Vaccinations
                                </label>
                                <input type="text" name="vaccinations" 
                                       value="<?php echo htmlspecialchars($visite['vaccinations'] ?? ''); ?>"
                                       placeholder="ex: BCG, Hépatite B, DTP..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            </div>
                            
                            <div>
                                <label for="prochaine_visite" class="block text-sm font-medium text-gray-700 mb-2">
                                    Prochaine visite
                                </label>
                                <input type="date" name="prochaine_visite" 
                                       value="<?php echo $visite['prochaine_visite'] ? date('Y-m-d', strtotime($visite['prochaine_visite'])) : ''; ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            </div>
                        </div>

                        <!-- Observations -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Observations</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="observations_mere" class="block text-sm font-medium text-gray-700 mb-2">
                                        Observations de la mère
                                    </label>
                                    <textarea name="observations_mere" rows="4"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                                              placeholder="Observations sur l'état de la mère..."><?php echo htmlspecialchars($visite['observations_mere'] ?? ''); ?></textarea>
                                </div>
                                
                                <div>
                                    <label for="observations_bebe" class="block text-sm font-medium text-gray-700 mb-2">
                                        Observations du bébé
                                    </label>
                                    <textarea name="observations_bebe" rows="4"
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                                              placeholder="Observations sur l'état du bébé..."><?php echo htmlspecialchars($visite['observations_bebe'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Boutons -->
                        <div class="flex justify-end space-x-4 pt-6">
                            <a href="voir.php?id=<?php echo $visite_id; ?>" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                                Annuler
                            </a>
                            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-md hover:shadow-lg transition-all duration-300">
                                <i class="fas fa-save mr-2"></i>Enregistrer les modifications
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