<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$message = '';
$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patiente_id = $_POST['patiente_id'] ?? '';
    $date_deces = $_POST['date_deces'] ?? '';
    $heure_deces = $_POST['heure_deces'] ?? '';
    $cause_deces = $_POST['cause_deces'] ?? '';
    $age_deces = $_POST['age_deces'] ?? '';
    $lieu_deces = $_POST['lieu_deces'] ?? '';
    $medecin_id = $_SESSION['user_id'];
    $observations = $_POST['observations'] ?? '';
    
    if (empty($patiente_id) || empty($date_deces) || empty($cause_deces)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } else {
        try {
            $date_time_deces = $date_deces . ' ' . ($heure_deces ?: '00:00:00');
            $db->query("INSERT INTO deces (patiente_id, date_deces, cause_deces, age_deces, lieu_deces, medecin_id, observations) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$patiente_id, $date_time_deces, $cause_deces, $age_deces, $lieu_deces, $medecin_id, $observations]);
            
            header('Location: /deces.php?success=1');
            exit;
        } catch (Exception $e) {
            $error = 'Erreur lors de l\'ajout : ' . $e->getMessage();
        }
    }
}

// Récupération des patientes
$patientes = $db->fetchAll("SELECT id, nom, prenom, date_naissance FROM patientes ORDER BY nom, prenom");
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
<body class="bg-gray-50">
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        <main class="flex-1 p-8">
            <!-- En-tête -->
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Ajouter un Décès</h2>
                <a href="/deces.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Retour
                </a>
            </div>

            <!-- Messages -->
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Formulaire -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Patiente *</label>
                        <select name="patiente_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                            <option value="">Sélectionner une patiente</option>
                            <?php foreach ($patientes as $pat): ?>
                                <option value="<?php echo $pat['id']; ?>" <?php echo (isset($_POST['patiente_id']) && $_POST['patiente_id'] == $pat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($pat['prenom'] . ' ' . $pat['nom'] . ' (' . date('d/m/Y', strtotime($pat['date_naissance'])) . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Date du décès *</label>
                            <input type="date" name="date_deces" value="<?php echo htmlspecialchars($_POST['date_deces'] ?? ''); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Heure du décès</label>
                            <input type="time" name="heure_deces" value="<?php echo htmlspecialchars($_POST['heure_deces'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cause du décès *</label>
                        <input type="text" name="cause_deces" value="<?php echo htmlspecialchars($_POST['cause_deces'] ?? ''); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Âge au décès (heures)</label>
                            <input type="number" name="age_deces" value="<?php echo htmlspecialchars($_POST['age_deces'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Lieu du décès</label>
                            <input type="text" name="lieu_deces" value="<?php echo htmlspecialchars($_POST['lieu_deces'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Observations</label>
                        <textarea name="observations" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500"><?php echo htmlspecialchars($_POST['observations'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                        <a href="/deces.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                            <i class="fas fa-times mr-2"></i>Annuler
                        </a>
                        <button type="submit" class="px-6 py-2 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-md hover:shadow-lg transition-all duration-300">
                            <i class="fas fa-save mr-2"></i>Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html> 