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
        p.nom, p.prenom, p.telephone, p.date_naissance
    FROM accouchements a
    JOIN patientes p ON a.patiente_id = p.id
    WHERE a.id = ?
", [$id]);

if (!$accouchement) {
    header('Location: /accouchements.php');
    exit;
}

// Récupérer les patientes
$patientes = $db->fetchAll("SELECT id, nom, prenom FROM patientes ORDER BY nom, prenom");

// Récupérer les médecins
$medecins = $db->fetchAll("
    SELECT id, nom, prenom, specialite
    FROM users 
    WHERE role = 'medecin' AND is_active = 1
    ORDER BY nom, prenom
");

// Récupérer les sage-femmes
$sage_femmes = $db->fetchAll("
    SELECT id, nom, prenom, specialite
    FROM users 
    WHERE role = 'sage_femme' AND is_active = 1
    ORDER BY nom, prenom
");

$error = '';
$success = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patiente_id = $_POST['patiente_id'] ?? '';
    $date_accouchement = $_POST['date_accouchement'] ?? '';
    $mode_accouchement = $_POST['mode_accouchement'] ?? '';
    $duree_travail = $_POST['duree_travail'] ?? '';
    $complications = $_POST['complications'] ?? '';
    $nom_bebe = $_POST['nom_bebe'] ?? '';
    $sexe_bebe = $_POST['sexe_bebe'] ?? '';
    $poids_bebe = $_POST['poids_bebe'] ?? '';
    $taille_bebe = $_POST['taille_bebe'] ?? '';
    $apgar_score = $_POST['apgar_score'] ?? '';
    $medecin_id = $_POST['medecin_id'] ?? '';
    $sage_femme_id = $_POST['sage_femme_id'] ?? '';
    $observations = $_POST['observations'] ?? '';
    
    if (empty($patiente_id) || empty($date_accouchement) || empty($mode_accouchement)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } else {
        try {
            $db->query("
                UPDATE accouchements SET 
                    patiente_id = ?, date_accouchement = ?, mode_accouchement = ?, 
                    duree_travail = ?, complications = ?, nom_bebe = ?, sexe_bebe = ?,
                    poids_bebe = ?, taille_bebe = ?, apgar_score = ?, medecin_id = ?,
                    sage_femme_id = ?, observations = ?
                WHERE id = ?
            ", [
                $patiente_id, $date_accouchement, $mode_accouchement, 
                $duree_travail, $complications, $nom_bebe, $sexe_bebe,
                $poids_bebe, $taille_bebe, $apgar_score, $medecin_id,
                $sage_femme_id ?: null, $observations, $id
            ]);
            
            $success = 'Accouchement modifié avec succès !';
            
            // Recharger les données
            $accouchement = $db->fetchOne("
                SELECT 
                    a.*,
                    p.nom, p.prenom, p.telephone, p.date_naissance
                FROM accouchements a
                JOIN patientes p ON a.patiente_id = p.id
                WHERE a.id = ?
            ", [$id]);
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
    <title>Modifier Accouchement - Clinique Obstétrique</title>
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
                                <span class="text-xl font-bold text-gray-900">Modifier Accouchement</span>
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
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Modifier l'Accouchement</h1>
                    <p class="text-gray-600">Modifier les informations de l'accouchement</p>
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
                                <select name="patiente_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="">Sélectionner une patiente</option>
                                    <?php foreach ($patientes as $patiente): ?>
                                        <option value="<?php echo $patiente['id']; ?>" <?php echo $accouchement['patiente_id'] == $patiente['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($patiente['prenom'] . ' ' . $patiente['nom']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="date_accouchement" class="block text-sm font-medium text-gray-700 mb-2">
                                    Date d'accouchement <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" name="date_accouchement" required
                                       value="<?php echo date('Y-m-d\TH:i', strtotime($accouchement['date_accouchement'])); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="mode_accouchement" class="block text-sm font-medium text-gray-700 mb-2">
                                    Mode d'accouchement <span class="text-red-500">*</span>
                                </label>
                                <select name="mode_accouchement" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="">Sélectionner</option>
                                    <option value="voie_basse" <?php echo $accouchement['mode_accouchement'] === 'voie_basse' ? 'selected' : ''; ?>>Voie basse</option>
                                    <option value="cesarienne" <?php echo $accouchement['mode_accouchement'] === 'cesarienne' ? 'selected' : ''; ?>>Césarienne</option>
                                    <option value="forceps" <?php echo $accouchement['mode_accouchement'] === 'forceps' ? 'selected' : ''; ?>>Forceps</option>
                                    <option value="ventouse" <?php echo $accouchement['mode_accouchement'] === 'ventouse' ? 'selected' : ''; ?>>Ventouse</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="duree_travail" class="block text-sm font-medium text-gray-700 mb-2">
                                    Durée du travail
                                </label>
                                <input type="text" name="duree_travail" 
                                       value="<?php echo htmlspecialchars($accouchement['duree_travail']); ?>"
                                       placeholder="ex: 8h30"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                        </div>

                        <!-- Informations du bébé -->
                        <div class="border-t pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Informations du Bébé</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label for="nom_bebe" class="block text-sm font-medium text-gray-700 mb-2">
                                        Nom du bébé
                                    </label>
                                    <input type="text" name="nom_bebe" 
                                           value="<?php echo htmlspecialchars($accouchement['nom_bebe']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                                
                                <div>
                                    <label for="sexe_bebe" class="block text-sm font-medium text-gray-700 mb-2">
                                        Sexe
                                    </label>
                                    <select name="sexe_bebe" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <option value="">Sélectionner</option>
                                        <option value="M" <?php echo $accouchement['sexe_bebe'] === 'M' ? 'selected' : ''; ?>>Masculin</option>
                                        <option value="F" <?php echo $accouchement['sexe_bebe'] === 'F' ? 'selected' : ''; ?>>Féminin</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="poids_bebe" class="block text-sm font-medium text-gray-700 mb-2">
                                        Poids (kg)
                                    </label>
                                    <input type="number" step="0.1" name="poids_bebe" 
                                           value="<?php echo htmlspecialchars($accouchement['poids_bebe']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                                
                                <div>
                                    <label for="taille_bebe" class="block text-sm font-medium text-gray-700 mb-2">
                                        Taille (cm)
                                    </label>
                                    <input type="number" step="0.1" name="taille_bebe" 
                                           value="<?php echo htmlspecialchars($accouchement['taille_bebe']); ?>"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                                
                                <div>
                                    <label for="apgar_score" class="block text-sm font-medium text-gray-700 mb-2">
                                        Score APGAR
                                    </label>
                                    <input type="text" name="apgar_score" 
                                           value="<?php echo htmlspecialchars($accouchement['apgar_score']); ?>"
                                           placeholder="ex: 9/10"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                            </div>
                        </div>

                        <!-- Équipe médicale -->
                        <div class="border-t pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Équipe Médicale</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="medecin_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        Médecin <span class="text-red-500">*</span>
                                    </label>
                                    <select name="medecin_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <option value="">Sélectionner un médecin</option>
                                        <?php foreach ($medecins as $medecin): ?>
                                            <option value="<?php echo $medecin['id']; ?>" <?php echo $accouchement['medecin_id'] == $medecin['id'] ? 'selected' : ''; ?>>
                                                Dr. <?php echo htmlspecialchars($medecin['nom'] . ' ' . $medecin['prenom']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div>
                                    <label for="sage_femme_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        Sage-femme
                                    </label>
                                    <select name="sage_femme_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <option value="">Sélectionner une sage-femme</option>
                                        <?php foreach ($sage_femmes as $sage_femme): ?>
                                            <option value="<?php echo $sage_femme['id']; ?>" <?php echo $accouchement['sage_femme_id'] == $sage_femme['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($sage_femme['nom'] . ' ' . $sage_femme['prenom']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Observations -->
                        <div class="border-t pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Observations</h3>
                            <div class="space-y-4">
                                <div>
                                    <label for="complications" class="block text-sm font-medium text-gray-700 mb-2">
                                        Complications
                                    </label>
                                    <textarea name="complications" rows="3" 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                              placeholder="Décrire les complications éventuelles..."><?php echo htmlspecialchars($accouchement['complications']); ?></textarea>
                                </div>
                                
                                <div>
                                    <label for="observations" class="block text-sm font-medium text-gray-700 mb-2">
                                        Observations générales
                                    </label>
                                    <textarea name="observations" rows="4" 
                                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
                                              placeholder="Observations sur l'accouchement..."><?php echo htmlspecialchars($accouchement['observations']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Boutons -->
                        <div class="flex justify-end space-x-4 pt-6">
                            <a href="/accouchements.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                                <i class="fas fa-times mr-2"></i>Annuler
                            </a>
                            <button type="submit" class="px-6 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 transition-colors">
                                <i class="fas fa-save mr-2"></i>Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function closeMessage(id) {
            document.getElementById(id).style.display = 'none';
        }
    </script>
</body>
</html> 