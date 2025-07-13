<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$db = new Database();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patiente_id = $_POST['patiente_id'] ?? '';
    $date_accouchement = $_POST['date_accouchement'] ?? '';
    $heure_accouchement = $_POST['heure_accouchement'] ?? '';
    $mode_accouchement = $_POST['mode_accouchement'] ?? '';
    $duree_travail = $_POST['duree_travail'] ?? '';
    $complications = $_POST['complications'] ?? '';
    $nom_bebe = $_POST['nom_bebe'] ?? '';
    $sexe_bebe = $_POST['sexe_bebe'] ?? '';
    $poids_bebe = $_POST['poids_bebe'] ?? '';
    $taille_bebe = $_POST['taille_bebe'] ?? '';
    $apgar_score = $_POST['apgar_score'] ?? '';
    $observations = $_POST['observations'] ?? '';
    $sage_femme_id = $_POST['sage_femme_id'] ?? '';
    
    // Validation
    $errors = [];
    if (empty($patiente_id)) $errors[] = "La patiente est requise";
    if (empty($date_accouchement)) $errors[] = "La date d'accouchement est requise";
    if (empty($mode_accouchement)) $errors[] = "Le mode d'accouchement est requis";
    
    if (empty($errors)) {
        $date_time_accouchement = $date_accouchement . ' ' . $heure_accouchement . ':00';
        
        try {
            $db->query("
                INSERT INTO accouchements (patiente_id, date_accouchement, mode_accouchement, duree_travail, 
                                         complications, medecin_id, sage_femme_id, nom_bebe, sexe_bebe, 
                                         poids_bebe, taille_bebe, statut_bebe, apgar_score, observations)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'vivant', ?, ?)
            ", [$patiente_id, $date_time_accouchement, $mode_accouchement, $duree_travail, $complications, 
                 $_SESSION['user_id'], $sage_femme_id, $nom_bebe, $sexe_bebe, $poids_bebe, $taille_bebe, 
                 $apgar_score, $observations]);
            
            header('Location: ../accouchements.php?success=1');
            exit;
        } catch (Exception $e) {
            $errors[] = "Erreur lors de l'enregistrement: " . $e->getMessage();
        }
    }
}

