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
$message = '';

// Récupérer l'ID de la consultation
$consultation_id = $_GET['id'] ?? null;

if (!$consultation_id) {
    header('Location: dashboard.php');
    exit();
}

// Récupérer les détails de la consultation
$consultation = $database->fetchOne("SELECT * FROM consultations WHERE id = ?", [$consultation_id]);

if (!$consultation) {
    header('Location: dashboard.php');
    exit();
}

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
            $sql = "UPDATE consultations SET patiente_id = ?, date_consultation = ?, type_consultation = ?, motif = ?, examen_clinique = ?, diagnostic = ?, traitement = ?, observations = ?, prochaine_consultation = ? WHERE id = ?";
            $params = [$patiente_id, $date_consultation, $type_consultation, $motif, $examen_clinique, $diagnostic, $traitement, $observations, $prochaine_consultation, $consultation_id];
            
            $database->query($sql, $params);
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">Consultation mise à jour avec succès !</div>';
            
            // Rediriger après 2 secondes
            header("refresh:2;url=view.php?id=" . $consultation_id);
        } catch (Exception $e) {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Erreur lors de la mise à jour : ' . $e->getMessage() . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Consultation - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <h1 class="text-3xl font-bold text-gray-900">Modifier Consultation</h1>
                    <a href="view.php?id=<?php echo $consultation_id; ?>" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Retour aux détails
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
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
                                            <option value="<?php echo htmlspecialchars($patiente['id']); ?>" <?php echo ($patiente['id'] == $consultation['patiente_id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($patiente['nom'] . ' ' . $patiente['prenom']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Date de consultation -->
                                <div>
                                    <label for="date_consultation" class="block text-sm font-medium text-gray-700">Date de consultation *</label>
                                    <input type="date" name="date_consultation" id="date_consultation" required value="<?php echo htmlspecialchars($consultation['date_consultation']); ?>" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                </div>

                                <!-- Type de consultation -->
                                <div>
                                    <label for="type_consultation" class="block text-sm font-medium text-gray-700">Type de consultation *</label>
                                    <select name="type_consultation" id="type_consultation" required class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                        <option value="">Sélectionner le type</option>
                                        <option value="Première consultation" <?php echo ($consultation['type_consultation'] == 'Première consultation') ? 'selected' : ''; ?>>Première consultation</option>
                                        <option value="Consultation de suivi" <?php echo ($consultation['type_consultation'] == 'Consultation de suivi') ? 'selected' : ''; ?>>Consultation de suivi</option>
                                        <option value="Consultation d'urgence" <?php echo ($consultation['type_consultation'] == 'Consultation d\'urgence') ? 'selected' : ''; ?>>Consultation d'urgence</option>
                                        <option value="Consultation post-accouchement" <?php echo ($consultation['type_consultation'] == 'Consultation post-accouchement') ? 'selected' : ''; ?>>Consultation post-accouchement</option>
                                        <option value="Échographie" <?php echo ($consultation['type_consultation'] == 'Échographie') ? 'selected' : ''; ?>>Échographie</option>
                                        <option value="Autre" <?php echo ($consultation['type_consultation'] == 'Autre') ? 'selected' : ''; ?>>Autre</option>
                                    </select>
                                </div>

                                <!-- Prochaine consultation -->
                                <div>
                                    <label for="prochaine_consultation" class="block text-sm font-medium text-gray-700">Prochaine consultation</label>
                                    <input type="date" name="prochaine_consultation" id="prochaine_consultation" value="<?php echo htmlspecialchars($consultation['prochaine_consultation'] ?? ''); ?>" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                                </div>
                            </div>

                            <!-- Motif -->
                            <div>
                                <label for="motif" class="block text-sm font-medium text-gray-700">Motif de la consultation</label>
                                <textarea name="motif" id="motif" rows="3" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Décrivez le motif de la consultation..."><?php echo htmlspecialchars($consultation['motif']); ?></textarea>
                            </div>

                            <!-- Examen clinique -->
                            <div>
                                <label for="examen_clinique" class="block text-sm font-medium text-gray-700">Examen clinique</label>
                                <textarea name="examen_clinique" id="examen_clinique" rows="4" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Détails de l'examen clinique..."><?php echo htmlspecialchars($consultation['examen_clinique']); ?></textarea>
                            </div>

                            <!-- Diagnostic -->
                            <div>
                                <label for="diagnostic" class="block text-sm font-medium text-gray-700">Diagnostic</label>
                                <textarea name="diagnostic" id="diagnostic" rows="3" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Diagnostic établi..."><?php echo htmlspecialchars($consultation['diagnostic']); ?></textarea>
                            </div>

                            <!-- Traitement -->
                            <div>
                                <label for="traitement" class="block text-sm font-medium text-gray-700">Traitement prescrit</label>
                                <textarea name="traitement" id="traitement" rows="3" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Traitement prescrit..."><?php echo htmlspecialchars($consultation['traitement']); ?></textarea>
                            </div>

                            <!-- Observations -->
                            <div>
                                <label for="observations" class="block text-sm font-medium text-gray-700">Observations</label>
                                <textarea name="observations" id="observations" rows="3" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Observations supplémentaires..."><?php echo htmlspecialchars($consultation['observations']); ?></textarea>
                            </div>

                            <!-- Boutons -->
                            <div class="flex justify-end space-x-3">
                                <a href="view.php?id=<?php echo $consultation_id; ?>" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                    Annuler
                                </a>
                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Mettre à jour
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 