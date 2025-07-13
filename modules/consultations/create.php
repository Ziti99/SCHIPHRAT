<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';

// Vérifier l'authentification
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../../login.php');
    exit();
}

$database = new Database();
$user = $auth->getCurrentUser();
$message = '';

// Récupérer la liste des patientes pour le select
$patientes = $database->fetchAll("SELECT id, nom, prenom FROM patientes ORDER BY nom, prenom");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patiente_id = $_POST['patiente_id'] ?? '';
    $date_consultation = $_POST['date_consultation'] ?? '';
    $type_consultation = $_POST['type_consultation'] ?? '';
    $motif = $_POST['motif'] ?? '';
    $examen_clinique = $_POST['examen_clinique'] ?? '';
    $diagnostic = $_POST['diagnostic'] ?? '';
    $traitement = $_POST['traitement'] ?? '';
    $observations = $_POST['observations'] ?? '';
    $prochaine_consultation = $_POST['prochaine_consultation'] ?? '';
    
    // Validation
    if (empty($patiente_id) || empty($date_consultation) || empty($type_consultation)) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Tous les champs obligatoires doivent être remplis.</div>';
    } else {
        try {
            $sql = "INSERT INTO consultations (patiente_id, date_consultation, type_consultation, motif, examen_clinique, diagnostic, traitement, observations, prochaine_consultation, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $params = [$patiente_id, $date_consultation, $type_consultation, $motif, $examen_clinique, $diagnostic, $traitement, $observations, $prochaine_consultation];
            
            $database->query($sql, $params);
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">Consultation créée avec succès !</div>';
            
            // Rediriger après 2 secondes
            header("refresh:2;url=dashboard.php");
        } catch (Exception $e) {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Erreur lors de la création : ' . $e->getMessage() . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Consultation - Clinique Obstétrique</title>
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
                        <i class="fas fa-stethoscope text-2xl text-pink-600 mr-3"></i>
                        <span class="text-xl font-bold text-gray-900">Nouvelle Consultation</span>
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
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Nouvelle Consultation</h1>
            <p class="text-gray-600">Créer une nouvelle consultation prénatale</p>
        </div>

        <!-- Formulaire -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Informations de la consultation</h2>
            </div>
            <div class="p-6">
                        <?php if ($message): ?>
                            <?php echo $message; ?>
                        <?php endif; ?>

                        <form method="POST" class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Patiente -->
                                <div>
                                    <label for="patiente_id" class="block text-sm font-medium text-gray-700">Patiente *</label>
                                    <select name="patiente_id" id="patiente_id" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                        <option value="">Sélectionner une patiente</option>
                                        <?php foreach ($patientes as $patiente): ?>
                                            <option value="<?php echo htmlspecialchars($patiente['id']); ?>">
                                                <?php echo htmlspecialchars($patiente['nom'] . ' ' . $patiente['prenom']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Date de consultation -->
                                <div>
                                    <label for="date_consultation" class="block text-sm font-medium text-gray-700">Date de consultation *</label>
                                    <input type="date" name="date_consultation" id="date_consultation" required value="<?php echo date('Y-m-d'); ?>" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                </div>

                                <!-- Type de consultation -->
                                <div>
                                    <label for="type_consultation" class="block text-sm font-medium text-gray-700">Type de consultation *</label>
                                    <select name="type_consultation" id="type_consultation" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                        <option value="">Sélectionner le type</option>
                                        <option value="Première consultation">Première consultation</option>
                                        <option value="Consultation de suivi">Consultation de suivi</option>
                                        <option value="Consultation d'urgence">Consultation d'urgence</option>
                                        <option value="Consultation post-accouchement">Consultation post-accouchement</option>
                                        <option value="Échographie">Échographie</option>
                                        <option value="Autre">Autre</option>
                                    </select>
                                </div>

                                <!-- Prochaine consultation -->
                                <div>
                                    <label for="prochaine_consultation" class="block text-sm font-medium text-gray-700">Prochaine consultation</label>
                                    <input type="date" name="prochaine_consultation" id="prochaine_consultation" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                </div>
                            </div>

                            <!-- Motif -->
                            <div>
                                <label for="motif" class="block text-sm font-medium text-gray-700">Motif de la consultation</label>
                                <textarea name="motif" id="motif" rows="3" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Décrivez le motif de la consultation..."></textarea>
                            </div>

                            <!-- Examen clinique -->
                            <div>
                                <label for="examen_clinique" class="block text-sm font-medium text-gray-700">Examen clinique</label>
                                <textarea name="examen_clinique" id="examen_clinique" rows="4" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Détails de l'examen clinique..."></textarea>
                            </div>

                            <!-- Diagnostic -->
                            <div>
                                <label for="diagnostic" class="block text-sm font-medium text-gray-700">Diagnostic</label>
                                <textarea name="diagnostic" id="diagnostic" rows="3" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Diagnostic établi..."></textarea>
                            </div>

                            <!-- Traitement -->
                            <div>
                                <label for="traitement" class="block text-sm font-medium text-gray-700">Traitement prescrit</label>
                                <textarea name="traitement" id="traitement" rows="3" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Traitement prescrit..."></textarea>
                            </div>

                            <!-- Observations -->
                            <div>
                                <label for="observations" class="block text-sm font-medium text-gray-700">Observations</label>
                                <textarea name="observations" id="observations" rows="3" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Observations supplémentaires..."></textarea>
                            </div>

                            <!-- Boutons -->
                            <div class="flex justify-end space-x-3 pt-6 border-t">
                                <a href="dashboard.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded-lg transition-all duration-300">
                                    <i class="fas fa-times mr-2"></i>Annuler
                                </a>
                                <button type="submit" class="bg-gradient-to-r from-pink-500 to-purple-500 hover:from-pink-600 hover:to-purple-600 text-white font-bold py-2 px-6 rounded-lg transition-all duration-300">
                                    <i class="fas fa-save mr-2"></i>Créer la consultation
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 