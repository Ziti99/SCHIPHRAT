<?php
session_start();
require_once '../../config/database.php';
require_once '../../config/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

$user = $auth->getCurrentUser();
$db = new Database();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: dashboard.php');
    exit;
}

$registre = $db->fetchOne("SELECT * FROM registres WHERE id = ?", [$id]);

if (!$registre) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type_registre = trim($_POST['type_registre']);
    $description = trim($_POST['description']);
    
    if (empty($type_registre)) {
        $error = 'Le type de registre est requis';
    } else {
        try {
            $db->query("
                UPDATE registres 
                SET type_registre = ?, description = ?
                WHERE id = ?
            ", [$type_registre, $description, $id]);
            
            $message = 'Registre modifié avec succès';
            $registre = $db->fetchOne("SELECT * FROM registres WHERE id = ?", [$id]);
        } catch (Exception $e) {
            $error = 'Erreur lors de la modification du registre';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le Registre - Clinique Obstétrique</title>
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
                        <i class="fas fa-book text-2xl text-purple-600 mr-3"></i>
                        <span class="text-xl font-bold text-gray-900">Modifier le Registre</span>
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

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- En-tête -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Modifier le Registre</h1>
            <p class="text-gray-600">Modifier les informations du registre</p>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <form method="POST" class="space-y-6">
                <div>
                    <label for="type_registre" class="block text-sm font-medium text-gray-700 mb-2">
                        Type de Registre *
                    </label>
                    <select name="type_registre" id="type_registre" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="">Sélectionner un type</option>
                        <option value="Consultations" <?php echo $registre['type_registre'] === 'Consultations' ? 'selected' : ''; ?>>Registre des Consultations</option>
                        <option value="Accouchements" <?php echo $registre['type_registre'] === 'Accouchements' ? 'selected' : ''; ?>>Registre des Accouchements</option>
                        <option value="Suivi Postnatal" <?php echo $registre['type_registre'] === 'Suivi Postnatal' ? 'selected' : ''; ?>>Registre du Suivi Postnatal</option>
                        <option value="Complications" <?php echo $registre['type_registre'] === 'Complications' ? 'selected' : ''; ?>>Registre des Complications</option>
                        <option value="Médicaments" <?php echo $registre['type_registre'] === 'Médicaments' ? 'selected' : ''; ?>>Registre des Médicaments</option>
                        <option value="Équipements" <?php echo $registre['type_registre'] === 'Équipements' ? 'selected' : ''; ?>>Registre des Équipements</option>
                    </select>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Description
                    </label>
                    <textarea name="description" id="description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="Description du registre..."><?php echo htmlspecialchars($registre['description']); ?></textarea>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="view.php?id=<?php echo $registre['id']; ?>" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                        Annuler
                    </a>
                    <button type="submit" class="bg-gradient-to-r from-purple-500 to-pink-500 text-white px-6 py-2 rounded-lg hover:shadow-lg transition-all duration-300">
                        <i class="fas fa-save mr-2"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 