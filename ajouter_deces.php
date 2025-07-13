<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$db = new Database();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patiente_id = $_POST['patiente_id'] ?? '';
    $date_deces = $_POST['date_deces'] ?? '';
    $heure_deces = $_POST['heure_deces'] ?? '';
    $cause_deces = $_POST['cause_deces'] ?? '';
    $age_deces = $_POST['age_deces'] ?? null;
    $lieu_deces = $_POST['lieu_deces'] ?? '';
    $observations = $_POST['observations'] ?? '';
    
    // Validation
    $errors = [];
    if (empty($patiente_id)) $errors[] = "La patiente est requise";
    if (empty($date_deces)) $errors[] = "La date de décès est requise";
    if (empty($cause_deces)) $errors[] = "La cause du décès est requise";
    
    if (empty($errors)) {
        $date_time_deces = $date_deces . ' ' . $heure_deces . ':00';
        
        try {
            $db->query("
                INSERT INTO deces (patiente_id, date_deces, cause_deces, age_deces, lieu_deces, medecin_id, observations)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ", [$patiente_id, $date_time_deces, $cause_deces, $age_deces, $lieu_deces, $_SESSION['user_id'], $observations]);
            
            header('Location: deces.php?success=1');
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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Décès - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-gray-50 via-red-50 to-pink-50 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Contenu principal -->
        <div class="flex-1">
            <!-- Navigation -->
            <nav class="bg-white shadow-lg border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <a href="deces.php" class="text-purple-600 hover:text-purple-800 mr-4">
                                <i class="fas fa-arrow-left mr-2"></i>Retour
                            </a>
                            <div class="flex-shrink-0 flex items-center">
                                <i class="fas fa-plus text-2xl text-red-600 mr-3"></i>
                                <span class="text-xl font-bold text-gray-900">Ajouter un Décès</span>
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
                    <h2 class="text-2xl font-bold text-gray-900">Enregistrer un Décès</h2>
                    <p class="text-gray-600">Remplissez les informations du décès</p>
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
                                <i class="fas fa-user mr-2 text-red-600"></i>Patiente *
                            </label>
                            <select name="patiente_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
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
                                    <i class="fas fa-calendar mr-2 text-red-600"></i>Date de décès *
                                </label>
                                <input type="date" name="date_deces" value="<?php echo htmlspecialchars($_POST['date_deces'] ?? ''); ?>" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-clock mr-2 text-red-600"></i>Heure de décès
                                </label>
                                <input type="time" name="heure_deces" value="<?php echo htmlspecialchars($_POST['heure_deces'] ?? ''); ?>"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>

                        <!-- Cause et lieu -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-heart mr-2 text-red-600"></i>Cause du décès *
                                </label>
                                <textarea name="cause_deces" rows="3" required placeholder="Décrivez la cause du décès..."
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"><?php echo htmlspecialchars($_POST['cause_deces'] ?? ''); ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-map-marker-alt mr-2 text-red-600"></i>Lieu du décès
                                </label>
                                <input type="text" name="lieu_deces" value="<?php echo htmlspecialchars($_POST['lieu_deces'] ?? ''); ?>" 
                                       placeholder="Domicile, Hôpital, Clinique..."
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                        </div>

                        <!-- Âge et observations -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-baby mr-2 text-red-600"></i>Âge au décès (heures)
                                </label>
                                <input type="number" name="age_deces" value="<?php echo htmlspecialchars($_POST['age_deces'] ?? ''); ?>" 
                                       placeholder="Âge en heures"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-sticky-note mr-2 text-red-600"></i>Observations
                                </label>
                                <textarea name="observations" rows="3" placeholder="Observations complémentaires..."
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"><?php echo htmlspecialchars($_POST['observations'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <!-- Boutons -->
                        <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                            <a href="deces.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                                <i class="fas fa-times mr-2"></i>Annuler
                            </a>
                            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-md hover:shadow-lg transition-all duration-300">
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