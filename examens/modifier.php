<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$db = new Database();

$examen_id = $_GET['id'] ?? 0;
$error_message = '';

// Récupérer l'examen
$examen = $db->fetch("SELECT * FROM examens WHERE id = ?", [$examen_id]);

if (!$examen) {
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
        
        $db->query("
            UPDATE examens SET
                type_examen = ?,
                date_examen = ?,
                resultats = ?,
                observations = ?,
                updated_at = NOW()
            WHERE id = ?
        ", [
            $type_examen,
            $date_examen,
            $resultats,
            $observations,
            $examen_id
        ]);
        
        header('Location: voir.php?id=' . $examen_id . '&success=1');
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
    <title>Modifier Examen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        <div class="flex-1">
            <?php include '../includes/navbar.php'; ?>
            
            <div class="p-8">
                <a href="voir.php?id=<?php echo $examen_id; ?>" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                    <i class="fas fa-arrow-left mr-2"></i>Retour
                </a>

                <h1 class="text-3xl font-bold text-gray-900 mb-6">Modifier l'Examen</h1>

                <?php if ($error_message): ?>
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-lg shadow-lg p-6">
                    <form method="POST" class="space-y-6">
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Type d'examen <span class="text-red-500">*</span>
                                </label>
                                <select name="type_examen" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                                    <option value="">Sélectionner...</option>
                                    <option value="echographie" <?php echo $examen['type_examen'] === 'echographie' ? 'selected' : ''; ?>>Échographie</option>
                                    <option value="bilan_sanguin" <?php echo $examen['type_examen'] === 'bilan_sanguin' ? 'selected' : ''; ?>>Bilan sanguin</option>
                                    <option value="urine" <?php echo $examen['type_examen'] === 'urine' ? 'selected' : ''; ?>>Analyse d'urine</option>
                                    <option value="autre" <?php echo $examen['type_examen'] === 'autre' ? 'selected' : ''; ?>>Autre</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Date de l'examen <span class="text-red-500">*</span>
                                </label>
                                <input type="date" name="date_examen" required 
                                       value="<?php echo $examen['date_examen']; ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Résultats</label>
                            <textarea name="resultats" rows="5" class="w-full px-4 py-3 border border-gray-300 rounded-lg"><?php echo htmlspecialchars($examen['resultats'] ?? ''); ?></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Observations</label>
                            <textarea name="observations" rows="3" class="w-full px-4 py-3 border border-gray-300 rounded-lg"><?php echo htmlspecialchars($examen['observations'] ?? ''); ?></textarea>
                        </div>

                        <div class="flex gap-4">
                            <button type="submit" class="flex-1 bg-blue-500 text-white py-3 rounded-lg hover:bg-blue-600 font-semibold">
                                <i class="fas fa-save mr-2"></i>Enregistrer
                            </button>
                            <a href="voir.php?id=<?php echo $examen_id; ?>" class="flex-1 text-center border border-gray-300 text-gray-700 py-3 rounded-lg hover:bg-gray-50">
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

