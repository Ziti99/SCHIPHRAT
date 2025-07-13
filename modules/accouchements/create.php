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
    $date_accouchement = $_POST['date_accouchement'] ?? '';
    $heure_debut = $_POST['heure_debut'] ?? '';
    $heure_fin = $_POST['heure_fin'] ?? '';
    $type_accouchement = $_POST['type_accouchement'] ?? '';
    $presentation = $_POST['presentation'] ?? '';
    $complications = $_POST['complications'] ?? '';
    $interventions = $_POST['interventions'] ?? '';
    $sexe_bebe = $_POST['sexe_bebe'] ?? '';
    $poids_bebe = $_POST['poids_bebe'] ?? '';
    $taille_bebe = $_POST['taille_bebe'] ?? '';
    $apgar_score = $_POST['apgar_score'] ?? '';
    $observations = $_POST['observations'] ?? '';
    
    // Validation
    if (empty($patiente_id) || empty($date_accouchement) || empty($type_accouchement)) {
        $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Tous les champs obligatoires doivent être remplis.</div>';
    } else {
        try {
            $sql = "INSERT INTO accouchements (patiente_id, date_accouchement, heure_debut, heure_fin, type_accouchement, presentation, complications, interventions, sexe_bebe, poids_bebe, taille_bebe, apgar_score, observations, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $params = [$patiente_id, $date_accouchement, $heure_debut, $heure_fin, $type_accouchement, $presentation, $complications, $interventions, $sexe_bebe, $poids_bebe, $taille_bebe, $apgar_score, $observations];
            
            $database->query($sql, $params);
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">Accouchement enregistré avec succès !</div>';
            
            // Rediriger après 2 secondes
            header("refresh:2;url=dashboard.php");
        } catch (Exception $e) {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Erreur lors de l\'enregistrement : ' . $e->getMessage() . '</div>';
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
    <!-- Navigation -->
    <nav class="bg-white shadow-lg border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-purple-600 hover:text-purple-800 mr-4">
                        <i class="fas fa-arrow-left mr-2"></i>Retour
                    </a>
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-baby text-2xl text-pink-600 mr-3"></i>
                        <span class="text-xl font-bold text-gray-900">Nouvel Accouchement</span>
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
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Nouvel Accouchement</h1>
            <p class="text-gray-600">Enregistrer un nouvel accouchement</p>
        </div>

        <!-- Formulaire -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Informations de l'accouchement</h2>
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

                        <!-- Date d'accouchement -->
                        <div>
                            <label for="date_accouchement" class="block text-sm font-medium text-gray-700">Date d'accouchement *</label>
                            <input type="date" name="date_accouchement" id="date_accouchement" required value="<?php echo date('Y-m-d'); ?>" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>

                        <!-- Heure début -->
                        <div>
                            <label for="heure_debut" class="block text-sm font-medium text-gray-700">Heure de début</label>
                            <input type="time" name="heure_debut" id="heure_debut" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>

                        <!-- Heure fin -->
                        <div>
                            <label for="heure_fin" class="block text-sm font-medium text-gray-700">Heure de fin</label>
                            <input type="time" name="heure_fin" id="heure_fin" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        </div>

                        <!-- Type d'accouchement -->
                        <div>
                            <label for="type_accouchement" class="block text-sm font-medium text-gray-700">Type d'accouchement *</label>
                            <select name="type_accouchement" id="type_accouchement" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="">Sélectionner le type</option>
                                <option value="Voie basse naturelle">Voie basse naturelle</option>
                                <option value="Voie basse instrumentale">Voie basse instrumentale</option>
                                <option value="Césarienne programmée">Césarienne programmée</option>
                                <option value="Césarienne en urgence">Césarienne en urgence</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>

                        <!-- Présentation -->
                        <div>
                            <label for="presentation" class="block text-sm font-medium text-gray-700">Présentation</label>
                            <select name="presentation" id="presentation" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="">Sélectionner la présentation</option>
                                <option value="Céphalique">Céphalique</option>
                                <option value="Siège">Siège</option>
                                <option value="Transverse">Transverse</option>
                                <option value="Autre">Autre</option>
                            </select>
                        </div>

                        <!-- Sexe du bébé -->
                        <div>
                            <label for="sexe_bebe" class="block text-sm font-medium text-gray-700">Sexe du bébé</label>
                            <select name="sexe_bebe" id="sexe_bebe" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                <option value="">Sélectionner</option>
                                <option value="Masculin">Masculin</option>
                                <option value="Féminin">Féminin</option>
                            </select>
                        </div>

                        <!-- Poids du bébé -->
                        <div>
                            <label for="poids_bebe" class="block text-sm font-medium text-gray-700">Poids du bébé (g)</label>
                            <input type="number" name="poids_bebe" id="poids_bebe" min="0" step="10" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Ex: 3200">
                        </div>

                        <!-- Taille du bébé -->
                        <div>
                            <label for="taille_bebe" class="block text-sm font-medium text-gray-700">Taille du bébé (cm)</label>
                            <input type="number" name="taille_bebe" id="taille_bebe" min="0" step="0.1" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Ex: 50">
                        </div>

                        <!-- Score APGAR -->
                        <div>
                            <label for="apgar_score" class="block text-sm font-medium text-gray-700">Score APGAR</label>
                            <input type="text" name="apgar_score" id="apgar_score" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Ex: 9/10">
                        </div>
                    </div>

                    <!-- Complications -->
                    <div>
                        <label for="complications" class="block text-sm font-medium text-gray-700">Complications</label>
                        <textarea name="complications" id="complications" rows="3" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Décrivez les complications éventuelles..."></textarea>
                    </div>

                    <!-- Interventions -->
                    <div>
                        <label for="interventions" class="block text-sm font-medium text-gray-700">Interventions réalisées</label>
                        <textarea name="interventions" id="interventions" rows="3" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Détaillez les interventions réalisées..."></textarea>
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
                            <i class="fas fa-save mr-2"></i>Enregistrer l'accouchement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html> 