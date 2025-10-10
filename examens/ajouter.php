<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$db = new Database();

$consultation_id = $_GET['consultation_id'] ?? 0;
$error_message = '';

// Récupérer les infos de la consultation
$consultation = $db->fetch("
    SELECT cp.*, pat.nom, pat.prenom
    FROM consultations_prenatales cp
    INNER JOIN patientes pat ON cp.patiente_id = pat.id
    WHERE cp.id = ?
", [$consultation_id]);

if (!$consultation) {
    header('Location: /consultations.php');
    exit;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $type_examen = $_POST['type_examen'] ?? '';
        $date_examen = $_POST['date_examen'] ?? '';
        $resultats = $_POST['resultats'] ?? null;
        $observations = $_POST['observations'] ?? null;
        
        if (empty($type_examen) || empty($date_examen)) {
            throw new Exception('Les champs obligatoires doivent être remplis');
        }
        
        // Insertion de l'examen
        $db->query("
            INSERT INTO examens (
                consultation_id, 
                type_examen, 
                date_examen, 
                resultats, 
                observations,
                created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ", [
            $consultation_id,
            $type_examen,
            $date_examen,
            $resultats,
            $observations
        ]);
        
        header('Location: /consultations/voir.php?id=' . $consultation_id . '&success_examen=1');
        exit;
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Examen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        <div class="flex-1">
            <?php include '../includes/navbar.php'; ?>
            
            <div class="p-8">
                <a href="../consultations/voir.php?id=<?php echo $consultation_id; ?>" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                    <i class="fas fa-arrow-left mr-2"></i>Retour à la consultation
                </a>

                <h1 class="text-3xl font-bold text-gray-900 mb-6">
                    <i class="fas fa-microscope text-blue-600 mr-3"></i>
                    Ajouter un Examen Médical
                </h1>

                <!-- Info consultation -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <p class="text-blue-900">
                        <strong>Consultation du</strong> <?php echo date('d/m/Y à H:i', strtotime($consultation['date_consultation'])); ?>
                        <strong>pour</strong> <?php echo htmlspecialchars($consultation['prenom'] . ' ' . $consultation['nom']); ?>
                    </p>
                </div>

                <?php if ($error_message): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Formulaire -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <form method="POST" class="space-y-6">
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Type d'examen <span class="text-red-500">*</span>
                                </label>
                                <select name="type_examen" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="">Sélectionner...</option>
                                    <option value="echographie">Échographie</option>
                                    <option value="bilan_sanguin">Bilan sanguin</option>
                                    <option value="urine">Analyse d'urine</option>
                                    <option value="autre">Autre</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Date de l'examen <span class="text-red-500">*</span>
                                </label>
                                <input type="date" name="date_examen" required 
                                       value="<?php echo date('Y-m-d'); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Résultats de l'examen
                            </label>
                            <textarea name="resultats" rows="5" 
                                      placeholder="Détails des résultats de l'examen..."
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Observations
                            </label>
                            <textarea name="observations" rows="3" 
                                      placeholder="Observations complémentaires..."
                                      class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                        </div>

                        <div class="flex gap-4">
                            <button type="submit" class="flex-1 bg-blue-500 text-white py-3 rounded-lg hover:bg-blue-600 font-semibold">
                                <i class="fas fa-save mr-2"></i>Enregistrer l'Examen
                            </button>
                            <a href="../consultations/voir.php?id=<?php echo $consultation_id; ?>" 
                               class="flex-1 text-center border border-gray-300 text-gray-700 py-3 rounded-lg hover:bg-gray-50">
                                Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

