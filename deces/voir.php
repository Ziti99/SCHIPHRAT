<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$deces_id = $_GET['id'] ?? 0;

if (!$deces_id) {
    header('Location: /deces.php');
    exit;
}

// Récupération des données du décès
$deces = $db->fetch("SELECT d.*, p.nom, p.prenom, p.date_naissance, p.nationalite, medecin.nom as medecin_nom, medecin.prenom as medecin_prenom FROM deces d JOIN patientes p ON d.patiente_id = p.id JOIN users medecin ON d.medecin_id = medecin.id WHERE d.id = ?", [$deces_id]);

if (!$deces) {
    header('Location: /deces.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détail du Décès - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="flex">
        <?php include '../includes/sidebar.php'; ?>
        <main class="flex-1 p-8">
            <!-- En-tête -->
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Détail du Décès</h2>
                <div class="flex space-x-2">
                    <a href="/deces.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">
                        <i class="fas fa-arrow-left mr-2"></i>Retour
                    </a>
                    <a href="modifier.php?id=<?php echo $deces_id; ?>" class="px-4 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors">
                        <i class="fas fa-edit mr-2"></i>Modifier
                    </a>
                </div>
            </div>

            <!-- Détails -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Patiente</label>
                        <p class="text-gray-900 font-medium text-lg"><?php echo htmlspecialchars($deces['prenom'] . ' ' . $deces['nom']); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date de naissance</label>
                        <p class="text-gray-900"><?php echo date('d/m/Y', strtotime($deces['date_naissance'])); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Date du décès</label>
                        <p class="text-gray-900"><?php echo date('d/m/Y H:i', strtotime($deces['date_deces'])); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Cause du décès</label>
                        <p class="text-gray-900"><?php echo htmlspecialchars($deces['cause_deces']); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Âge au décès (heures)</label>
                        <p class="text-gray-900"><?php echo htmlspecialchars($deces['age_deces'] ?? 'Non défini'); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Lieu du décès</label>
                        <p class="text-gray-900"><?php echo htmlspecialchars($deces['lieu_deces'] ?? 'Non défini'); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Médecin</label>
                        <p class="text-gray-900">Dr. <?php echo htmlspecialchars($deces['medecin_prenom'] . ' ' . $deces['medecin_nom']); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nationalité</label>
                        <p class="text-gray-900"><?php echo htmlspecialchars($deces['nationalite'] ?? 'Non définie'); ?></p>
                    </div>
                </div>
                
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700">Observations</label>
                    <p class="text-gray-900 mt-1"><?php echo nl2br(htmlspecialchars($deces['observations'] ?? 'Aucune observation')); ?></p>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 