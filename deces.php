<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$db = new Database();

// Gestion des actions : ajout, modification, vue
$action = $_GET['action'] ?? 'liste';
$deces_id = $_GET['id'] ?? null;

// Ajout ou modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patiente_id = $_POST['patiente_id'] ?? '';
    $date_deces = $_POST['date_deces'] ?? '';
    $heure_deces = $_POST['heure_deces'] ?? '';
    $cause_deces = $_POST['cause_deces'] ?? '';
    $age_deces = $_POST['age_deces'] ?? '';
    $lieu_deces = $_POST['lieu_deces'] ?? '';
    $medecin_id = $_SESSION['user_id'];
    $observations = $_POST['observations'] ?? '';
    $errors = [];
    if (empty($patiente_id)) $errors[] = "La patiente est requise.";
    if (empty($date_deces)) $errors[] = "La date de décès est requise.";
    if (empty($cause_deces)) $errors[] = "La cause du décès est requise.";
    if (empty($errors)) {
        $date_time_deces = $date_deces . ' ' . ($heure_deces ?: '00:00:00');
        if ($action === 'edit' && $deces_id) {
            $db->query("UPDATE deces SET patiente_id=?, date_deces=?, cause_deces=?, age_deces=?, lieu_deces=?, medecin_id=?, observations=? WHERE id=?",
                [$patiente_id, $date_time_deces, $cause_deces, $age_deces, $lieu_deces, $medecin_id, $observations, $deces_id]);
            header('Location: deces.php?action=view&id=' . $deces_id . '&success=1');
            exit;
        } else {
            $db->query("INSERT INTO deces (patiente_id, date_deces, cause_deces, age_deces, lieu_deces, medecin_id, observations) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$patiente_id, $date_time_deces, $cause_deces, $age_deces, $lieu_deces, $medecin_id, $observations]);
            header('Location: deces.php?success=1');
            exit;
        }
    }
}

// Récupération des patientes pour le select
$patientes = $db->fetchAll("SELECT id, nom, prenom, date_naissance FROM patientes ORDER BY nom, prenom");

