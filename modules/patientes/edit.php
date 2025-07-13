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

$message = '';
$error = '';

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
} catch (Exception $e) {
    header('Location: dashboard.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $sql = "UPDATE patientes SET nom = ?, prenom = ?, date_naissance = ?, adresse = ?, telephone = ?, groupe_sanguin = ?, nombre_grossesses = ?, nombre_fausses_couches = ?, antecedents_medicaux = ?, allergies = ? WHERE id = ?";
        
        $db->query($sql, [
            $_POST['nom'],
            $_POST['prenom'],
            $_POST['date_naissance'],
            $_POST['adresse'],
            $_POST['telephone'],
            $_POST['groupe_sanguin'],
            $_POST['nombre_grossesses'] ?? 0,
            $_POST['nombre_fausses_couches'] ?? 0,
            $_POST['antecedents_medicaux'] ?? '',
            $_POST['allergies'] ?? '',
            $id
        ]);
        
        $message = "Patiente modifiée avec succès !";
        
        // Récupérer les données mises à jour
        $patiente = $db->fetchOne("SELECT * FROM patientes WHERE id = ?", [$id]);
        
    } catch (Exception $e) {
        $error = "Erreur lors de la modification : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Patiente - Clinique Obstétrique</title>
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
                        <i class="fas fa-edit text-2xl text-purple-600 mr-3"></i>
                        <span class="text-xl font-bold text-gray-900">Modifier Patiente</span>
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

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire -->
        <div class="bg-white rounded-xl shadow-lg p-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">
                Modifier : <?php echo htmlspecialchars($patiente['nom'] . ' ' . $patiente['prenom']); ?>
            </h2>
            
            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Nom -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
                        <input type="text" name="nom" required value="<?php echo htmlspecialchars($patiente['nom']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <!-- Prénom -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Prénom *</label>
                        <input type="text" name="prenom" required value="<?php echo htmlspecialchars($patiente['prenom']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <!-- Date de naissance -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date de naissance *</label>
                        <input type="date" name="date_naissance" required value="<?php echo $patiente['date_naissance']; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <!-- Téléphone -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Téléphone *</label>
                        <input type="tel" name="telephone" required value="<?php echo htmlspecialchars($patiente['telephone']); ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <!-- Groupe sanguin -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Groupe sanguin</label>
                        <select name="groupe_sanguin" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="">Sélectionner</option>
                            <?php
                            $groupes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                            foreach ($groupes as $groupe) {
                                $selected = ($patiente['groupe_sanguin'] === $groupe) ? 'selected' : '';
                                echo "<option value=\"$groupe\" $selected>$groupe</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- Nombre de grossesses -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de grossesses</label>
                        <input type="number" name="nombre_grossesses" min="0" value="<?php echo $patiente['nombre_grossesses']; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <!-- Nombre de fausses couches -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nombre de fausses couches</label>
                        <input type="number" name="nombre_fausses_couches" min="0" value="<?php echo $patiente['nombre_fausses_couches']; ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                </div>
                
                <!-- Adresse -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Adresse *</label>
                    <textarea name="adresse" rows="3" required 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"><?php echo htmlspecialchars($patiente['adresse']); ?></textarea>
                </div>
                
                <!-- Antécédents médicaux -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Antécédents médicaux</label>
                    <textarea name="antecedents_medicaux" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                              placeholder="Antécédents médicaux, chirurgicaux, etc."><?php echo htmlspecialchars($patiente['antecedents_medicaux']); ?></textarea>
                </div>
                
                <!-- Allergies -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Allergies</label>
                    <textarea name="allergies" rows="2" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                              placeholder="Allergies médicamenteuses, alimentaires, etc."><?php echo htmlspecialchars($patiente['allergies']); ?></textarea>
                </div>
                
                <!-- Boutons -->
                <div class="flex justify-end space-x-4 pt-6">
                    <a href="view.php?id=<?php echo $patiente['id']; ?>" 
                       class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                        Annuler
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-gradient-to-r from-blue-500 to-purple-500 text-white rounded-lg hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-save mr-2"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 