<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Clinique\Auth\Auth;
use Clinique\Config\Database;

$auth = new Auth();
$auth->requireAnyRole(['admin', 'medecin', 'sage_femme']);

$db = Database::getInstance();
$error = '';
$success = '';

// Récupération de toutes les patientes
$patientes = $db->fetchAll("
    SELECT 
        p.id as patiente_id,
        p.nom, 
        p.prenom, 
        p.telephone
    FROM patientes p
    ORDER BY p.nom, p.prenom
");

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

// Fonction pour générer l'ID d'accouchement
function generateAccouchementId($db, $date_accouchement) {
    $mois = date('m', strtotime($date_accouchement));
    $annee = date('Y', strtotime($date_accouchement));
    
    // Compter les accouchements du mois
    $accouchements_mois = $db->fetch("
        SELECT COUNT(*) as count 
        FROM accouchements 
        WHERE MONTH(date_accouchement) = ? AND YEAR(date_accouchement) = ?
    ", [$mois, $annee])['count'];
    
    // Compter les accouchements de l'année
    $accouchements_annee = $db->fetch("
        SELECT COUNT(*) as count 
        FROM accouchements 
        WHERE YEAR(date_accouchement) = ?
    ", [$annee])['count'];
    
    // Générer l'ID : 5eme accouchement du 3eme mois et 12eme de l'année = 0503122025
    $numero_mois = str_pad($accouchements_mois + 1, 2, '0', STR_PAD_LEFT);
    $numero_annee = str_pad($accouchements_annee + 1, 2, '0', STR_PAD_LEFT);
    
    return $numero_mois . $mois . $numero_annee . $annee;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patiente_id = $_POST['patiente_id'] ?? '';
    $date_accouchement = $_POST['date_accouchement'] ?? '';
    $mode_accouchement = $_POST['mode_accouchement'] ?? '';
    $medecin_id = $_POST['medecin_id'] ?? '';
    $sage_femme_id = $_POST['sage_femme_id'] ?? '';
    $nom_bebe = $_POST['nom_bebe'] ?? '';
    $sexe_bebe = $_POST['sexe_bebe'] ?? '';
    $poids_bebe = !empty($_POST['poids_bebe']) ? $_POST['poids_bebe'] : null;
    $taille_bebe = !empty($_POST['taille_bebe']) ? $_POST['taille_bebe'] : null;
    $apgar_score = !empty($_POST['apgar_score']) ? $_POST['apgar_score'] : null;
    $complications = $_POST['complications'] ?? '';
    $observations = $_POST['observations'] ?? '';
    
    if (empty($patiente_id) || empty($date_accouchement) || empty($mode_accouchement) || empty($medecin_id) || empty($sexe_bebe)) {
        $error = 'Veuillez remplir tous les champs obligatoires (patiente, date, mode d\'accouchement, médecin et sexe du bébé).';
    } else {
        try {
            // Générer l'ID d'accouchement
            $accouchement_id = generateAccouchementId($db, $date_accouchement);
            
            $db->query("
                INSERT INTO accouchements (
                    patiente_id, date_accouchement, mode_accouchement, medecin_id, sage_femme_id,
                    nom_bebe, sexe_bebe, poids_bebe, taille_bebe, apgar_score,
                    complications, observations
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ", [
                $patiente_id, $date_accouchement, $mode_accouchement, $medecin_id, $sage_femme_id,
                $nom_bebe, $sexe_bebe, $poids_bebe, $taille_bebe, $apgar_score,
                $complications, $observations
            ]);
            
            $success = 'Accouchement enregistré avec succès ! ID: ' . $accouchement_id;
        } catch (Exception $e) {
            $error = 'Erreur lors de l\'enregistrement : ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvel Accouchement - Clinique Obstétrique</title>
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
                                <i class="fas fa-baby text-2xl text-purple-600 mr-3"></i>
                                <span class="text-xl font-bold text-gray-900">Nouvel Accouchement</span>
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
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
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
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Nouvel Accouchement</h1>
                    <p class="text-gray-600">Enregistrer un nouvel accouchement</p>
                    <?php if (isset($accouchement_id)): ?>
                        <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-id-card text-blue-600 mr-3"></i>
                                <div>
                                    <p class="text-sm font-medium text-blue-800">ID d'accouchement généré</p>
                                    <p class="text-lg font-bold text-blue-900"><?php echo htmlspecialchars($accouchement_id); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Formulaire -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <form method="POST" class="space-y-6">
                        <!-- Informations de base -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="patiente_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Patiente <span class="text-red-500">*</span>
                                </label>
                                <select name="patiente_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <option value="">Sélectionner une patiente</option>
                                    <?php foreach ($patientes as $patiente): ?>
                                        <option value="<?php echo $patiente['patiente_id']; ?>">
                                            <?php echo htmlspecialchars($patiente['prenom'] . ' ' . $patiente['nom']); ?>
                                            <?php if ($patiente['telephone']): ?>
                                                - <?php echo htmlspecialchars($patiente['telephone']); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="date_accouchement" class="block text-sm font-medium text-gray-700 mb-2">
                                    Date et heure d'accouchement <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" name="date_accouchement" required
                                       value="<?php echo date('Y-m-d\TH:i'); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="mode_accouchement" class="block text-sm font-medium text-gray-700 mb-2">
                                    Mode d'accouchement <span class="text-red-500">*</span>
                                </label>
                                <select name="mode_accouchement" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <option value="">Sélectionner</option>
                                    <option value="voie_basse">Voie basse</option>
                                    <option value="cesarienne">Césarienne</option>
                                    <option value="forceps">Forceps</option>
                                    <option value="ventouse">Ventouse</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="medecin_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Médecin <span class="text-red-500">*</span>
                                </label>
                                <select name="medecin_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <option value="">Sélectionner un médecin</option>
                                    <?php foreach ($medecins as $medecin): ?>
                                        <option value="<?php echo $medecin['id']; ?>">
                                            Dr. <?php echo htmlspecialchars($medecin['nom'] . ' ' . $medecin['prenom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="sage_femme_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Sage-femme
                                </label>
                                <select name="sage_femme_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                    <option value="">Sélectionner une sage-femme</option>
                                    <?php foreach ($sage_femmes as $sage_femme): ?>
                                        <option value="<?php echo $sage_femme['id']; ?>">
                                            <?php echo htmlspecialchars($sage_femme['nom'] . ' ' . $sage_femme['prenom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="duree_travail" class="block text-sm font-medium text-gray-700 mb-2">
                                    Durée du travail (minutes) (optionnel)
                                </label>
                                <input type="number" name="duree_travail" min="0"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                            </div>
                        </div>

                        <!-- Informations du bébé -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations du bébé</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="nom_bebe" class="block text-sm font-medium text-gray-700 mb-2">
                                        Nom du bébé (optionnel)
                                    </label>
                                    <input type="text" name="nom_bebe"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                                           placeholder="Peut être ajouté plus tard">
                                    <p class="text-xs text-gray-500 mt-1">Le nom peut être enregistré ultérieurement</p>
                                </div>
                                
                                <div>
                                    <label for="sexe_bebe" class="block text-sm font-medium text-gray-700 mb-2">
                                        Sexe <span class="text-red-500">*</span>
                                    </label>
                                    <select name="sexe_bebe" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                        <option value="">Sélectionner</option>
                                        <option value="M">Masculin</option>
                                        <option value="F">Féminin</option>
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1">Information médicale obligatoire</p>
                                </div>
                                
                                <div>
                                    <label for="apgar_score" class="block text-sm font-medium text-gray-700 mb-2">
                                        Score d'Apgar
                                    </label>
                                    <input type="number" name="apgar_score" min="0" max="10"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                                <div>
                                    <label for="poids_bebe" class="block text-sm font-medium text-gray-700 mb-2">
                                        Poids du bébé (kg)
                                    </label>
                                    <input type="number" step="0.001" name="poids_bebe" min="0"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                </div>
                                
                                <div>
                                    <label for="taille_bebe" class="block text-sm font-medium text-gray-700 mb-2">
                                        Taille du bébé (cm)
                                    </label>
                                    <input type="number" name="taille_bebe" min="0"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500">
                                </div>
                            </div>
                        </div>

                        <!-- Complications et observations -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="complications" class="block text-sm font-medium text-gray-700 mb-2">
                                    Complications (optionnel)
                                </label>
                                <textarea name="complications" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                                          placeholder="Décrivez les complications éventuelles..."></textarea>
                            </div>
                            
                            <div>
                                <label for="observations" class="block text-sm font-medium text-gray-700 mb-2">
                                    Observations (optionnel)
                                </label>
                                <textarea name="observations" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500"
                                          placeholder="Observations générales..."></textarea>
                            </div>
                        </div>

                        <!-- Boutons -->
                        <div class="flex justify-end space-x-4 pt-6">
                            <a href="../accouchements.php" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                                Annuler
                            </a>
                            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white rounded-md hover:shadow-lg transition-all duration-300">
                                <i class="fas fa-save mr-2"></i>Enregistrer
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