// Vue détaillée
if ($action === 'view' && $deces_id) {
    $deces = $db->fetch("SELECT d.*, p.nom, p.prenom, p.date_naissance, p.nationalite, medecin.nom as medecin_nom, medecin.prenom as medecin_prenom FROM deces d JOIN patientes p ON d.patiente_id = p.id JOIN users medecin ON d.medecin_id = medecin.id WHERE d.id = ?", [$deces_id]);
    if (!$deces) {
        header('Location: deces.php');
        exit;
    }
}
// Edition
if ($action === 'edit' && $deces_id) {
    $deces = $db->fetch("SELECT * FROM deces WHERE id = ?", [$deces_id]);
    if (!$deces) {
        header('Location: deces.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Décès - Clinique Obstétrique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-br from-gray-50 via-red-50 to-pink-50 min-h-screen">
    <div class="flex">
        <?php include 'includes/sidebar.php'; ?>
        <div class="flex-1">
            <nav class="bg-white shadow-lg border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <a href="registres.php" class="text-purple-600 hover:text-purple-800 mr-4">
                                <i class="fas fa-arrow-left mr-2"></i>Retour
                            </a>
                            <div class="flex-shrink-0 flex items-center">
                                <i class="fas fa-cross text-2xl text-red-600 mr-3"></i>
                                <span class="text-xl font-bold text-gray-900">Gestion des Décès</span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <span class="text-gray-700">
                                <i class="fas fa-user mr-2"></i>
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </span>
                            <a href="logout.php" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-sign-out-alt mr-2"></i>Déconnexion
                            </a>
                        </div>
                    </div>
                </div>
            </nav>
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <?php if (isset($_GET['success'])): ?>
                    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                        <div class="flex">
                            <i class="fas fa-check-circle mr-2 mt-1"></i>
                            <div>
                                <strong>Succès !</strong> L'opération a été réalisée avec succès.
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($action === 'add' || ($action === 'edit' && isset($deces))): ?>
                    <h2 class="text-2xl font-bold text-gray-900 mb-6"><?php echo $action === 'add' ? 'Ajouter un Décès' : 'Modifier le Décès'; ?></h2>
                    <?php if (!empty($errors)): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                            <ul class="list-disc list-inside">
                                <?php foreach ($errors as $err): ?><li><?php echo htmlspecialchars($err); ?></li><?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <form method="POST" class="space-y-6 bg-white rounded-xl shadow-lg p-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Patiente *</label>
                            <select name="patiente_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <option value="">Sélectionner une patiente</option>
                                <?php foreach ($patientes as $pat): ?>
                                    <option value="<?php echo $pat['id']; ?>" <?php echo (isset($deces['patiente_id']) && $deces['patiente_id'] == $pat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($pat['prenom'] . ' ' . $pat['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Date du décès *</label>
                                <input type="date" name="date_deces" value="<?php echo isset($deces['date_deces']) ? date('Y-m-d', strtotime($deces['date_deces'])) : ''; ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Heure du décès</label>
                                <input type="time" name="heure_deces" value="<?php echo isset($deces['date_deces']) ? date('H:i', strtotime($deces['date_deces'])) : ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cause du décès *</label>
                            <input type="text" name="cause_deces" value="<?php echo htmlspecialchars($deces['cause_deces'] ?? ''); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Âge au décès (heures)</label>
                                <input type="number" name="age_deces" value="<?php echo htmlspecialchars($deces['age_deces'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Lieu du décès</label>
                                <input type="text" name="lieu_deces" value="<?php echo htmlspecialchars($deces['lieu_deces'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Observations</label>
                            <textarea name="observations" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md"><?php echo htmlspecialchars($deces['observations'] ?? ''); ?></textarea>
                        </div>
                        <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                            <a href="deces.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                                <i class="fas fa-times mr-2"></i>Annuler
                            </a>
                            <button type="submit" class="px-6 py-2 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-md hover:shadow-lg transition-all duration-300">
                                <i class="fas fa-save mr-2"></i>Enregistrer
                            </button>
                        </div>
                    </form>
                <?php elseif ($action === 'view' && isset($deces)): ?>
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Détail du Décès</h2>
                    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Patiente</label>
                                <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($deces['prenom'] . ' ' . $deces['nom']); ?></p>
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
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700">Observations</label>
                            <p class="text-gray-900 mt-1"><?php echo nl2br(htmlspecialchars($deces['observations'] ?? 'Aucune observation')); ?></p>
                        </div>
                    </div>
                    <div class="flex justify-between items-center">
                        <a href="deces.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                            <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
                        </a>
                        <a href="deces.php?action=edit&id=<?php echo $deces_id; ?>" class="px-6 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition-colors">
                            <i class="fas fa-edit mr-2"></i>Modifier
                        </a>
                    </div>
                <?php else: ?>
                    <div class="flex justify-between items-center mb-8">
                        <h2 class="text-2xl font-bold text-gray-900">Décès enregistrés</h2>
                        <a href="deces.php?action=add" class="bg-gradient-to-r from-red-500 to-pink-500 text-white px-6 py-3 rounded-lg hover:shadow-lg transition-all duration-300 flex items-center">
                            <i class="fas fa-plus mr-2"></i>
                            Nouveau décès
                        </a>
                    </div>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gradient-to-r from-red-500 to-pink-500 text-white">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Patiente</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Cause</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Lieu</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Médecin</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php
                                    $liste = $db->fetchAll("SELECT d.id, d.date_deces, d.cause_deces, d.lieu_deces, p.nom, p.prenom, medecin.nom as medecin_nom, medecin.prenom as medecin_prenom FROM deces d JOIN patientes p ON d.patiente_id = p.id JOIN users medecin ON d.medecin_id = medecin.id ORDER BY d.date_deces DESC");
                                    if (empty($liste)):
                                    ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-cross text-4xl mb-4 text-gray-300"></i>
                                            <p class="text-lg">Aucun décès enregistré</p>
                                        </td>
                                    </tr>
                                    <?php else:
                                    foreach ($liste as $row): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo date('d/m/Y H:i', strtotime($row['date_deces'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['prenom'] . ' ' . $row['nom']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['cause_deces']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($row['lieu_deces'] ?? 'Non défini'); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">Dr. <?php echo htmlspecialchars($row['medecin_prenom'] . ' ' . $row['medecin_nom']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="deces.php?action=view&id=<?php echo $row['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3"><i class="fas fa-eye"></i></a>
                                            <a href="deces.php?action=edit&id=<?php echo $row['id']; ?>" class="text-red-600 hover:text-red-900"><i class="fas fa-edit"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 