<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
$db = new Database();

$examen_id = $_GET['id'] ?? 0;

// Récupérer les infos de l'examen
$examen = $db->fetch("
    SELECT e.*, cp.date_consultation, pat.nom, pat.prenom, pat.date_naissance
    FROM examens e
    INNER JOIN consultations_prenatales cp ON e.consultation_id = cp.id
    INNER JOIN patientes pat ON cp.patiente_id = pat.id
    WHERE e.id = ?
", [$examen_id]);

if (!$examen) {
    header('Location: /consultations.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détails Examen</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        <div class="flex-1">
            <?php include '../includes/navbar.php'; ?>
            
            <div class="p-8">
                <a href="../consultations/voir.php?id=<?php echo $examen['consultation_id']; ?>" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
                    <i class="fas fa-arrow-left mr-2"></i>Retour à la consultation
                </a>

                <h1 class="text-3xl font-bold text-gray-900 mb-6">Détails de l'Examen</h1>

                <div class="bg-white rounded-lg shadow-lg p-6">
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 mb-4">Informations Patiente</h2>
                            <dl class="space-y-2 text-sm">
                                <div><dt class="text-gray-600">Nom:</dt><dd class="font-semibold"><?php echo htmlspecialchars($examen['prenom'] . ' ' . $examen['nom']); ?></dd></div>
                                <div><dt class="text-gray-600">Date naissance:</dt><dd><?php echo date('d/m/Y', strtotime($examen['date_naissance'])); ?></dd></div>
                                <div><dt class="text-gray-600">Date consultation:</dt><dd><?php echo date('d/m/Y à H:i', strtotime($examen['date_consultation'])); ?></dd></div>
                            </dl>
                        </div>

                        <div>
                            <h2 class="text-xl font-bold text-gray-900 mb-4">Détails Examen</h2>
                            <dl class="space-y-2 text-sm">
                                <div><dt class="text-gray-600">Type:</dt><dd class="font-semibold capitalize"><?php echo str_replace('_', ' ', $examen['type_examen']); ?></dd></div>
                                <div><dt class="text-gray-600">Date examen:</dt><dd><?php echo date('d/m/Y', strtotime($examen['date_examen'])); ?></dd></div>
                            </dl>
                        </div>
                    </div>

                    <div class="mt-6 border-t pt-6">
                        <h3 class="font-semibold text-gray-900 mb-3">Résultats:</h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-gray-800 whitespace-pre-line"><?php echo htmlspecialchars($examen['resultats'] ?? 'Aucun résultat renseigné'); ?></p>
                        </div>
                    </div>

                    <?php if ($examen['observations']): ?>
                    <div class="mt-6">
                        <h3 class="font-semibold text-gray-900 mb-3">Observations:</h3>
                        <div class="bg-blue-50 rounded-lg p-4">
                            <p class="text-gray-800 whitespace-pre-line"><?php echo htmlspecialchars($examen['observations']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="mt-6 flex gap-4">
                        <a href="modifier.php?id=<?php echo $examen_id; ?>" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600">
                            <i class="fas fa-edit mr-2"></i>Modifier
                        </a>
                        <a href="../consultations/voir.php?id=<?php echo $examen['consultation_id']; ?>" class="border border-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-50">
                            Retour
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