// Récupération des patientes pour le select
$patientes = $db->fetchAll("
    SELECT id, nom, prenom, date_naissance, nationalite
    FROM patientes 
    ORDER BY nom, prenom
");

// Récupération des sages-femmes
$sages_femmes = $db->fetchAll("
    SELECT id, nom, prenom
    FROM users 
    WHERE role = 'sage_femme' AND is_active = 1
    ORDER BY nom, prenom
");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Accouchement - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-gray-50 via-green-50 to-emerald-50 min-h-screen">
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
                                <i class="fas fa-plus text-2xl text-green-600 mr-3"></i>
                                <span class="text-xl font-bold text-gray-900">Ajouter un Accouchement</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-gray-700">
                                <i class="fas fa-user mr-2"></i>
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
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
                    <h2 class="text-2xl font-bold text-gray-900">Enregistrer un Accouchement</h2>
                    <p class="text-gray-600">Remplissez les informations de l'accouchement</p>
                </div>

                <!-- Messages d'erreur -->
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                        <div class="flex">
                            <i class="fas fa-exclamation-triangle mr-2 mt-1"></i>
                            <div>
                                <strong>Erreurs :</strong>
                                <ul class="mt-1 list-disc list-inside">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Formulaire -->
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <form method="POST" class="space-y-6">
                        <!-- Patiente -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-user mr-2 text-green-600"></i>Patiente *
                            </label>
                            <select name="patiente_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                <option value="">Sélectionner une patiente</option>
                                <?php foreach ($patientes as $patiente): ?>
                                    <option value="<?php echo $patiente['id']; ?>" <?php echo (isset($_POST['patiente_id']) && $_POST['patiente_id'] == $patiente['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($patiente['prenom'] . ' ' . $patiente['nom'] . ' (' . $patiente['nationalite'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Date et heure -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-calendar mr-2 text-green-600"></i>Date d'accouchement *
                                </label>
                                <input type="date" name="date_accouchement" value="<?php echo htmlspecialchars($_POST['date_accouchement'] ?? ''); ?>" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-clock mr-2 text-green-600"></i>Heure d'accouchement
                                </label>
                                <input type="time" name="heure_accouchement" value="<?php echo htmlspecialchars($_POST['heure_accouchement'] ?? ''); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                        </div>

                        <!-- Mode d'accouchement et durée -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-baby mr-2 text-green-600"></i>Mode d'accouchement *
                                </label>
                                <select name="mode_accouchement" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                    <option value="">Sélectionner</option>
                                    <option value="voie_basse" <?php echo (isset($_POST['mode_accouchement']) && $_POST['mode_accouchement'] === 'voie_basse') ? 'selected' : ''; ?>>Voie basse</option>
                                    <option value="cesarienne" <?php echo (isset($_POST['mode_accouchement']) && $_POST['mode_accouchement'] === 'cesarienne') ? 'selected' : ''; ?>>Césarienne</option>
                                    <option value="forceps" <?php echo (isset($_POST['mode_accouchement']) && $_POST['mode_accouchement'] === 'forceps') ? 'selected' : ''; ?>>Forceps</option>
                                    <option value="ventouse" <?php echo (isset($_POST['mode_accouchement']) && $_POST['mode_accouchement'] === 'ventouse') ? 'selected' : ''; ?>>Ventouse</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-clock mr-2 text-green-600"></i>Durée du travail (minutes)
                                </label>
                                <input type="number" name="duree_travail" value="<?php echo htmlspecialchars($_POST['duree_travail'] ?? ''); ?>" 
                                       placeholder="Ex: 120" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                        </div>

                        <!-- Complications -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-exclamation-triangle mr-2 text-green-600"></i>Complications
                            </label>
                            <textarea name="complications" rows="3" placeholder="Complications éventuelles..."
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"><?php echo htmlspecialchars($_POST['complications'] ?? ''); ?></textarea>
                        </div>

                        <!-- Informations du bébé -->
                        <div class="border-t border-gray-200 pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-baby mr-2 text-green-600"></i>Informations du bébé
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Nom du bébé</label>
                                    <input type="text" name="nom_bebe" value="<?php echo htmlspecialchars($_POST['nom_bebe'] ?? ''); ?>" 
                                           placeholder="Nom du bébé" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Sexe</label>
                                    <select name="sexe_bebe" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <option value="">Sélectionner</option>
                                        <option value="M" <?php echo (isset($_POST['sexe_bebe']) && $_POST['sexe_bebe'] === 'M') ? 'selected' : ''; ?>>Masculin</option>
                                        <option value="F" <?php echo (isset($_POST['sexe_bebe']) && $_POST['sexe_bebe'] === 'F') ? 'selected' : ''; ?>>Féminin</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Poids (kg)</label>
                                    <input type="number" name="poids_bebe" value="<?php echo htmlspecialchars($_POST['poids_bebe'] ?? ''); ?>" 
                                           step="0.1" min="0" placeholder="Ex: 3.2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Taille (cm)</label>
                                    <input type="number" name="taille_bebe" value="<?php echo htmlspecialchars($_POST['taille_bebe'] ?? ''); ?>" 
                                           step="0.1" min="0" placeholder="Ex: 50" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Score Apgar</label>
                                    <input type="number" name="apgar_score" value="<?php echo htmlspecialchars($_POST['apgar_score'] ?? ''); ?>" 
                                           min="0" max="10" placeholder="Ex: 9" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Sage-femme</label>
                                    <select name="sage_femme_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <option value="">Sélectionner</option>
                                        <?php foreach ($sages_femmes as $sf): ?>
                                            <option value="<?php echo $sf['id']; ?>" <?php echo (isset($_POST['sage_femme_id']) && $_POST['sage_femme_id'] == $sf['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($sf['prenom'] . ' ' . $sf['nom']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Observations -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-sticky-note mr-2 text-green-600"></i>Observations
                            </label>
                            <textarea name="observations" rows="4" placeholder="Observations complémentaires..."
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"><?php echo htmlspecialchars($_POST['observations'] ?? ''); ?></textarea>
                        </div>

                        <!-- Boutons -->
                        <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                            <a href="../accouchements.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                                <i class="fas fa-times mr-2"></i>Annuler
                            </a>
                            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-green-500 to-emerald-500 text-white rounded-md hover:shadow-lg transition-all duration-300">
                                <i class="fas fa-save mr-2"></i>Enregistrer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 