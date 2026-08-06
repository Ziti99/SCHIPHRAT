<?php
require_once __DIR__ . '/vendor/autoload.php';

use Clinique\Services\Auth;
use Clinique\Helpers\Security;

Auth::initSecureSession();

// ⚠️ TEMPORAIRE: Simuler une session utilisateur sans login
if (empty($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['user_role'] = 'admin';
    $_SESSION['user_nom'] = 'Admin';
    $_SESSION['user_prenom'] = 'Test';
}

$user = [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'role' => $_SESSION['user_role'],
    'nom' => $_SESSION['user_nom'],
    'prenom' => $_SESSION['user_prenom']
];

$csrfToken = Security::generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white shadow-sm border-b border-gray-100 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-8">
                    <a href="/dashboard.php" class="flex items-center space-x-3">
                        <div class="w-9 h-9 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                            <i class="fas fa-heartbeat text-white"></i>
                        </div>
                        <span class="font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">Clinique</span>
                    </a>
                    <div class="hidden md:flex space-x-1">
                        <a href="/dashboard.php" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-purple-50"><i class="fas fa-home mr-2"></i>Dashboard</a>
                        <a href="/patientes.php" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 hover:bg-purple-50"><i class="fas fa-female mr-2"></i>Patientes</a>
                        <a href="/consultations.php" class="px-3 py-2 rounded-lg text-sm font-medium text-gray-700 bg-purple-50 text-purple-700"><i class="fas fa-stethoscope mr-2"></i>Consultations</a>
                    </div>
                </div>
                <a href="/logout.php" class="text-gray-400 hover:text-red-500 p-2"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
    </nav>
    <main>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <h1 class="text-2xl font-bold mb-6"><i class="fas fa-stethoscope mr-2 text-blue-500"></i>Consultations</h1>
            <button onclick="document.getElementById('consultModal').classList.toggle('hidden')" class="bg-blue-600 text-white px-6 py-2.5 rounded-lg mb-6">
                <i class="fas fa-plus mr-2"></i>Ajouter une consultation
            </button>
            <div class="bg-white rounded-xl shadow-sm border">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr class="text-left text-xs font-semibold uppercase">
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Patiente</th>
                            <th class="px-6 py-3">Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="3" class="px-6 py-8 text-center text-gray-500"><i class="fas fa-inbox text-2xl mb-2 block"></i>Aucune consultation enregistrée</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    <div id="consultModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black/30" onclick="document.getElementById('consultModal').classList.toggle('hidden')"></div>
            <div class="bg-white rounded-xl shadow-2xl p-8 max-w-md w-full z-50">
                <h2 class="text-2xl font-bold mb-6">Nouvelle consultation</h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <div>
                        <label class="block text-sm font-medium mb-2">Patiente *</label>
                        <input type="text" placeholder="Sélectionner une patiente" class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Date *</label>
                        <input type="date" name="date" required class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="submit" class="flex-1 bg-blue-600 text-white py-2 rounded-lg">Enregistrer</button>
                        <button type="button" onclick="document.getElementById('consultModal').classList.toggle('hidden')" class="flex-1 bg-gray-200 text-gray-800 py-2 rounded-lg">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <footer class="bg-white border-t border-gray-100 mt-12 py-6">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm text-gray-400">&copy; <?= date('Y') ?> Clinique Obstétrique</div>
    </footer>
</body>
</html>